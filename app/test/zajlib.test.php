<?php

    /**
     * A standard unit test for Outlast Framework system libraries.
     **/
    class OfwZajlibTest extends ofwTest {

        private $server_original;
        private $ofw_original;

        /**
         * Set up stuff.
         **/
        public function setUp() {
            // Save
            $this->server_original = $_SERVER;
            $this->ofw_original = $this->ofw;
        }

        /**
         * Zajconf bw compatibility
         */
        public function zajconf_compatibility() {
            // Check settings
            ofwTestAssert::isObject($this->ofw->zajconf);
            ofwTestAssert::isObject($this->ofw->ofwconf);
            ofwTestAssert::areIdentical($this->ofw->zajconf['locale_available'],
                $this->ofw->ofwconf['locale_available']);

            // Check object access
            ofwTestAssert::areIdentical($this->ofw->zajconf['locale_available'], $this->ofw->ofwconf->locale_available);

            // Modify zajconf
            $locales_available = $this->ofw->ofwconf->locale_available;
            $this->ofw->zajconf['locale_available'] = 'test1';
            ofwTestAssert::areIdentical('test1', $this->ofw->ofwconf['locale_available']);
            ofwTestAssert::areIdentical('test1', $this->ofw->ofwconf->locale_available);
            ofwTestAssert::areIdentical('test1', $this->ofw->zajconf['locale_available']);
            $this->ofw->ofwconf->locale_available = $locales_available;
            ofwTestAssert::areIdentical($locales_available, $this->ofw->ofwconf->locale_available);

            // Modify ofwconf and check if it is accessible via zajconf
            $this->ofw->ofwconf->locale_available = 'test2';
            ofwTestAssert::areIdentical('test2', $this->ofw->ofwconf['locale_available']);
            ofwTestAssert::areIdentical('test2', $this->ofw->ofwconf->locale_available);
            ofwTestAssert::areIdentical('test2', $this->ofw->zajconf['locale_available']);
            $this->ofw->ofwconf->locale_available = $locales_available;
            ofwTestAssert::areIdentical($locales_available, $this->ofw->ofwconf->locale_available);

        }


        /**
         * Check ofw baseurl detection.
         */
        public function baseurl_detection() {
            // Set some fake info
            $_SERVER['OFW_BASEURL'] = 'http://www.example.com/';
            $_SERVER['HTTPS'] = 'off';
            // Create
            $z = new zajLib('/', $this->ofw->ofwconf);
            ofwTestAssert::areIdentical('http:', $z->protocol);
            ofwTestAssert::areIdentical('example.com', $z->domain);
            ofwTestAssert::areIdentical('', $z->subdomain);
            ofwTestAssert::areIdentical('//www.example.com/', $z->baseurl);
            //@todo find a solution to check fullurl
            //ofwTestAssert::areIdentical('//www.example.com/update/test/', $z->fullurl);

            // Set some fake info again
            $_SERVER['OFW_BASEURL'] = 'https://test.example.com/asdf/';
            $_SERVER['HTTPS'] = 'on';
            // Create
            $z = new zajLib('/', $this->ofw->ofwconf);
            ofwTestAssert::areIdentical('https:', $z->protocol);
            ofwTestAssert::areIdentical('example.com', $z->domain);
            ofwTestAssert::areIdentical('test', $z->subdomain);
            ofwTestAssert::areIdentical('//test.example.com/asdf/', $z->baseurl);
            //ofwTestAssert::areIdentical('//test.example.com/asdf/update/test/', $z->fullurl);
        }

        /**
         * Check load methods.
         */
        public function load_libs_and_models() {
            // Try to load a non-existant library with fail as false
            $result = $this->ofw->load->library('this_library_will_not_exist', false, false);
            ofwTestAssert::isFalse($result);

            // Try to load a non-existant library with fail as true (default)
            $this->ofw->error->surpress_errors_during_test(true);
            $result = $this->ofw->load->library('this_library_will_not_exist');
            $last_error = $this->ofw->error->get_last('error');
            ofwTestAssert::areIdentical("Tried to auto-load library (this_library_will_not_exist), but failed: library file not found!",
                $last_error);
            $this->ofw->error->surpress_errors_during_test(false);
            ofwTestAssert::isFalse($result);


            // Try to load an existing library
            $result = $this->ofw->load->library('text');
            ofwTestAssert::isObject($result);

            // Try to load a non-existant model
            $result = $this->ofw->load->model('this_model_will_not_exist', false, false);
            ofwTestAssert::isFalse($result);
        }

        /**
         * Reset stuff, cleanup.
         **/
        public function tearDown() {
            // Restore
            $_SERVER = $this->server_original;
            $this->ofw = $this->ofw_original;

        }
    }