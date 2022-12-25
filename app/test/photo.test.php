<?php

    /**
     * A standard unit test for Outlast Framework automatic validation
     **/
    class OfwPhotoTest extends ofwTest {

        /** @var Photo $photo */
        var $photo;

        /**
         * Set up stuff.
         **/
        public function setUp() {
            // Disabled if mysql not enabled
            if (!$this->ofw->zajconf['mysql_enabled']) {
                return false;
            }
            // Create a photo object
            $this->photo = Photo::create();
        }

        /**
         * Check validation.
         */
        public function system_photo_basics() {
            // Disabled if mysql not enabled
            if (!$this->ofw->zajconf['mysql_enabled']) {
                return false;
            }
            // Disable errors
            $this->ofw->error->surpress_errors_during_test(true);
            /** Try to get master file path without any init **/
            if ($this->photo) {
                $this->photo->get_master_file_path();
                $err_txt = $this->ofw->error->get_last('error');
                $res = substr($err_txt, 0, strlen("Could not get photo file path."));
                ofwTestAssert::areIdentical("Could not get photo file path.", $res);
            }

            return true;
        }

        /**
         * Reset stuff, cleanup.
         **/
        public function tearDown() {
            // Disabled if mysql not enabled
            if (!$this->ofw->zajconf['mysql_enabled']) {
                return false;
            }
            // Delete all tests
            Photo::delete_tests();
        }

    }