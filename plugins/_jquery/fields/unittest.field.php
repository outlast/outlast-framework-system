<?php
/**
 * Field definition for the built-in unittest fields.
 * @package Fields
 * @subpackage BuiltinFields
 **/
 zajLib::me()->load->file('/fields/boolean.field.php');

class zajfield_unittest extends zajfield_boolean {
	// similar to boolean

	// only editor is not used
	const edit_template = false;    // string - the edit template, false if not used
	const disable_export = true;	// boolean - true if you want this field to be excluded from exports
	// ...and it should not be duplicated
	const use_duplicate = false;    // boolean - true if data should be duplicated when duplicate() is called

	// Construct
	public function __construct($name, $options, $class_name, &$zajlib){
		$options = array(false);
		return parent::__construct($name, $options, $class_name, $zajlib);
	}
}