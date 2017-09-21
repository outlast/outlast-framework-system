<?php
/**
 * Template related methods.
 * @author Aron Budinszky <aron@outlast.hu>
 * @version 3.0
 * @package Library
 **/

class zajlib_template extends zajLibExtension {

	/**
	 * If set to true, it means that all templates will be force-recompiled (as if in debug mode).
     * @todo Remove this!
	 */
	public $force_recompile = false;

	/**
	 * Compile the file specified by file_path.
	 * @param string $source_path This is the source file's path relative to any of the active view folders.
	 * @param bool|string $destination_path This is the destination file's path relative to the final compiled view folder. If not specified, the destination will be the same as the source (relative), which is the preferred way of doing things. You should only specify this if you are customizing the template compilation process.
	 * @return void
	 */
	private function compile($source_path, $destination_path=false){
		// load compile library
			$this->zajlib->compile->compile($source_path, $destination_path);
	}

	/**
	 * Prepares all files and variables for output. Compiles the file if necessary.
	 * @param string $source_path The path to the template to be compiled relative to the active view folders.
	 * @param boolean $force_recompile If set to true, the template file will be recompiled even if a cached version already exists. (False by default.)
	 * @param bool|string $destination_path This is the destination file's path relative to the final compiled view folder. If not specified, the destination will be the same as the source (relative), which is the preferred way of doing things. You should only specify this if you are customizing the template compilation process.
	 * @return string Returns the file path of the file to include.
	 */
	private function prepare($source_path, $force_recompile=false, $destination_path=false){
		// include file path
			if(!$destination_path) $include_file = $this->zajlib->basepath."/cache/view/".$source_path.".php";
			else $include_file = $this->zajlib->basepath."/cache/view/".$destination_path.".php";
		// if force_recompile or debug_mode or not yet compiled then recompile
			if($this->zajlib->debug_mode || $this->force_recompile || $force_recompile || !file_exists($include_file)) $this->compile($source_path, $destination_path);
		// set up my global {{ofw}} variable object if not yet set up
		    if(!is_object($this->zajlib->variable->ofw) || !is_a($this->zajlib->variable->ofw, 'zajlib_template_zajvariables')){
                $this->zajlib->variable->ofw = new zajlib_template_zajvariables($this->zajlib);
                $this->zajlib->variable->zaj = $this->zajlib->variable->ofw;
		    }
		// set up a few other globals
			$this->zajlib->variable->baseurl = $this->zajlib->baseurl;
			$this->zajlib->variable->fullurl = $this->zajlib->fullurl;
			$this->zajlib->variable->fullrequest = $this->zajlib->fullrequest;
		
		/*******
		 * ALL VARIABLES BELOW ARE NOT TO BE USED! THEY WILL BE REMOVED IN A FUTURE RELEASE!
		 *******/
		
			$this->zajlib->variable->self = $this->zajlib->fullrequest; // @deprecated!
		// access to request variables and version info
			$this->zajlib->variable->debug_mode = $this->zajlib->variable->ofw->bc_get('debug_mode');
			$this->zajlib->variable->app = $this->zajlib->variable->ofw->bc_get('app');
			$this->zajlib->variable->mode = $this->zajlib->variable->ofw->bc_get('mode');
		// init js layer
		// requests and urls
			if($this->zajlib->https) $this->zajlib->variable->protocol = 'https';
			else $this->zajlib->variable->protocol = 'http';
			$this->zajlib->variable->get = (object) $_GET;
			$this->zajlib->variable->post = (object) $_POST;
			$this->zajlib->variable->cookie = (object) $_COOKIE;
			$this->zajlib->variable->request = (object) $_REQUEST;
			if(!empty($_SERVER['HTTP_REFERER'])) $this->zajlib->variable->referer = $_SERVER['HTTP_REFERER'];
		return $include_file;
	}
	
	/**
	 * Returns an object containing the built-in 'zaj' variables that are available to the template.
	 * @return stdClass An object with the zaj variables.
	 **/
	public function get_variables(){
		return new zajlib_template_zajvariables($this->zajlib);
	}
	
	/**
	 * Performs the actual display or return of the contents.
	 * @param string $include_file The full path to the file which is to be included.
	 * @param boolean $return_contents If set to true, the compiled contents will be returned by the function and not sent to the browser (as is the default).
	 * @return string If requested by the $return_contents parameter, it returns the entire generated contents.
	 **/
	private function display($include_file, $return_contents = false){
		// now include the file
			// but should i return the contents?
				if($return_contents) ob_start();	// start output buffer
			// now include the file
				include($include_file);
			// verify validity
				if($return_contents){ 				// end output buffer
					$contents = ob_get_contents();
					ob_end_clean();
					return $contents;
				}
				else return true;
	}

	/**
	 * Display a specific template.
	 * If the request contains zaj_pushstate_block, it will reroute to block. See Outlast Framework pushState support for more info.
	 * @param string $source_path The path to the template to be compiled relative to the active view folders. 
	 * @param boolean $force_recompile If set to true, the template file will be recompiled even if a cached version already exists. (False by default.)
	 * @param boolean $return_contents If set to true, the compiled contents will be returned by the function and not sent to the browser (as is the default).
	 * @param boolean $custom_compile_destination If set, this allows you to compile the template to a different location than the default. This is not recommended unless you really know what you are doing!
	 * @return string|boolean If requested by the $return_contents parameter, it returns the entire generated contents.
	 **/
	public function show($source_path, $force_recompile = false, $return_contents = false, $custom_compile_destination = false){
		// override source path if device mode @todo make this more efficient so it does not search files each time!
			$source_path = $this->get_source_path($source_path);
		// do i need to show by block (if pushState request detected)
			if($this->zajlib->request->is_ajax() && !empty($_REQUEST['zaj_pushstate_block']) && preg_match("/^[a-z0-9_]{1,25}$/", $_REQUEST['zaj_pushstate_block'])){ $r = $_REQUEST['zaj_pushstate_block']; unset($_REQUEST['zaj_pushstate_block']); return $this->block($source_path, $r, $force_recompile, $return_contents); }
		// prepare
			$include_file = $this->prepare($source_path, $force_recompile, $custom_compile_destination);
		// set that we have started the output
			$this->zajlib->output_started = true;
		// now display or return
			return $this->display($include_file, $return_contents);
	}
	
	/**
	 * Extracts a specific block from a template and displays only that. This is useful for ajax requests.
	 * If the request contains zaj_pushstate_block, it will display that block. See Outlast Framework pushState support for more info.
	 * @param string $source_path The path to the template to be compiled relative to the active view folders.
	 * @param string $block_name The name of the block within the template.
	 * @param boolean $recursive If set to true (false by default), all parent files will be checked for this block as well.
	 * @param boolean $force_recompile If set to true, the template file will be recompiled even if a cached version already exists. (False by default.)
	 * @param boolean $return_contents If set to true, the compiled contents will be returned by the function and not sent to the browser (as is the default).
	 * @return bool|string Returns the contents if requested or false if failure.
	 */
	public function block($source_path, $block_name, $recursive = false, $force_recompile = false, $return_contents = false){
		// override source path if device mode @todo make this more efficient so it does not search files each time!
			$source_path = $this->get_source_path($source_path);
		// do i need to show by block (if pushState request detected)
			if($this->zajlib->request->is_ajax() && !empty($_REQUEST['zaj_pushstate_block']) && preg_match("/^[a-z0-9_]{1,25}$/", $_REQUEST['zaj_pushstate_block'])){ $block_name = $_REQUEST['zaj_pushstate_block']; unset($_REQUEST['zaj_pushstate_block']); }
		// first do a show to compile (if needed)
			$this->prepare($source_path, $force_recompile);
		// set that we have started the output
			$this->zajlib->output_started = true;
		// now extract and return the block content
			// generate appropriate file name
				$include_file = $this->zajlib->basepath."/cache/view/__block/".$source_path.'-'.$block_name.'.html.php';
			// check to see if block even exists
				if(!file_exists($include_file)){
					// if recursive and extended_path exists, try
					if($recursive){
						// see if extended
							$extend = $this->zajlib->compile->tags->extend;
							if($extend) return $this->block($extend, $block_name, $recursive, $force_recompile, $return_contents);
					}
					return $this->zajlib->error("Template block display failed! The request block '$block_name' could not be found in template file '$source_path'.");
				}
		// now display or return
			return $this->display($include_file, $return_contents);
	}

    /**
     * Modify the template source path based on device mode and locale.
	 * @param string $source_path The source path to check for.
     * @todo A html extension should not be required for this!
     * @todo The device and locale source paths should be combinable!
     * @todo This should be cached somehow so that we dont need so many checks
	 * @return string Return a new source path for the current device if available or the same if not.
     */
    private function get_source_path($source_path){
        $source_path = $this->get_device_source_path($source_path);
        return $this->get_locale_source_path($source_path);
    }

	/**
	 * Modify the template source path of any .html files if we are in a device mode (if set_device_mode() was called previously).
	 * @param string $source_path The source path to check for.
	 * @return string Return a new source path for the current device if available or the same if not.
	 */
	private function get_device_source_path($source_path){
		// Get the device
		$device_mode = $this->zajlib->browser->get_device_mode();

		// Do we have the device explicitly set?
		if(strstr($source_path, '?') !== false){
			// Parse out ?device=something
			$elements = explode('?', $source_path);
			parse_str($elements[1], $query_string);

			// Set device mode
			if(!empty($query_string['device_mode'])){
				$device_mode = $query_string['device_mode'];
				$source_path = $elements[0];
			}
		}

		// If the device mode is false or it is the default, just return the unmodified source path
		if($device_mode === false || $this->zajlib->browser->is_device_mode_default()) return $source_path;

		// It's not the default, so let's check to see if
		$device_source_path = str_ireplace('.html', '.'.$device_mode.'.html', $source_path);
		if($this->exists($device_source_path)) return $device_source_path;
		else return $source_path;
	}

	/**
	 * Modify the template source path of any .html files if we have a locale-specific template available.
	 * @param string $source_path The source path to check for.
     * @todo Add support for {% extends %} and {% insert %}
	 * @return string Return a new source path for the current locale if available or the same if not.
	 */
	private function get_locale_source_path($source_path){
		// Get the locale
		$locale = $this->zajlib->lang->get();

		// If the locale is not set, just return the unmodified source path
		if($locale === false) return $source_path;

		// It's not the default, so let's check to see if
		$device_source_path = str_ireplace('.html', '.'.$locale.'.html', $source_path);
		if($this->exists($device_source_path)) return $device_source_path;
		else return $source_path;
	}

	/**
	 * Returns true if a template file exists anywhere in the available paths based on the source path. Same as $this->zajlib->compile->source_exists().
	 * @param string $source_path The source path to check for.
	 * @return boolean Returns true if found, false if not.
	 */
	public function exists($source_path){
		return $this->zajlib->compile->source_exists($source_path);
	}

	/**
	 * This function will push the contents of the template to the user as a downloadable file. Useful for generating output like xml, csv, etc. The method will exit after execution is finished.
	 * @param string $source_path Path to the template file.
	 * @param string $mime_type The mime type by which to initiate the download.
	 * @param string $download_file_name The file name to use for this download.
	 * @param bool $force_download If set to true, the content will never be displayed within the browser. True is the default and the recommended setting.
	 * @param bool $force_recompile If set to true, the template will always be forced to recompile. Defaults to false.
	 */
	public function download($source_path, $mime_type, $download_file_name, $force_download=true, $force_recompile=false){
		// pass file thru to user
			header('Content-Type: '.$mime_type);
			//header('Content-Length: '.filesize($source_path)); // can i somehow detect this?!
			if($force_download) header('Content-Disposition: attachment; filename="'.$download_file_name.'"');
			else header('Content-Disposition: inline; filename="'.$download_file_name.'"');
			ob_clean();
			flush();
		// now sent to show
			$this->show($source_path, $force_recompile);
			exit;
	}


	/**
	 * Will return the output as an ajax response, setting the appropriate headers.
	 * @param string $source_path The path to the template to be compiled relative to the active view folders.
	 * @param bool|string $block_name If specified, only this block tag of the template file will be returned in the request.
	 * @param boolean $force_recompile If set to true, the template file will be recompiled even if a cached version already exists. (False by default.)
	 * @return boolean Will return true if successful.
	 */
	public function ajax($source_path, $block_name = false, $force_recompile = false){
		// send ajax header
			if(!$this->zajlib->output_started) header("Content-Type: application/x-javascript; charset=UTF-8");
		// now just show
			if(!is_string($block_name)) return $this->show($source_path, $force_recompile);
			else return $this->block($source_path, $block_name, $force_recompile);
	}
	
	/**
	 * Emails the template in an HTML format and returns true if successful.
	 * @param string $source_path The path to the template to be compiled relative to the active view folders. 
	 * @param string $from The email which is displayed as the from field.
	 * @param string $to The email to which this message should be sent.
	 * @param string $subject A string with the email's subject.
	 * @param string $sendcopyto If set, a copy of the email will be sent (bcc) to the specified email address. By default, no copy is sent.
	 * @param bool|array $additional_headers Any additional email headers you may want to send defined as a key/value pair.
	 * @param bool|string $plain_text_version The path to the template to be compiled for the plain text version.
	 * @param bool|integer $send_at Unix timestamp of the delayed sending or false if no delay is needed
     * @param bool $save_log If set to true (the default) it will log the sent email in the database.
	 * @return bool Will return true. Depending on the email gateway implementation it may return false if the email failed.
	 */
	public function email($source_path, $from, $to, $subject, $sendcopyto = "", $additional_headers = false, $send_at = false, $plain_text_version = false, $save_log = true){
		// capture output of this template
        $body = $this->show($source_path, false, true);

		// capture output of plain text template
        if($plain_text_version !== false){
            $plain_text_version = $this->show($plain_text_version, false, true);
            if($additional_headers === false) $additional_headers = [];
            $additional_headers['TextBody'] = $plain_text_version;
        }

		// set html
        return $this->zajlib->email->send($from, $to, $subject, $body, $sendcopyto, $additional_headers, $send_at, $save_log);
	}

	/**
	 * Strip XSS and throw a warning if such code is found.
	 * @param string $string The incoming string.
	 * @param string $context An optional string to specify context when warning message sent.
	 * @return string Returns the string if safe and empty string (with warning()) if an error is found.
	 */
	public function strip_xss($string, $context = ""){
		if($this->zajlib->security->has_xss($string)){
			$this->zajlib->warning("XSS attempt found and has been stripped. ".$context);
			return '';
		}
		return $string;
	}
}

/**
 * This is a special class which loads up the template variables when requested.
 * @author Aron Budinszky <aron@outlast.hu>
 * @property zajLib $zajlib
 **/
class zajlib_template_zajvariables {
	private $zajlib;	// The local copy of zajlib variable
	
	public $baseurl; 		// The base url of this project.
	public $fullurl; 		// The base url + the request.
	public $fullrequest; 	// The base url + the request + query string.
    public $tmp;            // A class that contains any temporary variables. Can be used in tags.
	
	/**
	 * Initializes all of the important variables which are always available.
	 **/
	public function __construct($zajlib){
		// First get my zajlib
			$this->zajlib = $zajlib;
		// Important variables
			$this->baseurl = $this->zajlib->baseurl;
			$this->fullurl = $this->zajlib->fullurl;
			$this->fullrequest = $this->zajlib->fullrequest;
		// Constants
			$this->zajlib->variable->true = true;
			$this->zajlib->variable->false = false;
        // Create an empty class to store temporary variables that need to be potentially passed on
            $this->tmp = new stdClass();
		// The rest of the variables are built on request via the __get() magic method...
	}
	
	/**
	 * Backwards-compatible access to these variables (will throw warning).
	 * @todo Remove this from a future version (when the depricated vars are removed as well)
	 **/
	public function bc_get($name){
		//$this->zajlib->warning("You are using an depricated variable ({{{$name}}}). Please use {{ofw.variable_name}} for all such variables.");
		return $this->__get($name);
	}	
	
	/**
	 * Generate and return all other useful variables only upon request.
	 **/
	public function __get($name){
		switch($name){
			// Debug mode			
				case 'debug':
				case 'debug_mode':
					return $this->zajlib->debug_mode;
			// My current app, mode, requestpath
				case 'app': return $this->zajlib->app;
				case 'mode': return $this->zajlib->mode;
                case 'requestpath': return $this->zajlib->requestpath;
                case 'self':
                    $this->zajlib->deprecated("ofw.self is deprecated, use ofw.requestpath instead.");
                    return $this->zajlib->requestpath;
			// The GET request
				case 'get': return $this->zajlib->array->to_object($_GET);
			// The POST request
				case 'post': return $this->zajlib->array->to_object($_POST);
			// The COOKIE request
				case 'cookie': return $this->zajlib->array->to_object($_COOKIE);
			// The REQUEST request
				case 'request': return $this->zajlib->array->to_object($_REQUEST);
			// The SERVER variables
				case 'server': return $this->zajlib->array->to_object($_SERVER);
			// The current protocol (HTTP/HTTPS)
				case 'protocol': return $this->zajlib->protocol;
			// Domain and top level domain
				case 'host': return $this->zajlib->host;
				case 'subdomain': return $this->zajlib->subdomain;
				case 'domain': return $this->zajlib->domain;
				case 'tld': return $this->zajlib->tld;
			// True if https
				case 'https': return $this->zajlib->https;
			// Return the current locale
				case 'locale': return $this->zajlib->lang->get();
				case 'locale_all': return $this->zajlib->lang->get_locales();
				case 'locale_default': return $this->zajlib->lang->get_default_locale();
			// Return the current lang (two letter version of locale)
				case 'lang': return $this->zajlib->lang->get_code();
			// Outlast Framework version info and other stuff
				case 'ofw':
				case 'mozajik':
				    $this->zajlib->warning("{{ofw.ofw}} and {{ofw.mozajik}} are deprecated. Use {{ofw.version}} instead.");
				    return $this->zajlib->mozajik;
                case 'version': return $this->zajlib->mozajik;
			// Mobile and tablet detection (uses server-side detection)
				case 'mobile': return $this->zajlib->mobile->is_mobile();
				case 'tablet': return $this->zajlib->mobile->is_tablet();
				case 'device_mode': return $this->zajlib->browser->get_device_mode();
			// Access to list of variables and config variables
				case 'variable': return $this->zajlib->variable;
				case 'config': return $this->zajlib->config->variable;
			// Platform detection (uses server-side detection, returns string from browser.lib.php)
				case 'platform': return $this->zajlib->browser->platform;
			// Server-side browser detection. Returns parameters from browser.lib.php.
				case 'browser': return $this->zajlib->browser->get();
			// Return the current time
				case 'now': return time();
			// Referer
				case 'referer': if(!empty($_SERVER['HTTP_REFERER'])) return $_SERVER['HTTP_REFERER']; else return '';
			// User-agent
				case 'useragent': if(!empty($_SERVER['HTTP_USER_AGENT'])) return $_SERVER['HTTP_USER_AGENT']; else return '';
			// Return which plugins are loaded
				case 'plugin':
					$array = $this->zajlib->plugin->get_plugins();
					return (object) array_combine($array, $array);
			// JS layer init script
				case 'js':
					if($this->zajlib->https) $protocol = 'https'; else $protocol = 'http';
					if($this->zajlib->debug_mode) $debug_mode = 'true';
					else $debug_mode = 'false';
					// Get my event track settings
						if($this->zajlib->zajconf['trackevents_analytics'] === false) $trackevents_analytics = 'false';
						else $trackevents_analytics = 'true';
						if($this->zajlib->zajconf['trackevents_local'] === true) $trackevents_local = 'true';
						else $trackevents_local = 'false';
					// Locale
						$locale = $this->zajlib->lang->get();
					// Disable
					$baseurl = htmlspecialchars($this->zajlib->baseurl, ENT_QUOTES);
					$fullrequest = str_ireplace("'", "&#39;", $this->zajlib->fullrequest);
					$fullurl = htmlspecialchars($this->zajlib->fullurl, ENT_QUOTES);
					$app = htmlspecialchars($this->zajlib->app, ENT_QUOTES);
					$mode = htmlspecialchars($this->zajlib->mode, ENT_QUOTES);
					return <<<EOF
<script type='text/javascript'>
    var ofwsettings = {
        baseurl: '{$protocol}:{$baseurl}',
        fullrequest: '{$protocol}:{$fullrequest}',
        fullurl: '{$protocol}:{$fullurl}',
        app: '{$app}',
        mode: '{$mode}',
        debug_mode: $debug_mode,
        protocol: '{$protocol}',
        trackeventsLocal: $trackevents_local,
        trackeventsAnalytics: $trackevents_analytics,
        locale: '$locale'
    };

    if(typeof require != 'undefined'){
        /** require init **/
        require.config({
            baseUrl: "{$baseurl}",
            urlArgs: "cachebuster=" + (new Date()).getTime()    	
        });
        if(typeof ofw == 'undefined' || ofw == null){
            // Backwards compatibility for unready set langs
            var ofwSetLang = [];
            // Define ready and jquery is ready
            var ofw = {
                ready: function(func){
                    ofw.readyFunctions.push(func);
                },
                setLang: function(keyOrArray, value, section){ ofwSetLang.push([keyOrArray, value, section]); },
                log: function(m){ console.log(m) },
                readyFunctions: [],
                jqueryIsReady: false	
            };
            $(document).ready(function(){ ofw.jqueryIsReady = true; });
            var zaj = ofw;
            
            // Now require and create
            requirejs(["system/js/ofw-jquery"], function(ofwsys){
                // Set my ready functions and init
                ofwsettings.readyFunctions = ofw.readyFunctions;
                ofwsettings.jqueryIsReady = ofw.jqueryIsReady;
                ofwsys.init(ofwsettings);
                // Now call each ofw set lang
                for(var i = 0; i < ofwSetLang.length; i++) ofwsys.setLang(ofwSetLang[i][0], ofwSetLang[i][1], ofwSetLang[i][2]);    
                // Finally, set variables
                ofw = zaj = ofwsys;
            });
            
            // Define jquery so that require knows about it
            define('jquery', [], function() {
                return jQuery;
            });
        }
    }
    else{
        /** legacy init **/
        if(typeof zaj != 'undefined'){
            $.extend(zaj, ofwsettings);
            var ofw = zaj;
        }
    }
</script>
EOF;

			// By default return nothing.
				default: return '';
		}
	}

}