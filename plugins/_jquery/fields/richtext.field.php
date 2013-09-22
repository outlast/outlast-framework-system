<?php
/**
 * Field definition richtext areas. This is basically an alias of textarea, but with a different control associated with it.
 * @package Fields
 * @subpackage BuiltinFields
 **/
zajLib::me()->load->file('/fields/textarea.field.php');

class zajfield_richtext extends zajfield_textarea {
	const edit_template = 'field/richtext.field.html';	// string - the edit template, false if not used
	const show_template = false;						// string - used on displaying the data via the appropriate tag (n/a)

	// alias of textarea, except save and editor

	/**
	 * Preprocess the data before saving to the database. Remove unnecessary tags.
	 * @param $data string The first parameter is the input data.
	 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
	 * @return array Returns an array where the first parameter is the database update, the second is the object update
	 **/
	public function save($data, &$object){
		$data = strip_tags($data, '<p><br><a><b><i><u><strong><em><div>');
		return $data;
	}

}