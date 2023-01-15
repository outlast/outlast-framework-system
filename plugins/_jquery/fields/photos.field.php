<?php
/**
 * Field definition for photo collections.
 * @package Fields
 * @subpackage BuiltinFields
 **/
class zajfield_photos extends zajField {
	// name, options - these are passed to constructor and available here!
	const in_database = false;		// boolean - true if this field is stored in database		
	const use_validation = false;	// boolean - true if data should be validated before saving
	const use_get = true;			// boolean - true if preprocessing required before getting data
	const use_save = true;			// boolean - true if preprocessing required before saving data
	const use_duplicate = false;	// boolean - true if data should be duplicated when duplicate() is called
	const use_filter = false;		// boolean - true if fetcher needs to be modified
	const disable_export = true;	// boolean - true if you want this field to be excluded from exports
	const search_field = false;		// boolean - true if this field is used during search()
	const edit_template = 'field/photos.field.html';	// string - the edit template, false if not used
	const show_template = false;	// string - used on displaying the data via the appropriate tag (n/a)
		
	// Construct
	public function __construct($name, $options, $class_name){
		// load up config file
			zajLib::me()->config->load('system/fields.conf.ini', 'photos');
		// set default options
			if(empty($options['min_height'])) $options['min_height'] = zajLib::me()->config->variable->field_photos_min_height_default;
			if(empty($options['min_width'])) $options['min_width'] = zajLib::me()->config->variable->field_photos_min_width_default;
			if(empty($options['max_height'])) $options['max_height'] = zajLib::me()->config->variable->field_photos_max_height_default;
			if(empty($options['max_width'])) $options['max_width'] = zajLib::me()->config->variable->field_photos_max_width_default;
			if(empty($options['max_file_size'])) $options['max_file_size'] = zajLib::me()->config->variable->field_photos_max_file_size_default;
		// call parent constructor
			parent::__construct(__CLASS__, $name, $options, $class_name);
	}	

	/**
	 * Check to see if input data is valid.
	 * @param mixed $input The input data.
	 * @return boolean Returns true if validation was successful, false otherwise.
	 **/
	public function validation(mixed $input) : bool {
		return true;
	}
	
	/**
	 * Preprocess the data before returning the data from the database.
	 * @param mixed $data The first parameter is the input data.
	 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
	 * @return mixed Return the data that should be in the variable.
	 **/
	public function get(mixed $data, zajModel &$object) : mixed {
		return Photo::fetch()->filter('parent',$object->id)->filter('field', $this->name);
	}

    /**
     * Returns the default value before an object is created and saved to the database.
	 * @param zajModel $object This parameter is a pointer to the actual object for which the default is being fetched. It is possible that the object does not yet exist.
     * @return zajFetcher Returns a list of objects.
     */
    public function get_default(zajModel &$object) : mixed {
        return $this->options['default'] ?? Photo::fetch()->exclude_all();
    }
	
	/**
	 * Preprocess the data before saving to the database.
	 * @param mixed $data The first parameter is the input data.
	 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
	 * @return array Returns an array where the first parameter is the database update, the second is the object update
	 * @todo Fix where second parameter is actually taken into account! Or just remove it...
	 **/
	public function save(mixed $data, zajModel &$object) : mixed {
		// if data is a photo object
			if(is_object($data) && is_a($data, 'Photo')){
				// check to see if already has parent (disable hijacking of photos)
					if($data->data->parent && $object->id != $data->data->parent) return zajLib::me()->warning("Cannot set parent of a photo object that already has a parent!");
				// now set parent
					$data->set('class', $object->class_name);
					$data->set('parent', $object->id);
					$data->set('field', $this->name);
					$data->set('status', 'saved');
					$data->save();
			}
		// else if it is an array (form field input)
            elseif(is_string($data)) {
				$sdata = $data;
				$data = json_decode($data);
				// If data is empty alltogether, it means that it wasnt JSON data, so it's a single photo id to be added!
					if(empty($data) && !empty($sdata)){
						$pobj = Photo::fetch($sdata);
							// cannot reclaim here!
							if($object->id != $pobj->parent && $pobj->status == 'saved') return zajLib::me()->warning("Cannot save a final of a photo that already exists! You are not the owner!");
						$pobj->set('class', $object->class_name);
						$pobj->set('parent', $object->id);
						$pobj->set('field', $this->name);
						$pobj->upload();
						return array(false, false);
					}
				// get new ones
					if(!empty($data->add)){
						foreach($data->add as $count=>$id){
							$pobj = Photo::fetch($id);
								// cannot reclaim here!
								if($object->id != $pobj->parent && $pobj->status == 'saved') return zajLib::me()->warning("Cannot save a final of a photo that already exists! You are not the owner!");
							$pobj->set('class', $object->class_name);
							$pobj->set('parent', $object->id);
							$pobj->set('field', $this->name);
							$pobj->upload();
						}
					}
				// delete old ones
					if(!empty($data->remove)){
						foreach($data->remove as $count=>$id){
							$pobj = Photo::fetch($id);
							if($object->id != $pobj->parent) return zajLib::me()->warning("Cannot delete a Photo object that belongs to another object!");
							$pobj->delete();
						}
					}
				// rename
					if(!empty($data->rename)){
						foreach($data->rename as $fileid=>$newname){
							$pobj = Photo::fetch($fileid);
							if($object->id != $pobj->parent) return zajLib::me()->warning("Cannot delete a Photo object that belongs to another object!");
							$pobj->set('name', $newname)->save();
						}
					}
				// reorder
					if(!empty($data->order)) Photo::reorder($data->order);
			}

		return array(false, false);
	}

}