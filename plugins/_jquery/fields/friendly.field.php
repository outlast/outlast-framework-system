<?php
/**
 * Field definition for dates.
 * @package Fields
 * @subpackage BuiltinFields
 **/
 zajLib::me()->load->file('/fields/text.field.php');

class zajfield_friendly extends zajfield_text {
	// similar to text
	const edit_template = 'field/friendly.field.html';	// string - the edit template, false if not used

	/**
	 * Check to see if input data is valid.
	 * @param mixed $input The input data.
	 * @return boolean Returns true if validation was successful, false otherwise.
	 **/
	public function validation($input){
		// make sure there are no duplicates!
		return true;
	}

}