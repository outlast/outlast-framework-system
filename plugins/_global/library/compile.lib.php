<?php
    /**
     * Backend compile-related classes.
     * @author Aron Budinszky <aron@outlast.hu>
     * @version 3.0
     * @package Template
     * @subpackage CompilingBackend
     * @todo Add a parameter to disable php tags. Test if php can even be used in templates.
     */

    require(zajLib::me()->basepath.'system/class/zajcompilesession.class.php');


    /**
     * This library will execute the compilation of a specific template file. This does not need to be loaded manually since it will automatically load when needed (via the template libary).
     **/
    class zajlib_compile extends zajLibExtension {
        /**
         * A {@link zajElementsLoader} object that loads tags and directs the execution to the correct tag processing method.
         **/
        public $tags;

        /**
         * A {@link zajElementsLoader} object that loads filters and directs the execution to the correct filter processing method.
         **/
        public $filters;

        /**
         * An array of {@link zajCompileSession} objects. When compiling a hierarchy of files, many sessions are needed to handle template inheritance correctly.
         **/
        public $sessions = [];

        /**
         * Creates a new compile session.
         **/
        function __construct(&$zajlib, $system_library) {
            // run parent contructor
            parent::__construct($zajlib, $system_library);
            // create tags object
            $this->tags = new zajElementsLoader($zajlib, 'tag');
            $this->filters = new zajElementsLoader($zajlib, 'filter');
            // register base tags & filters
            $this->register_tags('base');
            $this->register_tags('ofw');
            $this->register_filters('base');
            $this->register_filters('ofw');
        }

        /**
         * This will initiate a compile session for a source file.
         * @param string $source_path This is the source file's path relative to any of the active view folders.
         * @param bool|string $destination_path This is the destination file's path relative to the final compiled view folder. If not specified, the destination will be the same as the source (relative), which is the preferred way of doing things. You should only specify this if you are customizing the template compilation process.
         * @return boolean Will return false, but that does not mean it was a failure! Failures are either fatal or displayed inline.
         */
        public function compile($source_path, $destination_path = false) {
            // start a new compile session
            $new_session = new zajCompileSession($source_path, $this->zajlib, $destination_path);
            array_push($this->sessions, $new_session);

            // now go!
            return $this->go();
        }

        /**
         * Returns true if a template file exists anywhere in the available paths based on the source path.
         * @param string $source_path The source path to check for.
         * @return boolean Returns true if found, false if not.
         */
        public function source_exists($source_path) {
            $result = zajCompileSource::check_app_levels($source_path);
            if ($result === false) {
                return false;
            } else {
                return true;
            }
        }

        /**
         * This will start the compiling session and recursively continue the process until it is finished with all related files.
         * @return boolean Will return false, but that does not mean it was a failure! Failures are either fatal or displayed inline.
         **/
        private function go() {
            // get the latest session
            $current_session = end($this->sessions);
            // start compiling this session
            $this->zajlib->compile_started = true;
            $ended = $current_session->compile();
            // did it end?
            if (!$ended) {
                return false;
            }
            // remove it
            array_pop($this->sessions);
            // do i still have any compiling to do?
            if (count($this->sessions) > 0) {
                return $this->go();
            }

            return false;
        }


        /**
         * Registering the tags will make the list of tags in the specified tag file available for use.
         * @param string $name The name of the tags collections ($name.tags.php in plugins)
         **/
        public function register_tags($name) {
            // register in tags object
            $this->tags->register($name);
        }

        /**
         * Registering the filters will make the list of filters in the specified filters file available for use.
         * @param string $name The name of the filters collections ($name.filters.php in plugins)
         **/
        public function register_filters($name) {
            // register in filters object
            $this->filters->register($name);
        }

        /**
         * This method redirects method calls to the current active compilation session.
         **/
        public function __call($name, $args) {
            $session = end($this->sessions);
            if (!is_object($session)) {
                $this->zajlib->error("Session ended prematurely. Did you forget to close/end a block tag?");
            }
            if ($name == "get_session_id") {
                return $session->id;
            } else {
                return call_user_func_array([&$session, $name], $args);
            }
        }

    }

    /**
     * Loads the appropriate tag or filter method collection and then directs requests to the right method...
     **/
    class zajElementsLoader {
        protected $zajlib;
        protected $elements = [];
        protected $element_type = 'tag';
        protected $collections_loaded = false;

        public function __construct(&$zajlib, $element_type) {
            $this->zajlib =& $zajlib;
            $this->element_type = $element_type;
        }

        // request a new collection
        public function register($name) {
            // TODO: is there a better way to do this than reverse twice...there must be!
            // reverse so that it new elements are at front
            $this->elements = array_reverse($this->elements);
            // register this element - load it when called!
            $this->elements[$name] = false;
            // rereverse so that it new elements are at front
            $this->elements = array_reverse($this->elements);
            // since we've added a new one, collections are not loaded!
            $this->collections_loaded = false;
        }

        // loads any requested but not yet loaded collections
        private function load() {
            // do we need to load any collections?
            if (!$this->collections_loaded) {
                // search for any requested collections that haven't been loaded
                foreach ($this->elements as $element => $has_been_loaded) {
                    // if it hasn't been loaded, do so now!
                    if (!$has_been_loaded) {
                        // load the file
                        $this->zajlib->load->file('/'.$this->element_type.'s/'.$element.'.'.$this->element_type.'s.php');
                        // create the class
                        $class_name = 'zajlib_'.$this->element_type.'_'.$element;
                        $this->elements[$element] = new $class_name($this->zajlib);
                    }
                }
                // done. set loaded to true!
                $this->collections_loaded = true;
            }

            return true;
        }

        // handle any element calls
        // TODO: remove call by reference in call_user_func_array()
        public function __call($name, $arguments) {
            // do we need to load any collections?
            $this->load();
            // generate element method name
            $element_method = $this->element_type.'_'.$name;
            // search for $name among all registered tags
            foreach ($this->elements as $element_class_name => $element_object) {
                // does this tag exist in this collection?
                if (method_exists($element_object, $element_method)) {
                    // call the method in the apprpriate tags.php/filters.php file
                    // must use & here on second arg, because otherwise argument is not passed by reference!
                    if (empty($arguments[2])) {
                        $arguments[2] = 1;
                    }
                    $return = call_user_func_array([$element_object, $element_method],
                        [
                            $arguments[0],
                            &$arguments[1],
                            $arguments[2],
                            array_key_exists(3, $arguments) ? $arguments[3] : null
                        ]);

                    // check if return value is valid, if not just return the unmodified $debug_stats
                    return $return;
                }
            }
            // The filter/tag does not exist
            $this->zajlib->compile->get_current_source()->warning("$this->element_type name '$name' cannot be found!",
                $arguments[2]);

            return $arguments[2];
        }

        /**
         * This is a special method to retrieve variables instead of calling tags via tag getter methods.
         * Tag getter methods can be declared in zajElementCollection classes. See {@link tag_get_extend()} as an example.
         **/
        public function __get($name) {
            // do we need to load any collections?
            $this->load();
            // generate element method name
            $element_method = $this->element_type.'_get_'.$name;
            // search for $name among all registered tags
            foreach ($this->elements as $element_class_name => $element_object) {
                // does this tag getter method exist in this collection?
                if (method_exists($element_object, $element_method)) {
                    // call the method in the apprpriate tags.php/filters.php file and return
                    return call_user_func_array([$element_object, $element_method], []);
                }
            }

            // The filter/tag does not exist but no error thrown
            return $this->zajlib->compile->get_current_source()->warning("$this->element_type getter method for '$name' cannot be found!");
        }

    }

    /**
     * Abstract parent class from which all filter and tag extensions originate.
     **/
    abstract class zajElementCollection {
        /** @var zajLib $zajlib */
        protected $zajlib;

        public function __construct(&$zajlib) {
            $this->zajlib =& $zajlib;
        }
    }