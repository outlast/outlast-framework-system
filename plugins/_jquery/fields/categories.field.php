<?php
/**
 * Field definition for a single category.
 * @package Fields
 * @subpackage BuiltinFields
 **/
 zajLib::me()->load->file('/fields/manytomany.field.php');

class zajfield_categories extends zajfield_manytomany {
	// similar to manytoone

	// only editor is different
	const edit_template = 'field/categories.field.html';  // string - the edit template, false if not used

	// Construct
	public function __construct($name, $options, $class_name, &$zajlib){
		$options = array('Category');
		return parent::__construct($name, $options, $class_name, $zajlib);
	}
}