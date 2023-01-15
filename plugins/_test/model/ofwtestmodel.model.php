<?php
    /**
     * A basic test model.
     * @package Model
     * @subpackage BuiltinModels
     **/

    /**
     * Class OfwTestModel
     * @property OfwTestData $data
     */
    class OfwTestModel extends zajModel {

        static function __model(stdClass $fields = new stdClass()) : stdClass {
            // Fake email for testing email validation
            $fields->email = zajDb::email();

            // Fake text to verify name caching
            $fields->text = zajDb::name();

            // Fake manytomany connection
            $fields->ofwtestanothers = zajDb::manytomany('OfwTestAnother');

            // end of custom fields definition
            /////////////////////////////////////////

            // do not modify the line below!
            return parent::__model($fields);
        }

        /**
         * A test for a standard public method.
         **/
        public function just_a_test() {
            return "just_a_test";
        }

        /**
         * A test for a standard public static method.
         **/
        public static function just_a_test_static() {
            return "just_a_test_static";
        }

        /**
         * Test only in parent.
         **/
        public function only_in_parent() {
            return "only_in_parent";
        }

        /**
         * Test only in parent.
         **/
        public static function only_in_parent_static() {
            return "only_in_parent_static";
        }
    }
