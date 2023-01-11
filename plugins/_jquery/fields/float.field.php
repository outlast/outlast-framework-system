<?php
/**
 * Field definition for a number which is a float.
 * @package Fields
 * @subpackage BuiltinFields
 **/
class zajfield_float extends zajField {
	// name, options - these are passed to constructor and available here!
	const in_database = true;		// boolean - true if this field is stored in database		
	const use_validation = false;	// boolean - true if data should be validated before saving
	const use_get = false;			// boolean - true if preprocessing required before getting data
	const use_save = false;			// boolean - true if preprocessing required before saving data
	const use_duplicate = true;		// boolean - true if data should be duplicated when duplicate() is called
	const use_filter = false;		// boolean - true if fetch is modified
	const disable_export = false;	// boolean - true if you want this field to be excluded from exports
	const search_field = false;		// boolean - true if this field is used during search()
	const edit_template = 'field/float.field.html';	// string - the edit template, false if not used
	const show_template = false;	// string - used on displaying the data via the appropriate tag (n/a)
		
	// Construct
	public function __construct($name, $options, $class_name){
		// set default options
			// no default options
		// call parent constructor
			parent::__construct(__CLASS__, $name, $options, $class_name);
	}	
	
	/**
	 * Defines the structure and type of this field in the mysql database.
	 * @return array Returns in array with the database definition.
	 **/
    public function database() : array {
		// define each field
			$fields[$this->name] = array(
					'field' => $this->name,
					'type' => 'float',
					'option' => array(),
 					'key' => '',
					'default' => 0,
					'extra' => '',
					'comment' => 'float',
			);
		return $fields;
	}

}