<?php
/**
 * Field definition for photo collections.
 * @package Fields
 * @method zajfield_file max_file_size(int $m)
 * @method zajfield_file download_url(string $relative_url) Use {{id}} for file id. Example: admin/files/download/?id={{id}}
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
	public function __construct($name, $options, $class_name){
		// load up config file
			zajLib::me()->config->load('system/fields.conf.ini', 'files');
		// set default options
			if(empty($options['max_file_size'])) $options['max_file_size'] = zajLib::me()->config->variable->field_files_max_file_size_default;
		// call parent constructor
			parent::__construct(__CLASS__, $name, $options, $class_name);
	}

	/**
	 * Preprocess the data before returning the data from the database.
	 * @param mixed $data The first parameter is the input data.
	 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
	 * @return File|bool Return the data that should be in the variable.
	 **/
    public function get(mixed $data, zajModel &$object) : File|bool {
		return File::fetch()->filter('parent',$object->id)->filter('field', $this->name)->next();
	}

	/**
	 * Preprocess the data before saving to the database.
	 * @param mixed $data The first parameter is the input data.
	 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
	 * @return array|bool Returns an array where the first parameter is the database update, the second is the object update
	 * @todo Fix where second parameter is actually taken into account! Or just remove it...
	 **/
    public function save(mixed $data, zajModel &$object) : array|bool {
		// if it is a string (form field input where it's the id), convert to an object first
        if(!empty($data) && is_string($data) || is_integer($data)){
            $fobj = File::fetch($data);
            if(is_object($fobj)) $data = $fobj;
        }
		// if data is a file object
        if(is_object($data) && is_a($data, 'File')){
            /** @var File $data **/
            // Check to see if already has parent (disable hijacking of photos)
                if($data->data->parent) return zajLib::me()->warning("Cannot set parent of a file object that already has a parent!");
            // Remove previous ones
                $file = File::fetch()->filter('parent', $object->id)->filter('field', $this->name);
                foreach($file as $pold){ $pold->delete(); }
            // Set new one

            // check to see if already has parent (disable hijacking of photos)
                if($data->data->parent) return zajLib::me()->warning("Cannot set parent of a file object that already has a parent!");
            // now set parent
                $data->set('class', $object->class_name);
                $data->set('parent', $object->id);
                $data->set('field', $this->name);
                $data->upload();
            return array(false, false);
        }
        // else if it is an array (form field input)
        else{
            $data = json_decode($data);
            // If data is empty alltogether, it means that it wasnt JSON data, so it's a single photo id to be added!
            if(empty($data) && !empty($sdata)){
                $fobj = File::fetch($sdata);
                // cannot reclaim here!
                if($object->id != $fobj->parent && $fobj->status == 'saved') return zajLib::me()->warning("Cannot attach an existing and saved File object to another object!");
                $fobj->set('class', $object->class_name);
                $fobj->set('parent',$object->id);
                $fobj->set('field',$this->name);
                $fobj->upload();
                return array(false, false);
            }
            // get new ones
            if(!empty($data->add)){
                foreach($data->add as $count=>$id){
                    $fobj = File::fetch($id);
                    // cannot reclaim here!
                    if($fobj->status == 'saved' && $fobj->data->parent != $object->id) return zajLib::me()->error("Cannot attach an existing and saved File object to another object!");
                    $fobj->set('class', $object->class_name);
                    $fobj->set('parent', $object->id);
                    $fobj->set('field', $this->name);
                    $fobj->upload();
                }
            }
            // rename
            if(!empty($data->rename)){
                foreach($data->rename as $fileid=>$newname){
                    $fobj = File::fetch($fileid);
                    if($object->id != $fobj->parent) return zajLib::me()->warning("Cannot rename a File object that belongs to another object!");
                    $fobj->set('name', $newname)->save();
                }
            }
            // delete old ones
            if(!empty($data->remove)){
                foreach($data->remove as $count=>$id){
                    $fobj = File::fetch($id);
                    if($object->id != $fobj->parent) return zajLib::me()->warning("Cannot delete a File object that belongs to another object!");
                    $fobj->delete();
                }
            }
            // reorder
            if(!empty($data->order)) File::reorder($data->order);
        }
		return array(false, false);
	}

}