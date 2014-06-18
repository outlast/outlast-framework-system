<?php
	/**
	 * A standard unit test for Outlast Framework automatic validation
	 **/
	class OfwPhotoTest extends zajTest {

		/** @var Photo $photo */
		var $photo;

		/**
		 * Set up stuff.
		 **/
		public function setUp(){
			// Create a photo object
				$this->photo = Photo::create();
		}

		/**
		 * Check validation.
		 */
		public function photo_basics(){
				// Disable errors
					$this->zajlib->error->surpress_errors_during_test(true);
				/** Try to get master file path without any init **/
					$this->photo->get_master_file_path();
					$err_txt = $this->zajlib->error->get_last('error');
					$res = substr($err_txt, 0, strlen("Could not get photo file path."));
					zajTestAssert::areIdentical("Could not get photo file path.", $res);
			return true;
		}

		/**
		 * Reset stuff, cleanup.
		 **/
		public function tearDown(){
			// Delete all tests
				Photo::delete_tests();
		}

	}