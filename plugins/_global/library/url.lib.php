<?php
/**
 * This library contains useful methods for dealing with URLs.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Library
 **/

class zajlib_url extends zajLibExtension {

	/**
	 * Reroutes the user to a specified controller. This is depricated and will be removed in 1.0. Use $this->zajlib->reroute() instead.
	 * @param string $request A url-like request to a controller.
	 * @param array $optional_parameters An array of parameters passed to the controller method.
	 * @return Returns whatever the rerouted method returns.
	 * @todo Remove this in 1.0.
	 **/
	function redirect($request, $optional_parameters = false){
		return $this->zajlib->reroute($request, $optional_parameters);
	}
	
	
	/**
	 * Fetches the domain without any subdomains for the given url. For example, for foo.bar.www.youtube.com it will return youtube.com.
	 * @param string $url The url to parse.
	 * @return string The domain portion of the url.
	 **/
	function get_domain($url){
		// Get my hostname
			$hostname = parse_url($url, PHP_URL_HOST);
		// Get my domain match
			preg_match('/^(.*)\.(.*)\.(.*)/', $hostname, $matches);
		// Return my proper match
			return $matches[2].'.'.$matches[3];
	}

	/**
	 * Fetches the subdomain, but excludes www. This is useful because users usually think www.news.domain.com is the same as news.domain.com and domain.com is the same as www.domain.com.
	 * @param string $url The url to parse.
	 * @return string The subdomain portion of the url.
	 **/
	function get_subdomain($url){
		// Get my hostname
			$hostname = parse_url($url, PHP_URL_HOST);
		// Get my subdomain match
			preg_match('/^(www.)*(.*)(\..*){2}/', $hostname, $matches);
		// Return my proper match
			return $matches[2];
	}


	/**
	 * Generates a friendly url based on an input string.
	 * @param string $title Any string such as a name or title.
	 * @return string The string converted to a url-friendly format (no accents, trimmed, no spaces)
	 **/
	function friendly($title){
		// convert accents and trim
			$title = mb_strtolower(trim($this->zajlib->lang->convert_eng($title)));
		// remove any remaining non-alpha numeric
			$title = preg_replace("/[^a-z0-9 ]/", "", $title);
		// remove spaces
			$title = str_ireplace(' ', '-', $title);
		// return trimmed
			return $title;
	}

	/**
	 * Returns true or false depending on whether the passed string is a valid URL.
	 * @param string $url The url to be parsed
	 * @return bool True if a valid url. False otherwise.
	 * @todo Move this to validation lib.
	 **/
	function is_url($url){
	 	return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
	}

	/**
	 * Returns true or false depending on whether the passed string is a valid email address.
	 * @param string $email The email to be parsed
	 * @return bool True if a valid email. False otherwise.
	 * @todo Move this to validation lib.
	 **/
	function is_email($email){
	 	// return @eregi('^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$', $email);
	 	return preg_match("/\A([^@\s]+)@((?:[-a-z0-9]+\.)+[a-z]{2,})\Z/", $email);
	}
	
	/**
	 * Sends a POST request to a specified url, by using the query string as post data. Supports HTTPS.
	 * @param string $url The url of the desired destination. Example: send_post("https://www.mozajik.org/akarmi.php?asdf=1&qwerty=2");
	 * @param bool $returnheaders If set to true, the headers will be returned as well. By default it is false, so only document content is returned.
	 **/
	function send_post($url, $returnheaders = false){
		// parse the url
			$urldata = parse_url($url);
			if($urldata === false) return false;
		// now send the POST request and return the result
			return $this->send_request($url, $urldata['query'], 'POST', array('Content-type'=>'application/x-www-form-urlencoded'), $returnheaders);		
	}
	
	/**
	 * Sends a request via GET or POST method to the specified url. Supports HTTPS.
	 * @param string $url The url of the desired destination.
	 * @param string $content The content of the document to be sent.
	 * @param string $method Specifies the method by which the content is sent. Can be GET (the default) or POST.
	 * @param array $customheaders An array of keys and values with custom headers to be sent along with the content.
	 * @param bool $returnheaders If set to true, the headers will be returned as well. By default it is false, so only document content is returned.
	 **/
	function send_request($url, $content, $method = 'GET', $customheaders = false, $returnheaders = false){
		// parse the url
			$urldata = parse_url($url);
			if($urldata === false) return false;
		
		// get port
			if($urldata['scheme'] == "https"){
				$port = 443;
				$prefix = "ssl://";
			}
			else $port = 80;
		// get method
			 if($method == 'POST') $method = 'POST';
			 else $method = 'GET';
		// assemble my headers (if none given)
			if(empty($customheaders)) $customheaders = array();
			if(!is_array($customheaders)) return $this->zajlib->error("Invalid format for custom headers! Must be a key/value array.");
			if(empty($customheaders['Content-type']) && empty($customheaders['content-type']) && empty($customheaders['Content-Type'])) $customheaders['Content-type'] = "text/html";
		// open remote host
			$fp = fsockopen($prefix.$urldata['host'], $port);
			if($fp === false) return false;
		// send GET or POST request
			fputs($fp, "$method $urldata[path] HTTP/1.1\r\n");
			fputs($fp, "Host: $urldata[host]\r\n");
			// Send custom headers
				foreach($customheaders as $key=>$value) fputs($fp, "$key: $value\r\n");
		// send the content		
			fputs($fp, "Content-length: ".strlen($content)."\r\n");
			fputs($fp, "Connection: close\r\n\r\n");
			fputs($fp, $content."\r\n\r\n");
		// get response
			$buf = '';
			while (!feof($fp)) $buf.=fgets($fp,102);
		// close connection
			fclose($fp);
		
		// now split into header and content
			$bufdata = explode("\r\n\r\n", $buf);
			$headers = $bufdata[0];
			$content = $bufdata[1];
		
		// now return what was requested
		if($returnheaders) return $buf;
		else return $content;
	
	}
	
	/**
	 * Redirects to a URL based on the current subdomain.
	 * @param string $from The subdomain to check for.
	 * @param string $to The URL to redirect to.
	 * @return bool Redirects or returns false.
	 **/
	function redirect_from_subdomain_to_url($from,$to){
		$subdomaindata = explode(".",$_SERVER[HTTP_HOST]);
		if($subdomaindata[0]==$from || $subdomaindata[1]==$from){
			// redirect me!
			header("Location: $to");
			exit;
		}
		return false;
	}
}



?>