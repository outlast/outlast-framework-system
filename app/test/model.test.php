<?php
	/**
	 * A standard unit test for Outlast Framework models
	 * @todo Fix so that these tests do not fail if db is disabled.
	 **/
	class OfwModelTest extends zajTest {

		/* @var Photo $photo */
		public $photo;

		/**
		 * Set up stuff.
		 **/
		public function setUp(){
			// Disabled if mysql not enabled
				if(!$this->zajlib->zajconf['mysql_enabled']) return false;
			// Create a mock photo object
				$this->photo = Photo::create('mockid');
				$this->photo->set('name', 'mock!');
				$this->photo->set('field', 'mymockfield');
				$this->photo->save();
		}

		/**
		 * Verify that I could indeed save stuff
		 */
		public function system_verify_if_save_was_successful(){
			// Disabled if mysql not enabled
				if(!$this->zajlib->zajconf['mysql_enabled']) return false;
			// Fetch and test!
				$p = Photo::fetch('mockid');
				zajTestAssert::areIdentical('mock!', $p->name);
				zajTestAssert::areIdentical('mockid', $p->id);
		}


		/**
		 * Check the duplication feature
		 */
		public function system_check_duplication_feature(){
			// Disabled if mysql not enabled
				if(!$this->zajlib->zajconf['mysql_enabled']) return false;
			// Let's try to duplicate the Photo object
				$p = $this->photo->duplicate('mock2');
				$p->save();
			// Now verify if it has the same info
				$p = Photo::fetch('mock2');
				zajTestAssert::areIdentical('mock!', $p->name);
				zajTestAssert::areIdentical('mock2', $p->id);
				zajTestAssert::areNotIdentical($this->photo->id, $p->id);
			// The order number of mock2 should be greater than that of mockid
				zajTestAssert::areNotIdentical($this->photo->data->ordernum, $p->data->ordernum);
		}

		/**
		 * Let's test model extensions (and dynamic plugin loading)
		 */
		public function system_check_model_extending(){
			// Load up my _test plugin (if not already done)
				$load_test = $this->zajlib->plugin->load('_test', true, true);
				zajTestAssert::areIdentical('__plugin working!', $load_test);
			// Let's try some OfwTest action before it is extended!
				$result = OfwTest::just_a_test_static();
				zajTestAssert::areIdentical('just_a_test_static', $result);
				$ofwtest = OfwTest::create();
				$result = $ofwtest->just_a_test();
				zajTestAssert::areIdentical('just_a_test', $result);
			// Let's try some File action! But first run the autoload manually...
				// @todo When dynamically loading models, plugin/load should check to see if any overriding models are introduced - these need to load up
				include $this->zajlib->basepath.'system/plugins/_test/model/file.model.php';
				$result = File::just_a_test_static();
				zajTestAssert::areIdentical('just_a_test_static', $result);
				$filetest = File::create();
				$result = $filetest->just_a_test();
				zajTestAssert::areIdentical('just_a_test', $result);
			// Now dynamically extend OfwTest and see what happens
				OfwTestExt::extend('OfwTest');
				$result = OfwTest::only_in_ext_static();
			// Call stuff only in extension
				zajTestAssert::areIdentical('only_in_ext_static', $result);
				$result = OfwTest::just_a_test_static();
				zajTestAssert::areIdentical('just_a_test_static', $result);
				$ofwtest2 = OfwTest::create();
				$result = $ofwtest2->only_in_ext();
				zajTestAssert::areIdentical('only_in_ext', $result);
				$result = $ofwtest2->just_a_test();
				zajTestAssert::areIdentical('just_a_test_extended', $result);
			// Now call stuff that is NOT in extension but only in parent
				$result = OfwTest::only_in_parent_static();
				zajTestAssert::areIdentical('only_in_parent_static', $result);
				$result = $ofwtest2->only_in_parent();
				zajTestAssert::areIdentical('only_in_parent', $result);
		}

		/**
		 * Reset stuff, cleanup.
		 **/
		public function tearDown(){
			// Disabled if mysql not enabled
				if(!$this->zajlib->zajconf['mysql_enabled']) return false;
			// Remove permanently my mock photo
				$this->photo->delete(true);
			// Remove my mock2
				$m2 = Photo::fetch('mock2');
				if($m2->exists) $m2->delete(true);
		}

	}