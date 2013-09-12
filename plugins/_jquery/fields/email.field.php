<?php
/**
 * Field definition for dates.
 * @package Fields
 * @subpackage BuiltinFields
 **/
 zajLib::me()->load->file('/fields/text.field.php');

class zajfield_email extends zajfield_text {
	// similar to text, validation and editor differs
	const edit_template = 'field/email.field.html';	// string - the edit template, false if not used

	/**
	 * Check to see if input data is valid.
	 * @param mixed $input The input data.
	 * @return boolean Returns true if validation was successful, false otherwise.
	 **/
	public function validation($input){
		// Check to see if email is good
			return zajLib::me()->email->valid($input);
	}
}