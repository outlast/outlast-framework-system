<?php
/**
 * Field definition for a single category.
 * @package Fields
 * @subpackage BuiltinFields
 **/
 zajLib::me()->load->file('/fields/manytoone.field.php');

class zajfield_category extends zajfield_manytoone {
	// similar to manytoone

	// only editor is different
	const edit_template = 'field/category.field.html';  // string - the edit template, false if not used

	// Construct
	public function __construct($name, $options, $class_name, &$zajlib){
		$options = array('Category');
		return parent::__construct($name, $options, $class_name, $zajlib);
	}
}