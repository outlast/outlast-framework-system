<?php
/**
 * A test model that extends the File model
 * @package Model
 * @subpackage BuiltinModels
 **/

class FileTest extends zajModelExtender {

	/**
	 * __model function. extends the global User database fields available for objects of this class.
	 *
	 */
	static function __model(){
		/////////////////////////////////////////
		// begin custom fields definition:
			$f = (object) array();

			// Don't test model fields yet

		// end of custom fields definition
		/////////////////////////////////////////
		// do not modify the line below!
		$f = parent::__model(__CLASS__, $f);
		return $f;
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
