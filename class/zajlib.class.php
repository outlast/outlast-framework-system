<?php
    /**
     * The Outlast Framework base classes.
     * @author Aron Budinszky <aron@outlast.hu>
     * @version 3.0
     * @package Base
     */

    define('MAX_GLOBAL_EVENT_STACK', 50);

    /**
     * The zajlib class is a single, global object which stores all the basic methods and variables. It is accessible from all controller and model objects.
     * @package Base
     * @property zajlib_array $array
     * @property zajlib_browser $browser
     * @property zajlib_cache $cache
     * @property zajlib_compile|zajCompileSession $compile
     * @property zajlib_config $config
     * @property zajlib_cookie $cookie
     * @property zajlib_db|zajlib_db_session $db
     * @property zajlib_dom $dom
     * @property zajlib_email $email
     * @property zajlib_error $error
     * @property zajlib_export $export
     * @property zajlib_feed $feed
     * @property zajlib_file $file
     * @property zajlib_form $form
     * @property zajlib_graphics $graphics
     * @property zajlib_import $import
     * @property zajlib_lang $lang
     * @property zajlib_memcache $memcache
     * @property zajlib_mobile $mobile
     * @property zajlib_model $model
     * @property ofw_plugin $plugin
     * @property zajlib_request $request
     * @property zajlib_sandbox $sandbox
     * @property ofw_security $security
     * @property zajlib_template $template
     * @property ofw_test $test
     * @property zajlib_text $text
     * @property zajlib_url $url
     * @property string $requestpath This is read-only public.
     * @todo All instance variables should be changed to read-only!
     **/
    class zajLib {
        // instance variables
        // my path and url
        /**
         * The project root directory, with trailing slash. This is automatically determined.
         * @var string
         **/
        public $basepath;
        /**
         * The project root url, with trailing slash. This is automatically determined and will include a /subfolder/ if need be.
         * @var string
         **/
        public $baseurl;
        /**
         * The project root's subfolder if there is any. Will be empty if none, will have trailing slash if it is set. This is automatically determined.
         * @var string
         **/
        public $basefolder;
        /**
         * The full request URL without the query string.
         * @var string
         **/
        public $fullurl;
        /**
         * The full request URL including the query string.
         * @var string
         **/
        public $fullrequest;
        /**
         * The request path with trailing slash but without base url and without query string. Private because it is built up from scratch on request.
         * @var string
         **/
        private $requestpath = null;
        /**
         * The host of the current request. This is automatically determined, though keep in mind the end user can modify this!
         * @var string
         **/
        public $host;
        /**
         * The top level domain and the current domain. (example: 'outlast.hu' for framework.outlast.hu)
         * @var string
         **/
        public $domain = "";
        /**
         * The port of the current request. This will be empty when running on the default port.
         * @var string
         **/
        public $port = "";
        /**
         * The top level domain. (example: 'hu' for framework.outlast.hu)
         * @var string
         **/
        public $tld = "";
        /**
         * The subdomain, excluding www. (example: 'framework' for www.framework.outlast.hu or for framework.outlast.hu)
         * @var string
         **/
        public $subdomain = "";
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
         * The currently active htaccess file version.
         * @var integer
         **/
        public $htver;
        /**
         * Set to true if current request is a https secure request.
         * @var boolean
         **/
        public $https = false;            // boolean - am i in secure mode?
        /**
         * Set to the current protocol. Can be http: or https:.
         * @var string
         **/
        public $protocol = 'http:';
        /**
         * Set to true if output to user has begun already.
         * @var boolean
         **/
        public $output_started = false;
        /**
         * Set to true if compile of any template has begun already.
         * @var boolean
         **/
        public $compile_started = false;
        /**
         * An object which stores version information.
         * @var MozajikVersion
         **/
        public $mozajik;
        /**
         * A boolean value which if set to false turns off autoloading of model files. This can be useful when integrating in other systems.
         * @var boolean
         **/
        public $model_autoloading = true;
        /**
         * An object which stores the configuration values set in site/index.php.
         * @var OfwConf
         **/
        public $ofwconf;

        /**
         * @deprecated
         */
        public $zajconf;


        // my settings

        /**
         * True if debug mode is currently on.
         * @var boolean
         **/
        public $debug_mode = false;
        /**
         * Template vraiable for storing javascript logs.
         * @var string
         **/
        public $js_log;
        /**
         * An array of custom tag files.
         * @todo Depricated and should be removed from 1.0 version.
         * @var boolean
         **/
        public $customtags = false;
        /**
         * A count of notices during this execution.
         * @var integer
         **/
        public $num_of_notices = 0;
        /**
         * A count of sql queries during this execution.
         * @var integer
         **/
        public $num_of_queries = 0;
        /**
         * The time of SQL queries in ms
         * @var integer
         **/
        public $time_of_queries = 0;

        // template variables
        /**
         * An object which stores the template variables.
         * @var stdClass|zajVariable
         **/
        public $variable;

        /**
         * The global event stack size.
         * @var integer
         **/
        public $event_stack = 0;

        // libraries
        /**
         * A library to find and include other files.
         * @var zajLibLoader
         */
        public $load;

        // status of plugins

        /**
         * An array of plugins loaded.
         * @var array
         **/
        public $loaded_plugins = [];

        /**
         * Creates a the zajlib object.
         * @param string $root_folder The root from which basepath and others are calculated.
         * @param OfwConf $ofwconf The configuration array.
         */
        public function __construct($root_folder, $ofwconf) {
            // autodetect my path
            if ($root_folder) {
                $this->basepath = realpath($root_folder)."/";
            } else {
                $this->basepath = realpath(dirname(__FILE__)."/../../")."/";
            }
            // store configuration
            $this->ofwconf = $ofwconf;
            $this->zajconf = $this->ofwconf;
            // parse query string
            if (isset($_GET['zajapp'])) {
                // autodetect my app
                $this->app = $_GET['zajapp'];
                $this->mode = $_GET['zajmode'];
                $this->htver = $_GET['zajhtver'];
                // set GET query string (cut off zajapp and zajmode)
                unset($_GET['zajapp'], $_GET['zajmode'], $_GET['zajhtver']);
            } else if (isset($_POST['zajapp'])) {    // TODO: is this even needed?
                // autodetect my app
                $this->app = $_POST['zajapp'];
                $this->mode = $_POST['zajmode'];
                $this->htver = $_POST['zajhtver'];
                // set POST query string (cut off zajapp and zajmode)
                unset($_POST['zajapp'], $_POST['zajmode'], $_GET['zajhtver']);
            }
            // default app & mode
            if (empty($this->app)) {
                $this->app = $this->ofwconf['default_app'];
                $this->mode = $this->ofwconf['default_mode'];
            }

            // Determine https
            $is_https =
                // Apache normal mode
                (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off") ||
                // Apache in proxy mode
                (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == "https") ||
                // Nginx and certain Apache configs
                (!empty($_SERVER['HTTP_HTTPS']) && $_SERVER['HTTP_HTTPS'] != "off");
            // autodetect https protocol, if set
            if ($is_https) {
                $this->https = true;
                $this->protocol = 'https:';
            }

            // Get host (and port number)
            $this->host = self::server_host();
            if (empty($this->host)) {
                print "Invalid request. Please contact site administrator.";
                $this->error("Empty host detected. Request denied. If you experience this error from a legitimate browser please notify us!",
                    true);
            }

            // base url detection
            $this->fullurl = "//".preg_replace('(/{2,})', '/', preg_replace("([?&].*|/{1,}$)", "",
                        addslashes($this->host).addslashes($_SERVER['REQUEST_URI'])).'/');
            $this->basefolder = str_ireplace('/site/index.php', '', $_SERVER['SCRIPT_NAME']);
            if ($this->basefolder) {
                $this->basefolder .= '/';
            }
            $this->baseurl = '//'.trim($this->host.$this->basefolder, '/').'/';
            // Now override base url if needed
            if (!empty($_SERVER['OFW_BASEURL'])) {
                // Parse my baseurl
                $parsed_baseurl = parse_url($_SERVER['OFW_BASEURL']);
                // Make sure it is an array
                if ($parsed_baseurl === false) {
                    return $this->error("Malformed OFW_BASEURL set as Apache environmental variable: ".$_SERVER['OFW_BASEURL'].".");
                }
                // Set protcol
                if ($parsed_baseurl['scheme'] == 'http') {
                    $this->https = false;
                } else if ($parsed_baseurl['scheme'] == 'https') {
                    $this->https = true;
                    $this->protocol = 'https:';
                } else {
                    return $this->error("Malformed OFW_BASEURL set as Apache environmental variable: ".$_SERVER['OFW_BASEURL'].".");
                }
                // Originals
                $original_fullurl = $this->fullurl;
                $original_baseurl = $this->baseurl;
                // Set host, base url, basefolder
                $this->host = $parsed_baseurl['host'];
                $this->basefolder = $parsed_baseurl['path'];
                $this->baseurl = '//'.trim($this->host.$this->basefolder, '/').'/';
                $this->fullurl = $this->baseurl.str_ireplace($original_baseurl, '', $original_fullurl);
            }
            // full request detection (includes query string)
            if (!empty($_GET)) {
                // reset query string
                $_SERVER['QUERY_STRING'] = http_build_query($_GET);
                // build full request
                $this->fullrequest = $this->fullurl.'?'.$_SERVER['QUERY_STRING'];
            } else {
                // no query string in this case
                $_SERVER['QUERY_STRING'] = '';
                // build full request with ?
                $this->fullrequest = $this->fullurl.'?';
            }
            // fix my app and mode to always have a single trailing slash
            $this->app = trim($this->app, '/').'/';
            $this->mode = trim($this->mode, '/').'/';

            // autodetect my domain (todo: optimize this part with regexp!)
            // if not an ip address
            if (!preg_match('/^([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}$/',
                $this->host)) {
                // split the port (if it exists)
                $pdata = explode(':', $this->host);
                $this->host = $pdata[0];
                if (!empty($pdata[1])) {
                    $this->port = $pdata[1];
                } else {
                    $this->port = "";
                }
                // process domain and subdomain
                $ddata = explode(".", $this->host);
                $this->domain = join(".", array_slice($ddata, -2));
                $this->subdomain = str_replace("www.", "",
                    join(".", array_slice($ddata, 0, -2)));        // will exclude www.!
                if ($this->subdomain == "www") {
                    $this->subdomain = "";
                }                                // if only www, then set to none!
                $slice = array_slice($ddata, -1);
                $this->tld = reset($slice);
            }
            // loader
            $this->load = new zajLibLoader($this);
            // template variable object
            $this->variable = new zajVariable();        // for all variables
            $this->variable->field = (object)[];        // for field templates scope
            $this->variable->plugins = (object)[];        // for plugins scope
            $this->variable->ofw = (object)[];         // for framework system scope


            // check and load installation version (only for database format tracking)
            if (file_exists($this->basepath.'cache/install.dat')) {
                $installation = file_get_contents($this->basepath.'cache/install.dat');
                $this->mozajik = @unserialize($installation);
            }

            return true;
        }

        /**
         * Get the server host. Because of forwarding or clusters, this may actually be different from HTTP_HOST.
         */
        public static function server_host($remove_port = false) {
            $possible_host_sources = ['HTTP_X_FORWARDED_HOST', 'HTTP_HOST'];
            $source_transformations = [
                "HTTP_X_FORWARDED_HOST" => function ($value) {
                    $elements = explode(',', $value);

                    return trim(end($elements));
                },
            ];
            $host = '';
            foreach ($possible_host_sources as $source) {
                if (!empty($host)) {
                    break;
                }
                if (empty($_SERVER[$source])) {
                    continue;
                }
                $host = $_SERVER[$source];
                if (array_key_exists($source, $source_transformations)) {
                    $host = $source_transformations[$source]($host);
                }
            }

            // Remove port number from host
            if ($remove_port) {
                $host = preg_replace('/:\d+$/', '', $host);
            }

            return trim($host);
        }

        /**
         * Returns true or false depending on whether the external file has been loaded already. This is simply an alias of the {@link zajLibLoader->is_loaded()}.
         * @param string $type The type of the file (library, etc.)
         * @param string $name The name of the file.
         * @return bool Returns true if the file is loaded, false otherwise.
         **/
        public function is_loaded($type, $name) {
            return $this->load->is_loaded($type, $name);
        }


        /**
         * Unlike load->app this actually changes the app and mode variables!
         * @param string $request
         * @param bool $allow_magic_methods
         * @return mixed The value returned by the loaded app.
         */
        public function app_mode_redirect($request, $allow_magic_methods = true) {
            // TODO: check - and add if needed - subfolder support!
            // if magic methods aren't allowed
            if (!$allow_magic_methods && strpos($request, "__") !== false) {
                return $this->error("invalid request. invoke magic methods is not allowed here!");
            }
            // get all the seperate elements
            $rdata = explode("/", trim("/", $request));
            // now figure out which one is app and which one is mode
            $newapp = array_shift($rdata);
            $newmode = join("_", $rdata);
            // now set my new app&mode
            $this->app = $newapp;
            $this->mode = $newmode;

            // finally, load me and return
            return $this->load->app($request);
        }


        /**
         * Returns an error message and exists. Useful for fatal errors.
         * @param string $message The error message to display and/or log.
         * @param boolean $display_to_users If set to true, the message will also be displayed to users even if not in debug mode. Defaults to false with a generic error message displayed.
         * @return bool Returns false if error messages are surpressed (during test). Otherwise terminates.
         **/
        public function error($message, $display_to_users = false) {
            // Manually load error reporting lib
            /* @var zajlib_error $error */
            $error = $this->load->library('error');
            // Now report the error and send 500 error
            if (!$this->output_started) {
                header('HTTP/1.1 500 Internal Server Error');
            }
            $error->error($message, $display_to_users);    // this terminates the run

            return false;
        }

        /**
         * Returns a warning message but continues execution.
         * @param string $message The warning message to display and/or log.
         * @return bool Always returns false.
         **/
        public function warning($message) {
            // Manually load error reporting lib
            /* @var zajlib_error $error */
            $error = $this->load->library('error');
            // Now report the error and send 500 error in debug mode
            if (!$this->output_started && $this->debug_mode) {
                header('HTTP/1.1 500 Internal Server Error');
            }

            return $error->warning($message);
        }

        /**
         * Returns a deprecation message but continues execution.
         * @param string $message The deprecation message to display and/or log.
         * @return bool Always returns false.
         **/
        public function deprecated($message) {
            // Manually load error reporting lib
            /* @var zajlib_error $error */
            $error = $this->load->library('error');

            // Now report the error
            return $error->deprecated($message);
        }


        /**
         * Displays a query in the browser log.
         * @param string $message
         **/
        public function query($message) {
            // todo: log this instead of printing it?
            if (isset($_GET['query'])) {
                $query_backtrace = debug_backtrace(false);
                //$this->js_log .= " zaj.ready(function(){zaj.log('ZAJLIB SQL QUERY: ".str_replace("'","\\'",$message).' in '.$query_backtrace[6]['file'].' on line '.$query_backtrace[6]['line']."'); });";
            }
            $this->num_of_queries++;
        }

        /**
         * Displays a notice in the browser log.
         * @param string $message The notice message to log.
         **/
        public function notice($message) {
            // todo: log this instead of printing it?
            if (isset($_GET['notice'])) {
                //if($_GET['notice']=="screen") print "<div style='border: 2px red solid; padding: 5px;'>MOZAJIK NOTICE: $message</div>";
                //else $this->js_log .= " zaj.ready(function(){zaj.log('ZAJLIB NOTICE: ".str_replace("'","\\'",$message)."'); });";
            }
            $this->num_of_notices++;
            // log notices?
            // @todo add notice logging!
        }

        /**
         * Custom error handler to override the PHP defaults.
         **/
        public function error_handler($errno, $errstr, $errfile, $errline) {
            // get current error_reporting value
            $errrep = error_reporting();

            if ($errrep) {
                switch ($errno) {
                    case E_NOTICE:
                    case E_USER_NOTICE:
                        $this->notice("$errstr on line $errline in file $errfile");
                        break;
                    case E_WARNING:
                    case E_USER_WARNING:
                        $this->warning("$errstr on line $errline in file $errfile");
                        break;
                    case E_ERROR:
                    case E_USER_ERROR:
                        $this->error("$errstr on line $errline in file $errfile");
                        break;
                    default:
                        //$errors = "Unknown Error Occurred";
                        break;
                }
            }

            return true;
        }

        /**
         * Send an ajax response to the browser or return if test.
         * @param string $message The content to send to the browser.
         * @param boolean $return If set to true, the result will be returned instead of sent to the browser.
         * @return bool Does not return anything.
         **/
        public function ajax($message, $return = false) {
            // If test or return requested
            if ($return || $this->test->is_running()) {
                return $message;
            }

            // If actual
            header("Content-Type: application/x-javascript; charset=UTF-8");
            print $message;
            exit;
        }

        /**
         * Send json data to the browser or return if test.
         * @param string|array|object $data This can be a json-encoded string or any other data (in this latter case it would be converted to json data).
         * @param boolean $return If set to true, the result will be returned instead of sent to the browser.
         * @return bool If return is true, the json data is returned. Otherwise it is sent to the browser.
         **/
        public function json($data, $return = false) {
            // If return requested, then just return
            if ($return) {
                return $data;
            }

            // If the data is not already a string, convert it with json_encode()
            if (!is_string($data)) {
                $data = json_encode($data);
            }

            // If test running
            if ($this->test->is_running()) {
                return $data;
            }

            // If real, output and exit!
            header("Content-Type: application/json; charset=UTF-8");
            print $data;
            exit;
        }

        /**
         * Redirect the user to relative or absolute URL
         * @param string $url The specific url to redirect the user to.
         * @param integer|boolean $status_code HTTP status code of the redirection. None by default.
         * @param boolean $frame_breakout If set to true, it will use javascript redirect to break out of iframe.
         * @return bool Does not yet return anything.
         **/
        public function redirect($url, $status_code = false, $frame_breakout = false) {
            // For backward compatibility @todo Remove this
            if (is_bool($status_code)) {
                $frame_breakout = $status_code;
                $status_code = false;
            }

            // Get HTTP protocol
            $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');

            // Now redirect if real
            if (!$this->url->valid($url)) {
                $url = $this->baseurl.$url;
            }
            // If test return url
            if ($this->test->is_running()) {
                return $url;
            }
            // Frame breakout or standard?
            if ($frame_breakout) {
                exit("<script>window.top.location='".addslashes($url)."';</script>");
            } else {
                // Push headers
                if ($status_code) {
                    header($protocol." ".$status_code." ".$this->request->get_http_status_name($status_code));
                }
                header("Location: ".$url);
            }
            exit;
        }

        /**
         * Reroute processing to another app controller.
         * @param string $request The request relative to my baseurl.
         * @param array|bool $optional_parameters An array of parameters to be passed.
         * @param boolean $reroute_to_error When set to true (the default), the function will reroute requests to the proper __error method.
         * @param boolean $call_load_method If set to true (the default), the __load() magic method will be called.
         * @return mixed Will return whatever the app method returns.
         */
        public function reroute(
            $request,
            $optional_parameters = false,
            $reroute_to_error = true,
            $call_load_method = true
        ) {
            // request must be a string
            if (!is_string($request)) {
                $this->warning('Invalid reroute request!');
            }

            // load the app
            return $this->load->app($request, $optional_parameters, $reroute_to_error, $call_load_method);
        }

        /**
         * Magic method to automatically load libraries or magic properties on first request.
         * @param string $name The name of the library or property.
         * @return zajLibExtension|mixed Return the library class or some other magic property.
         **/
        public function __get($name) {
            // load smart properties or libraries
            switch ($name) {
                case 'requestpath':
                    if (is_null($this->requestpath)) {
                        $this->requestpath = $this->url->get_requestpath($this->fullurl);
                    }

                    return $this->requestpath;
                default:
                    // load up a library
                    return $this->load->library($name);
            }
        }

        /**
         * Magic method to display error when the object is converted to string.
         **/
        public function __toString() {
            return "[zajlib object]";
        }

        /**
         * Magic method to display debug information.
         **/
        public function __toDebug() {
            return "[zajlib object]";
        }

        /**
         * Get the global object and return it statically.
         * @return zajLib Return me.
         **@todo Fix so that this is a static, not global.
         */
        public static function me() {
            return $GLOBALS['ofw'];
        }

        /**
         * Autoload method.
         */
        public static function autoload($class_name) {
            // If autoloading enabled or not (required to work with legacy codes such as Wordpress)
            if (!zajLib::me()->model_autoloading) {
                return;
            }
            // check if models enabled
            if (!zajLib::me()->ofwconf['mysql_enabled']) {
                zajLib::me()->error("Mysql support not enabled for this installation, so model $class_name could not be loaded!");
            }

            // load the model
            return zajLib::me()->load->model($class_name);
        }

    }

// Load additional classes
    require('zajdb.class.php');
    require('zajfield.class.php');
    require('zajlibextension.class.php');
    require('zajlibloader.class.php');
    require('zajvariable.class.php');
    require('ofwsafestring.class.php');

// Register autoloading mechanism
    spl_autoload_register('zajLib::autoload');