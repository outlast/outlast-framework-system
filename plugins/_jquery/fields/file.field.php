<?php
/**
 * Field definition for photo collections.
 * @package Fields
 * @subpackage BuiltinFields
 **/
class zajfield_file extends zajField {
	// name, options - these are passed to constructor and available here!
	const in_database = false;		// boolean - true if this field is stored in database
	const use_validation = false;	// boolean - true if data should be validated before saving
	const use_get = true;			// boolean - true if preprocessing required before getting data
	const use_save = true;			// boolean - true if preprocessing required before saving data
	const use_duplicate = false;	// boolean - true if data should be duplicated when duplicate() is called
	const use_filter = false;		// boolean - true if fetcher needs to be modified
	const disable_export = false;	// boolean - true if you want this field to be excluded from exports
	const search_field = false;		// boolean - true if this field is used during search()
	const edit_template = 'field/file.field.html';	// string - the edit template, false if not used
	const show_template = false;	// string - used on displaying the data via the appropriate tag (n/a)

	// Construct
	public function __construct($name, $options, $class_name, &$zajlib){
		// load up config file
			zajLib::me()->config->load('system/fields.conf.ini', 'files');
		// set default options
			if(empty($options['max_file_size'])) $options['max_file_size'] = zajLib::me()->config->variable->field_files_max_file_size_default;
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
		return File::fetch()->filter('parent',$object->id)->filter('field', $this->name)->next();
	}

	/**
	 * Preprocess the data before saving to the database.
	 * @param mixed $data The first parameter is the input data.
	 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
	 * @return array Returns an array where the first parameter is the database update, the second is the object update
	 * @todo Fix where second parameter is actually taken into account! Or just remove it...
	 **/
	public function save($data, &$object){
		// if it is a string (form field input where it's the id), convert to an object first
			if(!empty($data) && is_string($data) || is_integer($data)){
				// Remove previous ones
				$photos = File::fetch()->filter('parent',$object->id)->filter('field', $this->name);
				foreach($photos as $pold){ $pold->delete(); }
				// Set new one
				$pobj = File::fetch($data);
				if(is_object($pobj)) $data = $pobj;
			}
		// if data is a file object
			if(is_object($data) && is_a($data, 'File')){
				/** @var File $data **/
				// Check to see if already has parent (disable hijacking of photos)
					if($data->data->parent) return $this->zajlib->warning("Cannot set parent of a file object that already has a parent!");
				// Remove previous ones
					$file = File::fetch()->filter('parent', $object->id)->filter('field', $this->name);
					foreach($file as $pold){ $pold->delete(); }
				// Set new one

				// check to see if already has parent (disable hijacking of photos)
					if($data->data->parent) return $this->zajlib->warning("Cannot set parent of a file object that already has a parent!");
				// now set parent
					$data->set('class', $object->class_name);
					$data->set('parent', $object->id);
					$data->set('field', $this->name);
					$data->upload();
				return array(false, false);
			}
		return array(false, false);
	}

}