<?php
/**
 * Various security-related methods.
 * @author Aron Budinszky <aron@outlast.hu>
 * @version 3.0
 * @package Library
 **/

class zajlib_security extends zajLibExtension {

	/**
	 * Create an HTTP-AUTH dialog with the specified user and password.
	 * @param string $user The user-name required by the dialog.
	 * @param string $password The password required by the dialog.
	 * @param string $realm The realm is a string which specifies which area this access includes. Search google for HTTP AUTH for more details.
	 * @param string $message This message is displayed if the user fails to input the correct user/password.
	 * @return bool Returns true if successful authentication, exits otherwise.
	 */
	public function protect($user,$password,$realm="default",$message="ACCESS DENIED!"){
		// check if already logged in
			if($_SERVER['PHP_AUTH_USER']==$user && ($_SERVER['PHP_AUTH_PW']==$password || crypt($_SERVER['PHP_AUTH_PW'], "za")==$password || md5($_SERVER['PHP_AUTH_PW'])==$password)) return true;
		// if not then show login
			// convert to iso
				$this->zajlib->load->library("lang");
				$realm = $this->zajlib->lang->UTF2ISO($realm);
			// print headers
				header("WWW-Authenticate: Basic realm=\"$realm\"");
				header('HTTP/1.0 401 Unauthorized');
				echo "$message\n";
			exit;
	}
	/** @ignore **/
	public function protect_me($user,$password,$realm="default",$message="ACCESS DENIED!"){ $this->protect($user,$password,$realm,$message); }
	
	/**
	 * Generate a random password of a specified length.
	 * @param integer $length The length of the password. 10 by default.
	 * @return string The generated password.
	 **/
	public function random_password($length = 10) {
		$allowable_characters = "ABCDEFGHKMNPQRSTUVWXYZ23456789";
		// Explode string into array of characters
			$chars = str_split($allowable_characters);
		// Declare the password as a blank string.
			$pass = "";
		// Loop the number of times specified by $length and select a random char at each
			for($i = 0; $i < $length; $i++){
				$key = array_rand($chars);
				$pass .= $chars[$key];
			}
		// Retun the password we've selected
			return $pass;
	}

	/**
	 * Uses CORS to allows ajax requests from cross-domain origins. Sends headers so it must be called before any output. See link for IE issues.
	 * @param string $allow_origin The domain to allow, or * to whitelist everything. Defaults to *.
	 * @param string $allow_methods Allow the method by which to send data. List comma-separated. Defaults to POST, GET, OPTIONS.
	 * @link http://blogs.msdn.com/b/ieinternals/archive/2010/05/13/xdomainrequest-restrictions-limitations-and-workarounds.aspx
	 **/
	public function cors($allow_origin = '*', $allow_methods = 'POST, GET, OPTIONS'){
		// Enable cross-domain access
			header('Access-Control-Allow-Origin: '.$allow_origin);
			header('Access-Control-Allow-Credentials: true');
			header('Access-Control-Allow-Methods: '.$allow_methods);
			header('Access-Control-Allow-Headers: X-Requested-With, Content-Type');
	}

	/**
	 * Check code for xss, return boolean.
	 * @param string $string The string to run XSS detection logic on.
	 * @link https://github.com/symphonycms/xssfilter/blob/master/extension.driver.php#L138
	 * @return boolean True if the given string contains XSS, false if clean.
	 */
	public function has_xss($string){
		$contains_xss = false;
		// Skip any null or non string values or if xss protection is disabled
		if(is_null($string) || !is_string($string) || empty($this->zajlib->zajconf['feature_xss_protection_enabled'])){
			return $contains_xss;
		}
		// Keep a copy of the original string before cleaning up
		$orig = $string;
		// URL decode
		$string = urldecode($string);
		// Convert Hexadecimals
		$string = preg_replace_callback('!(&#|\\\)[xX]([0-9a-fA-F]+);?!',function($matches){ return chr(hexdec($matches[1])); }, $string);
		// Clean up entities
		$string = preg_replace('!(&#0+[0-9]+)!','$1;',$string);
		// Decode entities
		$string = html_entity_decode($string, ENT_NOQUOTES, 'UTF-8');
		// Strip whitespace characters
		$string = preg_replace('!\s!','',$string);
		// Set the patterns we'll test against
		$patterns = array(
			// Match any attribute starting with "on" or xmlns
			'#(<[^>]+[\x00-\x20\"\'\/])(on|xmlns)[^>]*>?#iUu',
			// Match javascript:, livescript:, vbscript: and mocha: protocols
			'!((java|live|vb)script|mocha|feed|data):(\w)*!iUu',
			'#-moz-binding[\x00-\x20]*:#u',
			// Match style attributes
			'#(<[^>]+[\x00-\x20\"\'\/])style=[^>]*>?#iUu',
			// Match unneeded tags
			'#</*(applet|meta|xml|blink|link|style|script|embed|object|iframe|frame|frameset|ilayer|layer|bgsound|title|base)[^>]*>?#i'
		);
		foreach($patterns as $pattern){
			// Test both the original string and clean string
			if(preg_match($pattern, $string) || preg_match($pattern, $orig)){
				$contains_xss = true;
			}
			if ($contains_xss === true) return true;
		}
		return false;
	}

	/**
	 * Check to see if the string is a valid ID. Valid IDs are anything with A-z0-9.
	 * @param string $id The id to check.
	 * @return boolean Will return true if valid, false if not.
	 */
	public function is_valid_id($id){
		return (boolean) preg_match('/^[A-z0-9]+$/', $id);
	}
	
	/**
	 * Checks if an IP address is within the specified range.
	 * 
	 * Network ranges can be specified as:
	 * 1. Wildcard format:     1.2.3.*
	 * 2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
	 * 3. Start-End IP format: 1.2.3.0-1.2.3.255
	 * The function will return true if the supplied IP is within the range.
	 *
	 * @param string|boolean $ip The ip address to check or an array of IP addresses to check. If set to false, my current IP will be used.
	 * @param string $range The ip address range to check in.
	 * @return boolean Will return true if the specified IP is within the given range.
	 **/
	public function ip_in_range($ip, $range){
		// default to current ip
			if($ip === false) $ip = $this->zajlib->request->client_ip();
	 	// if ip range is an array, then call for each one
	 		if(is_array($range)){
	 			foreach($range as $range_item){
	 				// Is item in range? Return true!
						$result = $this->ip_in_range($ip, $range_item);
						if($result) return true;
	 			}
	 			return false;
	 		}
		// if ip is equal to range
			if($ip == $range) return true;
		// otherwise...
			if(strpos($range, '/') !== false){
				// $range is in IP/NETMASK format
				list($range, $netmask) = explode('/', $range, 2);
				if(strpos($netmask, '.') !== false){
					// $netmask is a 255.255.0.0 format
					$netmask = str_replace('*', '0', $netmask);
					$netmask_dec = ip2long($netmask);
					return ( (ip2long($ip) & $netmask_dec) == (ip2long($range) & $netmask_dec) );
				}
				else{
					// $netmask is a CIDR size block
					// fix the range argument
					$x = explode('.', $range);
					while(count($x)<4) $x[] = '0';
					list($a,$b,$c,$d) = $x;
					$range = sprintf("%u.%u.%u.%u", empty($a)?'0':$a, empty($b)?'0':$b,empty($c)?'0':$c,empty($d)?'0':$d);
					$range_dec = ip2long($range);
					$ip_dec = ip2long($ip);

					# Strategy 1 - Create the netmask with 'netmask' 1s and then fill it to 32 with 0s
					#$netmask_dec = bindec(str_pad('', $netmask, '1') . str_pad('', 32-$netmask, '0'));

					# Strategy 2 - Use math to create it
					$wildcard_dec = pow(2, (32-$netmask)) - 1;
					$netmask_dec = ~ $wildcard_dec;

					return (($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec));
				}
			}
			else{
				// range might be 255.255.*.* or 1.2.3.0-1.2.3.255
				if (strpos($range, '*') !==false) { // a.b.*.* format
					// Just convert to A-B format by setting * to 0 for A and 255 for B
					$lower = str_replace('*', '0', $range);
					$upper = str_replace('*', '255', $range);
					$range = "$lower-$upper";
				}
				if (strpos($range, '-')!==false) { // A-B format
					list($lower, $upper) = explode('-', $range, 2);
					$lower_dec = (float)sprintf("%u",ip2long($lower));
					$upper_dec = (float)sprintf("%u",ip2long($upper));
					$ip_dec = (float)sprintf("%u",ip2long($ip));
					return ( ($ip_dec>=$lower_dec) && ($ip_dec<=$upper_dec) );
				}
				return false;
			}
	}
}