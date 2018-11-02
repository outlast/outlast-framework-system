<?php

    /**
     * A standard unit test for Outlast Framework models
     * @todo Fix so that these tests do not fail if db is disabled.
     **/
    class OfwModelTest extends ofwTest {

        /* @var Category $category */
        public $category;

        /**
         * Set up stuff.
         **/
        public function setUp() {
            // Disabled if mysql not enabled
            if (!$this->ofw->ofwconf['mysql_enabled']) {
                return false;
            }
            // Create a mock category object
            $this->category = Category::create('mockid');
            $this->category->set('name', 'mock!');
            $this->category->set('description', 'mymockdesc');
            $this->category->save();
            return true;
        }

        /**
         * Make sure data is properly set/get in models.
         */
        public function system_verify_data_setter_getter() {
            // Create with email
            $c2 = Category::create();
            $c2->set('name', 'let us try!');

            // Modified data should be available after reload
            ofwTestAssert::areIdentical('let us try!', $c2->data->get_modified('name'));
            $c2->data->reload();
            ofwTestAssert::areIdentical('let us try!', $c2->data->get_modified('name'));

            // ...but not after save
            $c2->save();
            ofwTestAssert::isNull($c2->data->get_modified('name'));
        }


        /**
         * Verify that I could indeed save stuff
         */
        public function system_verify_if_save_was_successful() {
            // Fetch and test!
            $cat = Category::fetch('mockid');
            ofwTestAssert::areIdentical('mock!', $cat->name);
            ofwTestAssert::areIdentical('mockid', $cat->id);
        }


        /**
         * Check the duplication feature
         */
        public function system_check_duplication_feature() {
            // Let's try to duplicate the Category object
            $cat = $this->category->duplicate('mock2');
            $cat->save();
            // Now verify if it has the same info
            $cat = Category::fetch('mock2');
            ofwTestAssert::areIdentical('mock!', $cat->name);
            ofwTestAssert::areIdentical('mock2', $cat->id);
            ofwTestAssert::areNotIdentical($this->category->id, $cat->id);
            // The order number of mock2 should be greater than that of mockid
            ofwTestAssert::areNotIdentical($this->category->data->ordernum, $cat->data->ordernum);
        }

        /**
         * Let's test model extensions (and dynamic plugin loading)
         */
        public function system_check_model_extending() {
            // Load up my _test plugin (if not already done)
            $load_test = $this->ofw->plugin->load('_test', true, true);
            ofwTestAssert::areIdentical('__plugin working!', $load_test);
            // Let's try some OfwTest action before it is extended!
            $result = OfwTestModel::just_a_test_static();
            ofwTestAssert::areIdentical('just_a_test_static', $result);
            $ofwtest = OfwTestModel::create();
            $result = $ofwtest->just_a_test();
            ofwTestAssert::areIdentical('just_a_test', $result);
            // Let's try some File action! But first run the autoload manually...
            // @todo When dynamically loading models, plugin/load should check to see if any overriding models are introduced - these need to load up
            include $this->ofw->basepath.'system/plugins/_test/model/file.model.php';
            $result = File::just_a_test_static();
            ofwTestAssert::areIdentical('just_a_test_static', $result);
            $filetest = File::create();
            $result = $filetest->just_a_test();
            ofwTestAssert::areIdentical('just_a_test', $result);
            // Now dynamically extend OfwTest and see what happens
            OfwTestExt::extend('OfwTestModel');
            $result = OfwTestModel::only_in_ext_static();
            // Call stuff only in extension
            ofwTestAssert::areIdentical('only_in_ext_static', $result);
            $result = OfwTestModel::just_a_test_static();
            ofwTestAssert::areIdentical('just_a_test_static', $result);
            $ofwtest2 = OfwTestModel::create();
            $result = $ofwtest2->only_in_ext();
            ofwTestAssert::areIdentical('only_in_ext', $result);
            $result = $ofwtest2->just_a_test();
            ofwTestAssert::areIdentical('just_a_test_extended', $result);
            // Now call stuff that is NOT in extension but only in parent
            $result = OfwTestModel::only_in_parent_static();
            ofwTestAssert::areIdentical('only_in_parent_static', $result);
            $result = $ofwtest2->only_in_parent();
            ofwTestAssert::areIdentical('only_in_parent', $result);
        }

        /**
         * Reset stuff, cleanup.
         **/
        public function tearDown() {
            // Remove all of my tests
            Category::delete_tests();

            return true;
        }

    }