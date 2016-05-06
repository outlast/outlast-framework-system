<?php
/**
 * A class for storing custom fields
 */
class CustomField extends zajModel{

	///////////////////////////////////////////////////////////////
	// !Model design
	///////////////////////////////////////////////////////////////
	public static function __model(){
		// define custom database fields
		$f = (object)array();
		$f->name = zajDb::name();
		$f->type = zajDb::select(array('text', 'number'));
		$f->featured = zajDb::boolean();
		$f->customfieldentries = zajDb::onetomany('CustomFieldEntry', 'customfield');

		// do not modify the line below!
		$f = parent::__model(__CLASS__, $f);
		return $f;
	}

	/**
	 * Construction and required methods
	 */
	public function __construct($id = ""){
		parent::__construct($id, __CLASS__);
		return true;
	}

	public static function __callStatic($name, $arguments){
		array_unshift($arguments, __CLASS__);
		return call_user_func_array(array('parent', $name), $arguments);
	}

	///////////////////////////////////////////////////////////////
	// !Custom methods
	///////////////////////////////////////////////////////////////

	public function __afterFetch(){
	}

	public static function __onSearch($fetcher){ return $fetcher; }
}