<?php
/**
 * Various security-related methods.
 * @author Aron Budinszky <aron@mozajik.org>
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
	 **/	 
	function protect_me($user,$password,$realm="default",$message="ACCESS DENIED!"){
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
	
	/**
	 * Generate a random password of a specified length.
	 * @param integer $length The length of the password. 10 by default.
	 * @return string The generated password.
	 **/
	function random_password($length = 10) {
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
}



?>