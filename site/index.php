<?php
    // define the current minimum htaccess / config file versions
    define('OFW_HTACCESS_VERSION', 303);
    define('OFW_CONFIG_VERSION', 303);

    define('OFW_RECOMMENDED_HTACCESS_VERSION', 305);
    define('OFW_RECOMMENDED_CONFIG_VERSION', 305);

    // Backwards compatibility
    if (!empty($zajconf) && is_array($zajconf)) {
        $ofwconf = $zajconf;
    }

    // Auto-detect root folder if not set already
    if (empty($ofwconf['root_folder'])) {
        $ofwconf['root_folder'] = realpath(dirname(__FILE__).'/../../');
    }

    // Include the zajlib system class
    if (!(include $ofwconf['root_folder'].'/system/class/ofwconf.class.php')) {
        exit("<b>Outlast Framework error:</b> missing Outlast Framework system files or incorrect path given! set in site/index.php!");
    }

    /**
     * @var OfwConf
     */
    $ofwconf = new OfwConf($ofwconf);

    // Set my locale and numeric to US for compatibility
    setlocale(LC_ALL, $ofwconf['locale_default']);
    setlocale(LC_NUMERIC, $ofwconf['locale_numeric']);

    // Set timezone default
    if (!empty($ofwconf['timezone'])) {
        date_default_timezone_set($ofwconf['timezone']);
    } else {
        date_default_timezone_set('Europe/Budapest');
    }

    // Start execution
    $GLOBALS['execute_start'] = microtime(true);

    // Set default encoding to unicode
    ini_set('default_charset', 'utf-8');
    mb_internal_encoding("UTF-8");

    // Avoid scientific notation in large numbers (64bit int is 19 digits)
    ini_set('precision', 19);

    // Check for request errors
    if (!empty($_REQUEST['error'])) {
        if ($_REQUEST['error'] == "private") {
            exit("OUTLAST FRAMEWORK REQUEST ERROR: cannot access this folder!");
        }
        if ($_REQUEST['error'] == "norewrite") {
            exit("OUTLAST FRAMEWORK REQUEST ERROR: the required apache rewrite support not enabled!");
        }
    }

    // Check for versions
    if (empty($_REQUEST['zajhtver']) || $_REQUEST['zajhtver'] < OFW_HTACCESS_VERSION) {
        exit("OUTLAST FRAMEWORK VERSION ERROR: please update the htaccess file to the latest version!");
    }
    if (empty($ofwconf['config_file_version']) || $ofwconf['config_file_version'] < OFW_CONFIG_VERSION) {
        exit("OUTLAST FRAMEWORK VERSION ERROR: please update your main config file to the latest version!");
    }

    // Prepare my requests - trim app and mode
    $_REQUEST['zajapp'] = trim($_REQUEST['zajapp'], " _-\"\\'/");
    $_REQUEST['zajmode'] = trim($_REQUEST['zajmode'], " _-\"\\'/");

    // Figure out my relative path
    if (!empty($ofwconf['site_folder']) && empty($ofwconf['root_folder'])) {
        exit("OUTLAST FRAMEWORK CONFIG ERROR: If you set the site_folder parameter, you must also set the root_folder!");
    }

    // Include the zajlib system class
    if (!(include $ofwconf['root_folder'].'/system/class/zajlib.class.php')) {
        exit("<b>Outlast Framework error:</b> missing Outlast Framework system files or incorrect path given! set in site/index.php!");
    }

    // Create a new zajlib object
    $ofw = new zajLib($ofwconf['root_folder'], $ofwconf);

    // Set internal error handler
    set_error_handler(function ($errno, $errstr, $errfile, $errline, $errcontext) {
        if (!is_object(zajLib::me())) {
            print "FATAL ERROR: Check error log.";
        } else {
            zajLib::me()->error_handler($errno, $errstr, $errfile, $errline, $errcontext);
        }
    });

    // Don't use xdebug error handling
    if (function_exists('xdebug_disable')) {
        xdebug_disable();
    }

    // Set shutdown error handler (fatal)
    register_shutdown_function(function () {
        // Get error info (if there is one)
        $error = error_get_last();
        // Is there an error? Is it fatal or is it a parse error
        if ($error !== null && ($error['type'] == 4 || $error['type'] == 1)) {
            // Try to log it to file
            zajLib::me()->error_handler(E_USER_ERROR, $error['message'], $error['file'], $error['line']);
        }
    });

    // Debug mode needed?
    if (in_array($ofw->host,
            $ofwconf['debug_mode_domains']) || !empty($ofwconf['debug_mode']) || !empty($_SERVER['DEBUG_MODE']) || !empty($_SERVER['MOZAJIK_DEBUG_MODE']) || !empty($_SERVER['OFW_DEBUG_MODE'])) {
        $ofw->debug_mode = true;
    }

    // Debug mode explicity overridden?
    if ($ofw->debug_mode && isset($_REQUEST['debug_mode'])) {
        $ofw->debug_mode = false;
    }

    // Use debug database?
    if ($ofw->debug_mode && !empty($ofwconf['mysql_enabled_debug'])) {
        $ofw->ofwconf['mysql_enabled'] = $ofwconf['mysql_enabled_debug'];
        $ofw->ofwconf['mysql_server'] = $ofwconf['mysql_server_debug'];
        $ofw->ofwconf['mysql_user'] = $ofwconf['mysql_user_debug'];
        $ofw->ofwconf['mysql_password'] = $ofwconf['mysql_password_debug'];
        $ofw->ofwconf['mysql_db'] = $ofwconf['mysql_db_debug'];
        $ofw->ofwconf['mysql_ignore_tables'] = $ofwconf['mysql_ignore_tables_debug'];
    }

    // If LOGIN_AUTH is set up in Apache conf and user does not have proper cookie set, redirect!
    if (!empty($_SERVER['MOZAJIK_LOGIN_AUTH']) && !empty($_SERVER['MOZAJIK_LOGIN_URL'])) {
        // Check if whitelisted ip
        $whitelisted = false;
        if (!empty($_SERVER['MOZAJIK_LOGIN_WHITELIST'])) {
            // Get all IPs that are whitelisted
            $whitelisted_ips = explode(',', $_SERVER['MOZAJIK_LOGIN_WHITELIST']);
            // Check against my ip
            foreach ($whitelisted_ips as $whitelisted_ip) {
                if ($ofw->security->ip_in_range($ofw->request->client_ip(), $whitelisted_ip)) {
                    $whitelisted = true;
                }
            }
        }
        // Redirect to authentication
        if (!$whitelisted && $_SERVER['MOZAJIK_LOGIN_AUTH'] != $_COOKIE['MOZAJIK_LOGIN_AUTH']) {
            header("Location: ".$_SERVER['MOZAJIK_LOGIN_URL'].'?from='.urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']));
            exit;
        }
    }

    // Load controller support
    include_once($ofwconf['root_folder'].'/system/class/zajcontroller.class.php');

    // All init is completed, after this it's only checks and plugin loads, etcetc. @todo remove?
    if (!empty($GLOBALS['ZAJ_HOOK_INIT']) && is_callable($GLOBALS['ZAJ_HOOK_INIT'])) {
        $GLOBALS['ZAJ_HOOK_INIT']();
    }

    // Load plugins, but make sure we aren't using any reserved names
    $reserved_names = array_merge(['local', 'plugin_apps', 'system', 'system_apps', 'temp_block', 'compiled'],
        $ofwconf['system_apps']);
    foreach (array_reverse($ofwconf['plugin_apps']) as $plugin) {
        if (in_array($plugin, $reserved_names)) {
            exit("<b>Outlast Framework error:</b> you tried to load up a plugin with an invalid name. '$plugin' is a reserved or system app name!");
        }
        $ofw->plugin->load($plugin);
    }

    // Update in progress check
    if (file_exists($ofw->basepath."cache/progress.dat") && trim($ofw->app, '/') != $ofwconf['update_appname']) {
        $ofw->reroute($ofwconf['update_appname'].'/progress/');
    }

    // installation check
    $installation_valid = true;

    // 1. Check cache and data folder writable
    if (!is_writable($ofw->basepath."cache/") || !is_writable($ofw->basepath."data/")) {
        $installation_valid = false;
    }
    // 2. Check if activated
    if (!is_object($ofw->version)) {
        $installation_valid = false;
    }
    // 3. Activate model support and check system file validity (fatal error if not)
    if (!(include $ofwconf['root_folder'].'/system/class/zajmodel.class.php') || !(include $ofwconf['root_folder'].'/system/class/zajmodelextender.class.php')) {
        exit("<b>Outlast Framework error:</b> missing Outlast Framework system files or incorrect path given! set in site/index.php!");
    }
    if (!(include $ofwconf['root_folder'].'/system/class/zajmodellocalizer.class.php') || !(include $ofwconf['root_folder'].'/system/class/zajmodellocalizeritem.class.php')) {
        exit("<b>Outlast Framework error:</b> missing Outlast Framework system files or incorrect path given! set in site/index.php!");
    }
    // 4. Check database issues (if mysql is enabled) - this does not actually connect but newly installed sites should already run into (2) activation error. Again, fatal errors if missing.
    if ($ofwconf['mysql_enabled']) {
        // include the data and fetcher system class
        if (!(include $ofwconf['root_folder'].'/system/class/zajdata.class.php')) {
            exit("<b>Outlast Framework error:</b> missing Outlast Framework system files or incorrect path given! set in site/index.php!");
        }
        if (!(include $ofwconf['root_folder'].'/system/class/zajfetcher.class.php')) {
            exit("<b>Outlast Framework error:</b> missing Outlast Framework system files or incorrect path given! set in site/index.php!");
        }
        // load db library
        $ofw->load->library("db");
    }

    // 5. Check user/pass for update
    if (!$ofw->debug_mode && (empty($ofwconf['update_user']) || empty($ofwconf['update_password']))) {
        $installation_valid = false;
    }

    // Now reroute to install script if installation issues found and not explicitly disabled with $zaj_dont_install_mode
    if (empty($zaj_dont_install_mode) && !$installation_valid && trim($ofw->app,
            '/') != $ofwconf['update_appname']) {
        $ofw->redirect($ofwconf['update_appname'].'/install/');
    }

    // Select the right app and mode (todo: move this stuff to zajlib.class.php eventually)
    if (!isset($_REQUEST['zajapp']) || $_REQUEST['zajapp'] == '' || $_REQUEST['zajapp'] == "default") {
        $zaj_app = $ofwconf['default_app'];
    } else {
        $zaj_app = $_REQUEST['zajapp'];
    }

    // Select the mode (and trim trailing slash)
    if (!isset($_REQUEST['zajmode']) || $_REQUEST['zajmode'] == '' || $_REQUEST['zajmode'] == "default") {
        $zaj_mode = '';
    } else {
        $zaj_mode = trim($_REQUEST['zajmode'], "/");
    }

    // Ready to unset $_REQUEST!
    unset($_REQUEST['zajhtver'], $_REQUEST['zajapp'], $_REQUEST['zajmode']);
    // Now create url
    $app_request = $zaj_app."/".$zaj_mode;

    // Make zajlib backwards compatible
    $zajlib = $ofw;
