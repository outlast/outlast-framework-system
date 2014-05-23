<?php
/**
 * Field definition for a color, stored in hex.
 * @package Fields
 * @subpackage BuiltinFields
 **/
 zajLib::me()->load->file('/fields/text.field.php');

class zajfield_color extends zajfield_text {
	// save as text, but with different editor

	// only editor is different
	const edit_template = 'field/color.field.html';  // string - the edit template, false if not used

}