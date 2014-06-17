<?php
/**
 * This library contains useful methods for dealing with URLs.
 * @author Aron Budinszky <aron@outlast.hu>
 * @version 3.0
 * @package Library
 **/

class zajlib_url extends zajLibExtension {

	/**
	 * Reroutes the user to a specified controller. This is depricated and will be removed in 1.0. Use $this->zajlib->reroute() instead.
	 * @param string $request A url-like request to a controller.
	 * @param array|bool $optional_parameters An array of parameters passed to the controller method.
	 * @return Returns whatever the rerouted method returns.
	 * @todo Remove this in 1.0.
	 */
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
			$hdata = explode('.', $hostname);
			$hc = count($hdata);
		// Return my proper match
			return $hdata[$hc-2].'.'.$hdata[$hc-1];
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
	 * Get ready for query string by adding a ? or & to the url.
	 * @param string $url Any url.
	 * @return string Will return a url that is definitely ready to append a query string.
	 */
	function querymode($url){
		// Does it have a ? already?
			if(strstr($url, '?')) return $url.'&';
			else return $url.'?';
	}

	/**
	 * Returns true or false depending on whether the passed string is a valid http URL.
	 * @param string $url The url to be parsed
	 * @param boolean $allow_spaces Allow spaces in query string. This will allow spaces in query string, but also in the url. True url-encoded strings should not require this since spaces are %20.
	 * @return bool True if a valid url. False otherwise.
	 * @todo Move this to validation lib.
	 **/
	function valid($url, $allow_spaces = true){
	 	if($allow_spaces) return (boolean) preg_match('/^(https?|ftp):\/\/[^\s\/$.?#].[\S ]*$/i', $url);
		else return (boolean) preg_match('/^(https?|ftp):\/\/[^\s\/$.?#].[^\s]*$/i', $url);
	}

	/**
	 * Redirects to a URL based on the current subdomain.
	 * @param string $from The subdomain to check for.
	 * @param string $to The URL to redirect to.
	 * @return bool Redirects or returns false.
	 **/
	function redirect_from_subdomain_to_url($from,$to){
		$subdomaindata = explode(".",$_SERVER['HTTP_HOST']);
		if($subdomaindata[0]==$from || $subdomaindata[1]==$from){
			// redirect me!
			header("Location: $to");
			exit;
		}
		return false;
	}




	/**
	 * @deprecated
	 * @ignore
	 **/
	function is_url($url){ return $this->valid($url); }

	/**
	 * Returns true or false depending on whether the passed string is a valid email address.
	 * @param string $email The email to be parsed
	 * @return bool True if a valid email. False otherwise.
	 * @ignore
	 * @deprecated
	 **/
	function is_email($email){
		return $this->zajlib->email->valid($email);
	 	//return preg_match("/\A([^@\s]+)@((?:[-a-z0-9]+\.)+[a-z]{2,})\Z/", $email);
	}


	/**
	 * Deprecated. Use {@link zajlib_request->post()} instead.
	 * @ignore
	 * @deprecated
	 **/
	function send_post($url, $returnheaders = false){
		return $this->zajlib->request->post($url, '', $returnheaders);
	}
	
	/**
	 * Deprecated. Use {@link zajlib_request->get()} instead.
	 * @ignore
	 * @deprecated
	 **/
	function send_request($url, $content='', $method = 'GET', $customheaders = false, $returnheaders = false){
		return $this->zajlib->request->get($url, $content, $returnheaders, $customheaders, $method);
	}

}