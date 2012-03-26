<?php
	// define the current minimum htaccess / config file versions
		define('MOZAJIK_HTACCESS_VERSION', 303);
		define('MOZAJIK_CONFIG_VERSION', 303);
	
	// start execution
		$GLOBALS['execute_start'] = microtime(true);
	// set default encoding to unicode
		ini_set('default_charset','utf-8');
		mb_internal_encoding("UTF-8");
	// check for request errors
		if(!empty($_REQUEST['error'])){
			if($_REQUEST['error'] == "querystring") exit("MOZAJIK REQUEST ERROR: cannot explicity use zajapp or zajmode in GET or POST query!");
			if($_REQUEST['error'] == "private") exit("MOZAJIK REQUEST ERROR: cannot access this folder!");
			if($_REQUEST['error'] == "norewrite") exit("MOZAJIK REQUEST ERROR: the required apache rewrite support not enabled!");
		}
	// check for versions
		if(empty($_REQUEST['zajhtver']) || $_REQUEST['zajhtver'] < MOZAJIK_HTACCESS_VERSION) exit("MOZAJIK VERSION ERROR: please update the htaccess file to the latest version!");
		if(empty($GLOBALS['zaj_config_file_version']) || $GLOBALS['zaj_config_file_version'] < MOZAJIK_CONFIG_VERSION) exit("MOZAJIK VERSION ERROR: please update your main config file to the latest version!");
	// prepare my requests - trim app and mode
		$_REQUEST['zajapp'] = trim($_REQUEST['zajapp'], " _-\"\\'/");
		$_REQUEST['zajmode'] = trim($_REQUEST['zajmode'], " _-\"\\'/");
		
	// figure out my relative path
		if($GLOBALS['zaj_site_folder'] != "" && $GLOBALS['zaj_root_folder'] == "") exit("MOZAJIK CONFIG ERROR: If you set the zaj_site_folder parameter, you must also set the zaj_root_folder!");
		if($GLOBALS['zaj_site_folder'] == "" && $GLOBALS['zaj_root_folder'] == "") $GLOBALS['zaj_root_folder'] = "..";
	// set the default system plugins (for backwards compatibility)
		if(empty($GLOBALS['zaj_system_apps'])) $GLOBALS['zaj_system_apps'] = array('_global', '_mootools');
	// include the zajlib system class
		if (!(include $GLOBALS['zaj_root_folder'].'/system/class/zajlib.class.php')) exit("<b>zajlib error:</b> missing zajlib system files or incorrect path given! set in site/index.php!");
	// create a new zajlib object
		$zajlib = new zajLib($GLOBALS['zaj_root_folder']);
	// set internal error handler
		set_error_handler(function($errno, $errstr, $errfile, $errline, $errcontext){ if(!is_object($GLOBALS['zajlib'])){ print "FATAL ERROR: Check error log."; } else $GLOBALS['zajlib']->error_handler($errno, $errstr, $errfile, $errline, $errcontext);});			
	// debug mode needed?
		if(in_array($zajlib->host, $GLOBALS['debug_mode_domains']) || !empty($GLOBALS['debug_mode']) || !empty($_SERVER['DEBUG_MODE'])) $zajlib->debug_mode = true;
	// debug mode explicity overridden?
		if($zajlib->debug_mode && isset($_REQUEST['debug_mode'])) $zajlib->debug_mode = false;
	// load default libraries
		$zajlib->load->library('text');
		$zajlib->load->library('template');

	// load controller support
		include_once($GLOBALS['zaj_root_folder'].'/system/class/zajcontroller.class.php');

	// update progress check
		if(file_exists($zajlib->basepath."cache/progress.dat") && $zajlib->app != $GLOBALS['zaj_update_appname']) $zajlib->reroute($GLOBALS['zaj_update_appname'].'/progress/');

	// installation check
		$installation_valid = true;
		
		// 1. Check cache and data folder writable
			if(!is_writable($zajlib->basepath."cache/") || !is_writable($zajlib->basepath."data/")) $installation_valid  = false;
		// 2. Check if activated
			if(!is_object($zajlib->mozajik)) $installation_valid = false;
		// 3. Activate model support and check system file validity (fatal error if not)
			if (!(include $GLOBALS['zaj_root_folder'].'/system/class/zajmodel.class.php')) exit("<b>zajlib error:</b> missing zajlib system files or incorrect path given! set in site/index.php!");
		// 4. Check database issues (if mysql is enabled) - this does not actually connect but newly installed sites should already run into (2) activation error. Again, fatal errors if missing.
			if($GLOBALS['zaj_mysql_enabled']){	
				// include the data and fetcher system class
					if (!(include $GLOBALS['zaj_root_folder'].'/system/class/zajdata.class.php')) exit("<b>zajlib error:</b> missing zajlib system files or incorrect path given! set in site/index.php!");
					if (!(include $GLOBALS['zaj_root_folder'].'/system/class/zajfetcher.class.php')) exit("<b>zajlib error:</b> missing zajlib system files or incorrect path given! set in site/index.php!");
				// load db library
					$zajlib->load->library("db");
			}
		// 5. Check user/pass for update
			if(!$zajlib->debug_mode && (empty($GLOBALS['zaj_update_user']) || empty($GLOBALS['zaj_update_password']))) $installation_valid  = false;			

	// Now reroute to install script if installation issues found			
		if(!$installation_valid && $zajlib->app != $GLOBALS['zaj_update_appname']) $zajlib->reroute($GLOBALS['zaj_update_appname'].'/install/');

	// select the right app and mode
		// select
			if(!isset($_REQUEST['zajapp']) || $_REQUEST['zajapp']=='' || $_REQUEST['zajapp'] == "default") $zaj_app = $GLOBALS['zaj_default_app'];
			else $zaj_app = $_REQUEST['zajapp'];
		// select the mode (and trim trailing slash)
			if(!isset($_REQUEST['zajmode']) || $_REQUEST['zajmode']=='' || $_REQUEST['zajmode'] == "default") $zaj_mode = '';
			else $zaj_mode = trim($_REQUEST['zajmode'], "/");
	// now create url
		$app_request = $zaj_app."/".$zaj_mode;

	// load plugin default files - run through each activated plugin and reroute to /PLUGIN_NAME/__plugin()
		foreach(array_reverse($GLOBALS['zaj_plugin_apps']) as $plugin){
			// only do this if either default controller exists in the plugin folder
				if(file_exists($zajlib->basepath.'plugins/'.$plugin.'/controller/'.$plugin.'.ctl.php') || file_exists($zajlib->basepath.'plugins/'.$plugin.'/controller/'.$plugin.'/default.ctl.php')){			
					// reroute but if no __plugin method, just skip without an error message (TODO: maybe remove the false here?)!
						$result = $zajlib->reroute($plugin.'/__plugin/', array($app_request, $zaj_app, $zaj_mode), false);
				}
		}

?>