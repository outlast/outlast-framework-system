<?php
/**
 * Field definition for photo collections.
 * @package Fields
 * @subpackage BuiltinFields
 **/
class zajfield_custom extends zajField {
	// name, options - these are passed to constructor and available here!
	const in_database = false;		// boolean - true if this field is stored in database
	const use_validation = false;	// boolean - true if data should be validated before saving
	const use_get = true;			// boolean - true if preprocessing required before getting data
	const use_save = true;			// boolean - true if preprocessing required before saving data
	const use_duplicate = false;	// boolean - true if data should be duplicated when duplicate() is called
	const use_filter = false;		// boolean - true if fetcher needs to be modified
	const disable_export = false;	// boolean - true if you want this field to be excluded from exports
	const search_field = false;		// boolean - true if this field is used during search()
	const edit_template = 'field/custom.field.html';	// string - the edit template, false if not used
	const show_template = false;	// string - used on displaying the data via the appropriate tag (n/a)

	// Construct
	public function __construct($name, $options, $class_name, &$zajlib){

		// call parent constructor
		parent::__construct(__CLASS__, $name, $options, $class_name, $zajlib);
	}

	/**
	 * Check to see if input data is valid.
	 * @param mixed $input The input data.
	 * @return boolean Returns true if validation was successful, false otherwise.
	 **/
	public function validation($input){
		return true;
	}

	/**
	 * Preprocess the data before returning the data from the database.
	 * @param mixed $data The first parameter is the input data.
	 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
	 * @return mixed Return the data that should be in the variable.
	 **/
	public function get($data, &$object){
		return CustomFieldEntry::fetch()->filter('parent',$object->id)->filter('class', $object->class_name)->filter('field', $this->name)->next();
	}

	/**
	 * Preprocess the data before saving to the database.
	 * @param mixed $data The first parameter is the input data.
	 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
	 * @return array Returns an array where the first parameter is the database update, the second is the object update
	 * @todo Fix where second parameter is actually taken into account! Or just remove it...
	 **/
	public function save($data, &$object){
		// it's always a JSON
		$data = json_decode($data);
		if(!empty($data)){
			if(!empty($data->add)){
				foreach($data->add as $additem){
					$cf = CustomFieldEntry::create();
					$cf->set('customfield', $additem->customfield);
					$cf->set('value', $additem->value);
					$cf->set('parent', $object->id);
					$cf->set('class', $object->class_name);
					$cf->set('field', $this->name);
					$cf->save();
				}
			}
			if(!empty($data->update)){
				foreach($data->update as $additem){
					$cf = CustomFieldEntry::fetch($additem->id);
					$cf->set('customfield', $additem->customfield);
					$cf->set('value', $additem->value);
					$cf->set('parent', $object->id);
					$cf->set('class', $object->class_name);
					$cf->set('field', $this->name);
					$cf->save();
				}
			}
			if(!empty($data->remove)){
				foreach($data->remove as $removeitem){
					$cf = CustomFieldEntry::fetch($removeitem->id);
					$cf->delete();
				}
			}
		}
	}

	/**
	 * This method is called just before the input field is generated. Here you can set specific variables and such that are needed by the field's GUI control.
	 * @param array $param_array The array of parameters passed by the input field tag. This is the same as for tag definitions.
	 * @param zajCompileSource $source This is a pointer to the source file object which contains this tag.
	 * @return bool
	 */
	public function __onInputGeneration($param_array, &$source){
		// write to compile destination
		$this->zajlib->compile->write('<?php $this->zajlib->variable->field->customfields = CustomField::__onSearch(CustomField::fetch()); if($this->zajlib->variable->field->customfields === false) $this->zajlib->warning("__onSearch method required for CustomField for this input."); ?>');
		return true;
	}

}