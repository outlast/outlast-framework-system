<?php
/**
 * This class allows the user to load files into the OFW system. These files may be libraries, apps, models, etc.
 * @package Base
 **/
class zajLibLoader{

    /**
     * The currently requested app with trailing slash. Default for example will be 'default/'.
     * @var string
     **/
    public $app;

    /**
     * The currently requested mode with trailing slash.
     * @var string
     **/
    public $mode;

	/**
	 * A reference to the global zajlib object.
	 * @var zajLib
	 **/
	protected $zajlib;

	/**
	 * A multi-dimensional array with the loaded files.
	 * @var array
	 **/
	public $loaded = array();

	/**
	 * Creates a new {@link zajLibLoader}. This is run when initializing the request.
	 * @param zajLib $zajlib A reference to the global zajlib object.
	 **/
	public function __construct(&$zajlib){
		// set my parent
			$this->zajlib =& $zajlib;
	}


	/**
	 * Load a controller file.
	 * @param string $file_name The relative file name of the controller to load.
	 * @param array|bool $optional_parameters An array or a single parameter which is passed as the first parameter to __load()
	 * @param boolean $call_load_method If set to true (the default), the __load() magic method will be called.
	 * @param boolean $fail_with_error_message If error, then fail with a fatal error.
	 * @return mixed|zajController Returns whatever the __load() method returns. If the __load() method is not invoked, the controller object is returned. A return by __load of explicit false is meant to signify a problem. Or it may also mean that the controller was not loaded (if $fail_with_error_message is false).
	 * @todo Rewrite $controller_name generation to regexp
	 **/
	public function controller($file_name, $optional_parameters=false, $call_load_method=true, $fail_with_error_message = true){
		// Load the file
			$loaded = $this->zajlib->load->file('controller/'.$file_name, $fail_with_error_message);
		// If failed to load, return
			if(!$loaded) return false;
		// Remove .ctl.php off of end and / to _
			$controller_name = str_ireplace('/', '_', substr($file_name, 0, -8));
		// If default, then fix it!
			if(substr($controller_name, -8) == '_default') $controller_name = substr($controller_name, 0, -8);
		// Create my class
			$controller_class = 'zajapp_'.$controller_name;
		// Create a new object
			$cobj = new $controller_class($this->zajlib, $controller_name);
			if($call_load_method && method_exists($cobj, "__load")) return $cobj->__load($this->zajlib->mode, $optional_parameters);
		// Return the controller object since no __load method
			return $cobj;
	}


	/**
	 * Load a library file.
	 * @param string $name The name of the library to load.
	 * @param array|bool $optional_parameters An array of optional parameters which are stored in {@link zajLibExtension->options}
	 * @param boolean $fail_with_error_message If error, then fail with a fatal error.
	 * @return zajLibExtension|bool Returns a zajlib object or false if fails.
	 */
	public function library($name, $optional_parameters=false, $fail_with_error_message = true){
		// is it loaded already?
			if(isset($this->loaded['library'][$name])) return $this->loaded['library'][$name];
		// try to load the file
			$result = $this->file("library/$name.lib.php", false);
		// if library does not exist
			if(!$result){
				if($fail_with_error_message) return $this->zajlib->error("Tried to auto-load library ($name), but failed: library file not found!");
				else return false;
			}
			else{
				// return the new lib object
					$library_class = 'zajlib_'.$name;
					$libobj = new $library_class($this->zajlib, $name);
					$libobj->options = $optional_parameters;
					$this->loaded['library'][$name] = $libobj;
					return $this->loaded['library'][$name];
			}
	}

	/**
	 * Load a model file.
	 * @param string $name The name of the model to load.
	 * @param array|boolean $optional_parameters This will be passed to the __load method (not yet implemented)
	 * @todo Implement optional parameters.
	 * @param boolean $fail_with_error_message If error, then fail with a fatal error.
	 * @return boolean Will return true if successfully loaded, false if not.
	 **/
	public function model($name, $optional_parameters = false, $fail_with_error_message = true){
		// is it loaded already?
			if(isset($this->loaded['model'][$name])) return true;
		// now just load the file
			$result = $this->file("model/".strtolower($name).".model.php", false);
		// return result
			if(!$result){
				if($fail_with_error_message) return $this->zajlib->error("model or app controller object <strong>$name</strong> has not been properly defined or does not exist! is the class name correctly defined in the model/ctl file?");
				else return false;
			}
			else{
				// set it as loaded
					$this->loaded['model'][$name] = true;
				return true;
			}
	}

	/**
	 * Load an app file and call the appropriate method.
	 * @param string $request The application request.
	 * @param array|bool $optional_parameters An array of parameters passed to the request method.
	 * @param boolean $reroute_to_error When set to true (the default), the function will reroute requests to the proper __error method.
	 * @param boolean $call_load_method If set to true (the default), the __load() magic method will be called.
	 * @return bool|mixed Returns whatever the app endpoint returns.
	 */
	public function app($request, $optional_parameters=false, $reroute_to_error=true, $call_load_method=true){
		// check for security
			if(substr_count($request, "..") > 0) $this->zajlib->error("application request ($request) could not be processed: illegal characters!");
		// remove the starting and trailing slash
			$request = trim($request, '/\\');
		// remove double-slashes - @todo remove multiple slashes? - its slower and do we need it? not really...
			$request = str_ireplace('//','/',$request);
		// set defaults
			$result = false;
			$fnum = 1;
			$fmax = substr_count($request, "/")+1;
		// break into pieces
			$rdata = explode("/",$request);
		// order: /admin/whatever/ => 1. admin.ctl.php / whatever(), 2. admin/whatever.ctl.php / main() 3. admin/whatever/default.ctl.php / main() 4. admin/default.ctl.php / whatever() 5. default.ctl.php / admin_whatever();
		// - __error is called on the lowest default.ctl.php found. if the error is not found there, then it does not propogate upward...

		// now try to go through various alternatives ( 1. admin.ctl.php / whatever, 2. admin/whatever.ctl.php )
			while(!$result && $fnum <= $fmax){
				// create file name
					$zaj_app = implode("/", array_slice($rdata, 0, $fnum));
					$zaj_mode = implode("_", array_slice($rdata, $fnum));
				// now try to load the file
					$result = $this->file("controller/".strtolower($zaj_app).".ctl.php", false);
				// add one
					$fnum++;
			}
		// Fnum is now one two big!
			$fnum--;
		// now try to go through various alternatives (3. admin/whatever/default.ctl.php / 4. admin/default.ctl.php) if app is defined
			while(!empty($zaj_app) && !$result && $fnum >= 1){
				// create file name
					$zaj_app = implode("/", array_slice($rdata, 0, $fnum));
					$zaj_mode = implode("_", array_slice($rdata, $fnum));
				// now try to load the file
					$result = $this->file("controller/".strtolower($zaj_app).'/'.strtolower($this->zajlib->zajconf['default_app']).'.ctl.php', false);
				// add one
					$fnum--;
			}
		// if result still not successful just do default (5. default.ctl.php)
			if(!$result){
				// create file name
					$zaj_app = $this->zajlib->zajconf['default_app'];
					$zaj_mode = implode("_", $rdata);
				// now try to load the file
					$result = $this->file("controller/".strtolower($zaj_app).".ctl.php", false);
					if(!$result) $this->error("default controller not in place. you must have a $zaj_app.ctl.php file in your controller folder!");
			}

		// if zaj_mode not defined
			if(empty($zaj_mode)) $zaj_mode = strtolower($this->zajlib->zajconf['default_mode']);

		//////////////////////////////////////////////////
		// - zaj_mode and zaj_app are properly defined!
		// - now, let's direct to the right method
		//////////////////////////////////////////////////

		// make it a proper object name
			$zaj_app = str_ireplace('/', '_', $zaj_app);
		// set zajlib's app and mode
			$this->app = $zaj_app;
			$this->mode = $zaj_mode;

        // Replace - with _ in mode
			$zaj_mode = str_ireplace('-', '_', $zaj_mode);

		// assemble optional parameters
			if(!$optional_parameters) $optional_parameters = array();
			elseif(!is_array($optional_parameters)){
				$op[] = $optional_parameters;
				$optional_parameters = $op;
			}

		// start the app controller
			$app_object_name = "zajapp_".$zaj_app;
			$my_app = new $app_object_name($this->zajlib, $zaj_app);
		// fire __load magic method if call_load_method is true
			$load_result = true;
			if($call_load_method && method_exists($my_app, "__load")){
				$load_result = $my_app->__load($zaj_mode, $optional_parameters);
			}
		// if __load() explicitly returns false, then do not continue with but instead return false
			if($load_result === false) return false;

		// if method does not exist, call __error
			// TODO: make errors go backwards as well: check child folder's default controllers first!
			if(!method_exists($my_app, $zaj_mode)){
				// If I have an __error method and it is allowed, reroute to that
					if(method_exists($my_app, '__error') && $reroute_to_error) return $my_app->__error($zaj_mode, $optional_parameters);
				// If no error method, but $reroute_to_error is true, throw an error
					elseif($reroute_to_error){
						// Check if not already default
							if($zaj_app == $this->zajlib->zajconf['default_app']) $this->zajlib->error("Could not route request and default controller does not implement __error() method.");
						// Split into sections and remerge into parent
							$parent_controller = implode('_', array_slice(explode('_', $zaj_app), 0, -1));
						// Set to default
							if(empty($parent_controller)) $parent_controller = $this->zajlib->zajconf['default_app'];
						// Reroute to parent method's error method
							// TODO: fix so that first parameter passed is correct (currently it is not!)
							return $this->app($parent_controller.'/__error', array($zaj_app.'_'.$zaj_mode, $optional_parameters));
						//return $this->zajlib->error("Could not route $request and $zaj_app no __error method found.");
					}
				// If reroute to error is disabled, then dont check and dont make noise - just return true.
					else return true;
			}
		// it exist, so call!
			else return call_user_func_array(array(&$my_app,$zaj_mode),$optional_parameters);
	}

	/**
	 *  @todo Both js and css - preloading could be made more effecient by including them in the header during compilation.
	 *		Certain fields require certain js and css files, so it should be easy to rewrite this such that all of this is
	 *		already done during template compilation. This would eliminate the need for run-time file_exist and client-side
	 *		in-line loading of files, both of which are less efficient.
	 **/

	/**
	 * Load a js file runtime.
	 * @param string $file_path The file path relative to the system or site folder.
	 * @param boolean $check_if_exists Not implemented.
	 * @return void|bool Prints the string generated, returns nothing or true if already printed.
	 * @deprecated
	 **/
	public function js($file_path, $check_if_exists = false){
		// is it loaded already?
			if(isset($this->loaded['js'][$file_path])) return true;
		// set it as loaded
			$this->loaded['js'][$file_path] = true;
		// check to see if this file exists in the user folder...if so, then use that instead of the system-provided version
			if(file_exists($this->zajlib->basepath."site/js/$file_path")) $subfolder = "";
			else $subfolder = "system";
		// now load the js file into zajlib.js variable OR print it
			if(!$this->zajlib->output_started) $this->zajlib->variable->js .= "\n\t\t<script language='JavaScript' src='".$this->zajlib->baseurl."$subfolder/js/$file_path' type='text/javascript'></script>";
			else print "<script>zajlib.load_js('".$this->zajlib->baseurl."$subfolder/js/$file_path');</script>";
	}

	/**
	 * Load a css file runtime.
	 * @param string $file_path The file path relative to the system or site folder.
	 * @param boolean $check_if_exists Not implemented.
	 * @return void|bool Prints the string generated, returns nothing or true if already printed.
	 * @deprecated
	 **/
	public function css($file_path, $check_if_exists = false){
		// is it loaded already?
			if(isset($this->loaded['css'][$file_path])) return true;
		// set it as loaded
			$this->loaded['css'][$file_path] = true;
		// check to see if this file exists in the user folder...if so, then use that instead of the system-provided version
			if(file_exists($this->zajlib->basepath."site/css/$file_path")) $subfolder = "";
			else $subfolder = "system";
		// now load the css file into zajlib.css variable OR print it
			if(!$this->zajlib->output_started) $this->zajlib->variable->css .= "\n\t\t<link rel='stylesheet' type='text/css' href='".$this->zajlib->baseurl."$subfolder/css/$file_path' type='text/javascript'></script>";
			else print "<script>new Asset.css('".$this->zajlib->baseurl."$subfolder/css/$file_path', { oncomplete: function(){ zajlib.onCssLoad(); } } ); zajlib.asset_css_load_at_runtime++;</script>";
	}

	/**
	 * Include a file as relative to the base path.
	 * @param string $file_path The file path relative to the base path.
	 * @param boolean $fail_with_error_message If error, then fail with a fatal error.
	 * @param boolean $include_now If set to true (the default), the file will also be included. On false, only the file path will be returned (and $this->loaded will not be set to true!).
	 * @param string $scope Can be "full" (looks for all variations - default), "specific" (looks for a specific relative path and fails if not found), "project" (looks for anything in the projects folder), "plugin" (looks for anything in the plugins folder), "system" (looks for anything in the system folder)
	 * @return boolean Returns false on error, otherwise returns the path of the file found, relative to to basepath
	 **/
	public function file($file_path, $fail_with_error_message = true, $include_now = true, $scope = "full"){
		// is it loaded already?
			if(isset($this->loaded['file'][$file_path])) return true;
		// test file path
			if(!$this->check_path($file_path)) $this->zajlib->error("Invalid file path detected when including file. Please refer to manual for requirements.");


		// Is it a specific path scope? If so, just try to load it!
			if($scope == "specific"){
				if(file_exists($this->zajlib->basepath.$file_path) && (!$include_now || include_once $this->zajlib->basepath.$file_path)){
					if($include_now) $this->loaded['file'][$file_path] = true;
					return $file_path;
				}
			}
		// Else, I need to search subfolders
			else{
				// 1. try the project path
					if($scope == "full" || $scope == "project"){
						if(file_exists($this->zajlib->basepath.'app/'.$file_path) && (!$include_now || include_once $this->zajlib->basepath.'app/'.$file_path)){
							if($include_now) $this->loaded['file'][$file_path] = true;
							return 'app/'.$file_path;
						}
					}
				// 2. try plugin paths in order
					if($scope == "full" || $scope == "plugin"){
						foreach($this->zajlib->loaded_plugins as $app){
							if(file_exists($this->zajlib->basepath.'plugins/'.$app.'/'.$file_path) && (!$include_now || include_once $this->zajlib->basepath.'plugins/'.$app.'/'.$file_path)){
								// set file as loaded and return true
									if($include_now) $this->loaded['file'][$file_path] = true;
									return 'plugins/'.$app.'/'.$file_path;
							}
						}
					}
				// 3. try the system path
					if($scope == "full" || $scope == "system"){
						if(file_exists($this->zajlib->basepath.'system/app/'.$file_path) && (!$include_now || include_once $this->zajlib->basepath.'system/app/'.$file_path)){
							if($include_now) $this->loaded['file'][$file_path] = true;
							return 'system/app/'.$file_path;
						}
					}
				// 4. try the system plugins
					if($scope == "full" || $scope == "system"){
						foreach($this->zajlib->zajconf['system_apps'] as $app){
							if(file_exists($this->zajlib->basepath.'system/plugins/'.$app.'/'.$file_path) && (!$include_now || include_once $this->zajlib->basepath.'system/plugins/'.$app.'/'.$file_path)){
								if($include_now) $this->loaded['file'][$file_path] = true;
								return 'system/plugins/'.$app.'/'.$file_path;
							}
						}
					}
			}
		// None worked, so fail with error or return false
			if($fail_with_error_message) $this->zajlib->error("Search for included file $file_path failed. Is the plugin activated? Is the file where it should be?");
			else return false;
	}

	/**
	 * Checks to see if file of certain type has been loaded.
	 * @param string $type The type (for example, 'library')
	 * @param string $name The name of the element to load.
	 * @return boolean True if already loaded, false otherwise.
	 **/
	public function is_loaded($type, $name){
		if(isset($this->loaded[$type][$name]) && $this->loaded[$type][$name]) return true;
		else return false;
	}

	/**
	 * Does a security check to see if the given path is valid and is chrooted.
	 * @param string $file_path The path to check.
	 * @return boolean Returns true if the path is valid and ready to be used. False otherwise.
	 * @todo Add more checks!
	 */
	public static function check_path($file_path){
		if(substr_count($file_path, "..") > 0) return false;
		// todo: do some more checks here!
		return true;
	}
}