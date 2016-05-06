<?php
/**
 * A class for storing custom fields
 */
class CustomFieldEntry extends zajModel{

	///////////////////////////////////////////////////////////////
	// !Model design
	///////////////////////////////////////////////////////////////
	public static function __model(){
		// define custom database fields
		$f = (object)array();
		$f->customfield = zajDb::manytoone('CustomField');
		$f->value = zajDb::text();
		$f->class = zajDb::text();
		$f->parent = zajDb::text();
		$f->field = zajDb::text();

		// do not modify the line below!
		$f = parent::__model(__CLASS__, $f);
		return $f;
	}

	/**
	 * Construction and required methods
	 */
	public function __construct($id = ""){ parent::__construct($id, __CLASS__); return true; }
	public static function __callStatic($name, $arguments){ array_unshift($arguments, __CLASS__); return call_user_func_array(array('parent', $name), $arguments); }

	///////////////////////////////////////////////////////////////
	// !Custom methods
	///////////////////////////////////////////////////////////////

	public function __afterFetch(){
	}
}