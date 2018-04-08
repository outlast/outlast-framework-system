<?php
/**
 * Field definition for storing files associated with an object.
 * @package Fields
 * @subpackage BuiltinFields
 * @method zajfield_file max_file_size(int $m)
 * @method zajfield_file download_url(string $relative_url) Use {{id}} for file id. Example: admin/files/download/?id={{id}}
 **/
class zajfield_files extends zajField {
	// name, options - these are passed to constructor and available here!
	const in_database = false;		// boolean - true if this field is stored in database
	const use_validation = false;	// boolean - true if data should be validated before saving
	const use_get = true;			// boolean - true if preprocessing required before getting data
	const use_save = true;			// boolean - true if preprocessing required before saving data
	const use_duplicate = false;	// boolean - true if data should be duplicated when duplicate() is called
	const use_filter = true;		// boolean - true if fetch is modified
	const disable_export = true;	// boolean - true if you want this field to be excluded from exports
	const search_field = false;		// boolean - true if this field is used during search()
	const edit_template = 'field/files.field.html';	// string - the edit template, false if not used
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
		return File::fetch()->filter('parent',$object->id); // ->filter('field', $this->name);
	}

    /**
     * Returns the default value before an object is created and saved to the database.
	 * @param zajModel $object This parameter is a pointer to the actual object for which the default is being fetched. It is possible that the object does not yet exist.
     * @return zajFetcher Returns a list of objects.
     */
    public function get_default(&$object){
        if(is_object($this->options['default'])) return $this->options['default'];
        else{
            // Return an empty zajfetcher
            return File::fetch()->exclude_all();
        }
    }

	/**
	 * Preprocess the data before saving to the database.
	 * @param mixed $data The first parameter is the input data.
	 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
	 * @return array Returns an array where the first parameter is the database update, the second is the object update
	 * @todo Fix where second parameter is actually taken into account! Or just remove it...
	 **/
	public function save($data, &$object){
		// if data is a photo object
			if(is_object($data) && is_a($data, 'File')){
				// check to see if already has parent (disable hijacking of photos)
					if($data->data->parent && $data->data->parent != $object->id) $this->zajlib->error("Cannot edit File: The requested File object is not an object of this parent!");
				// now set parent
                    $data->set('class', $object->class_name);
					$data->set('parent', $object->id);
					$data->set('field', $this->name);
					$data->set('status', 'saved');
					$data->save();
			}
		// else if it is an array (form field input)
			else{
				$data = json_decode($data);
				// If data is empty alltogether, it means that it wasnt JSON data, so it's a single photo id to be added!
                if(empty($data) && !empty($sdata)){
                    $fobj = File::fetch($sdata);
                        // cannot reclaim here!
                        if($object->id != $fobj->parent && $fobj->status == 'saved') return $this->zajlib->warning("Cannot attach an existing and saved File object to another object!");
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
                        if($fobj->status == 'saved' && $fobj->data->parent != $object->id) return $this->zajlib->error("Cannot attach an existing and saved File object to another object!");
                        $fobj->set('class', $object->class_name);
                        $fobj->set('parent', $object->id);
                        $fobj->set('field', $this->name);
                        $fobj->upload();
                    }
                }
				// rename
                if(!empty($data->rename)){
                    foreach($data->rename as $fileid=>$newname){
                        $pobj = File::fetch($fileid);
                        if($object->id != $pobj->parent) return $this->zajlib->warning("Cannot rename a File object that belongs to another object!");
                        $pobj->set('name', $newname)->save();
                    }
                }
				// delete old ones
                if(!empty($data->remove)){
                    foreach($data->remove as $count=>$id){
                        $fobj = File::fetch($id);
                        if($object->id != $fobj->parent) return $this->zajlib->warning("Cannot delete a File object that belongs to another object!");
                        $fobj->delete();
                    }
                }
				// reorder
                if(!empty($data->order)) File::reorder($data->order, false);
			}
		return array(false, false);
	}

    /**
     * This is called when a filter() or exclude() methods are run on this field. It is actually executed only when the query is being built.
     * @param zajFetcher $fetcher A pointer to the "parent" fetcher which is being filtered.
     * @param array $filter An array of values specifying what type of filter this is.
     **/
    public function filter(&$fetcher, $filter){
        // break up filter

        list($field, $value, $logic, $type) = $filter;
        // other fetcher's field
        $other_field = 'parent';

        // if value is a fetcher
        if(zajFetcher::is_instance_of_me($value)){
            // get my other query
            $other_fetcher = $value->limit(false)->sort(false);
            // add field source
            $other_fetcher->add_field_source('model.'.$other_field, 'other_field', true);
        }
        // else value is an id
        else{
            $model = $this->options['model'];
            $other_fetcher = $model::fetch();
            // filter the other fetcher
            $other_fetcher->filter('id', $value)->limit(false)->sort(false);
            $other_fetcher->add_field_source('model.'.$other_field, 'other_field', true);
        }
        // add source
        $as_name = strtolower('sub_'.$this->class_name.'_'.$this->options['model'].'_'.$this->name);
        $fetcher->add_source('('.$other_fetcher->get_query().')', $as_name);
        // create local query
        return "$as_name.other_field = model.id";
    }
}