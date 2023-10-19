<?php
    /**
     * Helper library for creating unit tests.
     * @author Aron Budinszky <aron@outlast.hu>
     * @version 3.0
     * @package Library
     **/

// Include Enhance tester class
    include_once(zajLib::me()->basepath.'system/class/zajtest.class.php');

    class ofw_test extends zajLibExtension {

        private static $paths = [            // array - array of paths to search, order does not matter here!
            'local'       => 'app/test/',
            'plugin_apps' => true,
            // boolean - set this to false if you don't want to check for app plugin views
            'system'      => 'system/app/test/',
            'system_apps' => true,
            // boolean - when true, system apps will be loaded (don't change this unless you know what you're doing!)
        ];
        private $filecount = 0;                    // integer - the number of files loaded up

        private $is_running = false;            // boolean - this is true when the test is running

        private $notices = [];                // array - an array of notices

        /**
         * Check if test is running.
         **/
        public function is_running() {
            return $this->is_running;
        }

        /**
         * Prepare a specific test for running by including it.
         * @param string $file The include path is relative to basepath.
         * @param string $only_extension Only include files with this extension.
         * @return int Returns the current file count.
         */
        public function prepare($file, $only_extension = 'php') {
            // Verify that the file is sandboxed within the project
            $file = $this->ofw->file->file_check($file);
            // Now include it (if it is PHP)!
            if ($only_extension !== false && $this->ofw->file->get_extension($file) != $only_extension) {
                return $this->filecount;
            }
            // Otherwise, go ahead and include!
            include($this->ofw->basepath.$file);

            // Add one to filecount
            return ++$this->filecount;
        }

        /**
         * Prepare all tests for running.
         **/
        public function prepare_all() {
            // collect all the path
            $allpaths = [];
            foreach (ofw_test::$paths as $type => $path) {
                // if type is plugin_apps, then it is special!
                if ($type == 'plugin_apps' && $path) {
                    // run through all of my registered plugin apps' views and return if one found!
                    foreach (zajLib::me()->loaded_plugins as $plugin_app) {
                        $path = zajLib::me()->basepath.'plugins/'.$plugin_app.'/test/';
                        if (file_exists($path)) {
                            $allpaths[] = $path;
                        }
                    }
                } else if ($type == 'system_apps' && $path) {
                    // run through all of my registered system apps' views and return if one found!
                    foreach (zajLib::me()->zajconf['system_apps'] as $plugin_app) {
                        $path = zajLib::me()->basepath.'system/plugins/'.$plugin_app.'/test/';
                        if (file_exists($path)) {
                            $allpaths[] = $path;
                        }
                    }
                } else {
                    $path = zajLib::me()->basepath.$path;
                    if (file_exists($path)) {
                        $allpaths[] = $path;
                    }
                }
            }
            // Now get all files in each path
            foreach ($allpaths as $path) {
                foreach ($this->ofw->file->get_files_in_dir($path) as $file) {
                    $file = str_ireplace($this->ofw->basepath, '', $file);
                    $this->prepare($file);
                }
            }
        }

        /**
         * Run the tests and return the count.
         * @return integer The number of tests run including successful and unsuccessful ones.
         **/
        public function run() {
            // Set is_running to true
            $this->is_running = true;
            // Get the EnhanceTestFramework object
            $this->ofw->variable->test = \Enhance\Core::runTests("MOZAJIK");
            $this->ofw->variable->test->filecount = $this->filecount;
            $this->ofw->variable->test->testcount = count($this->ofw->variable->test->Results) + count($this->ofw->variable->test->Errors);
            $this->ofw->variable->testnotices = $this->notices;

            // Return to originator!
            return $this->ofw->variable->test->testcount;
        }

        /**
         * You can send notices which will be shown on the test run page.
         */
        public function notice($string) {
            $this->notices[] = $string;
        }

    }

    /**
     * Provide non-namespaced class name for unit testing.
     * @property zajLib $ofw A pointer to the global ofw object.
     **/
    class ofwTest extends \Enhance\TestFixture {
        public $ofw;
    }


    /**
     * @deprecated
     */
    class zajTest extends \Enhance\TestFixture {
        public $ofw;

        /**
         * @var zajLib
         * @deprecated
         */
        public $zajlib;

    }

    /**
     * Class ofwTestAssert
     */
    class ofwTestAssert extends \Enhance\Assert {
    }

    /**
     * @deprecated
     */
    class zajTestAssert extends ofwTestAssert {
    }
