<?php
/**
 * A basic test model.
 * @package Model
 * @subpackage BuiltinModels
 **/

class OfwTest extends zajModel {

	/**
	 * __model function. extends the global User database fields available for objects of this class.
	 *
	 */
	static function __model(){
		/////////////////////////////////////////
		// begin custom fields definition:
			$f = (object) array();

			// Fake email for testing email validation
			$f->email = zajDb::email();

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

	/**
	 * Test only in parent.
	 **/
	public function only_in_parent(){
		return "only_in_parent";
	}

	/**
	 * Test only in parent.
	 **/
	public static function only_in_parent_static(){
		return "only_in_parent_static";
	}
}
