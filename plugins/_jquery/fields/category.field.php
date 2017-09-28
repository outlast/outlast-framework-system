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
		$options = ['Category'];
		return parent::__construct($name, $options, $class_name, $zajlib);
	}

	/**
	 * This method is called just before the input field is generated. Here you can set specific variables and such that are needed by the field's GUI control.
	 * @param array $param_array The array of parameters passed by the input field tag. This is the same as for tag definitions.
	 * @param zajCompileSource $source This is a pointer to the source file object which contains this tag.
	 * @return bool
	 **/
	public function __onInputGeneration($param_array, &$source){
		// override to print all choices
			// use search method with all
				$class_name = $this->options['model'];
			// write to compile destination
				$this->zajlib->compile->write('<?php $this->zajlib->variable->field->choices = '.$class_name.'::__onSearch('.$class_name.'::fetch()); $this->zajlib->variable->field->choices_toplevel = '.$class_name.'::__onSearch('.$class_name.'::fetch_top_level()); if($this->zajlib->variable->field->choices === false) $this->zajlib->warning("__onSearch method required for '.$class_name.' for this input."); ?>');
		return true;
	}
	
}