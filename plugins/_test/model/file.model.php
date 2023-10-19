<?php
/**
 * A test model that extends the File model
 * @package Model
 * @subpackage BuiltinModels
 **/

class FileTest extends zajModelExtender {

    static function __model(stdClass $fields = new stdClass()) : stdClass {

		// Don't test model fields yet

		// end of custom fields definition
		/////////////////////////////////////////

		// do not modify the line below!
        return parent::__model($fields);
	}

	/**
	 * A test for a standard public method.
	 **/
	public function just_a_test(){
		return "just_a_test";
	}

	/**
	 * A test for a standard public static method.
	 **/
	public static function just_a_test_static(){
		return "just_a_test_static";
	}
}

// Extend my file
FileTest::extend('File');
