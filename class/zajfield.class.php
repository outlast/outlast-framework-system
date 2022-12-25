<?php

/**
 * Full field structure is stored in this class. These are the default return values of each method which are overridden in the field definition files.
 * @package Base
 **/
class zajField {

	/** @var zajLib  */
	protected $ofw;					// object - a reference to the singleton ofw object

	/** @deprecated  */
	protected $zajlib;					// object - a reference to the global zajlib object
	protected $class_name;				// string - class name of the parent class
	public $name;						// string - name of this field
	public $options;					// array - this is an array of the options set in the model definition
	public $type;						// string - type of the field (Outlast Framework type, not mysql)
    public $exists;                     // boolean - true if the field value exists in the database

	// Default values for fields
	const in_database = true;		// boolean - true if this field is stored in database
	const use_validation = false;	// boolean - true if data should be validated before saving
	const use_get = false;			// boolean - true if preprocessing required before getting data
	const use_save = false;			// boolean - true if preprocessing required before saving data
	const use_duplicate = true;		// boolean - true if data should be duplicated when duplicate() is called
	const use_filter = false;		// boolean - true if fetch is modified
	const use_export = false;		// boolean - true if export is formatted
	const disable_export = false;	// boolean - true if you want this field to be excluded from exports
	const search_field = true;		// boolean - true if this field is used during search()
	const edit_template = 'field/base.field.html';	    // string - the edit template, defaults to base
    const filter_template = 'field/base.filter.html';   // string - the filter template, defaults to base
	const show_template = false;	// string - used on displaying the data via the appropriate tag (n/a)

	/**
	 * Creates a field definition object
	 **/
	public function __construct($field_class, $name, $options, $class_name, &$zajlib){
		$this->zajlib =& $zajlib;
		$this->ofw =& $zajlib;
		$this->name = $name;
		$this->options = $options;
		$this->class_name = $class_name;
		$this->type = substr($field_class, 9);
	}

	/**
	 * Check to see if the field settings are valid. Run during database update.
	 * @return boolean|string Returns false if all is well, returns an error string if something is up.
	 **/
	public function get_settings_validation_errors(){
		return false;
	}

	/**
	 * Check to see if input data is valid.
	 * @param $input mixed The input data.
	 * @return boolean Returns true if validation was successful, false otherwise.
	 **/
	public function validation($input){
		return true;
	}

	/**
	 * Preprocess the data before returning the data from the database.
	 * @param $data mixed The first parameter is the input data.
	 * @param zajModel $object This parameter is a pointer to the actual object which is being retrieved.
	 * @return mixed Return the data that should be in the variable.
	 **/
	public function get($data, &$object){
		return $data;
	}

	/**
	 * Preprocess the data before saving to the database.
	 * @param $data mixed The first parameter is the input data.
	 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
	 * @return array Returns an array where the first parameter is the database update, the second is the object update
	 **/
	public function save($data, &$object){
		return $data;
	}

	/**
	 * Preprocess the data and convert it to a string before exporting.
	 * @param mixed $data The data to process. This will typically be whatever is returned by {@link get()}
	 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
	 * @return string|array Returns a string ready for export column. If you return an array of strings, then the data will be parsed into multiple columns with 'columnname_arraykey' as the name.
	 */
	public function export($data, &$object){
		return $data;
	}

	/**
	 * This is called when a filter() or exclude() methods are run on this field. It is actually executed only when the query is being built.
	 * @param zajFetcher $fetcher A pointer to the "parent" fetcher which is being filtered.
	 * @param array $filter An array of values specifying what type of filter this is.
	 * @return bool|string Returns false by default; this will use the default filter. Otherwise it can return the filter SQL string.
	 */
	public function filter(&$fetcher, $filter){
		return false;
	}

	/**
	 * This method allows you to create a subtable which is associated with this field.
	 * @return bool Return the table definition. False if no table.
	 **/
	public function table(){
		return false;
	}

	/**
	 * Defines the structure and type of this field in the mysql database.
	 * @return array Returns in array with the database definition.
	 **/
	public function database(){
		return array();
	}

	/**
	 * Duplicates the data when duplicate() is called on a model object. This method can be overridden to add extra processing before duplication. See built-in ordernum as an override example.
	 * @param $data mixed The first parameter is the input data.
	 * @param zajModel $object This parameter is a pointer to the actual object which is being duplicated.
	 * @return mixed Returns the duplicated value.
	 **/
	public function duplicate($data, &$object){
		return $data;
	}

    /**
     * Returns the default value before an object is created and saved to the database.
	 * @param zajModel $object This parameter is a pointer to the actual object for which the default is being fetched. It is possible that the object does not yet exist.
     * @return mixed Returns the default value.
     */
    public function get_default(&$object){
        if(is_object($this->options)) return $this->options->default;   // Old method
        else return $this->options['default'];
    }

	/**
	 * Returns an error message, but is this still needed?
	 **/
	public function form(){
		return "[undefined form field for $this->name. this is a bug in the system or in a plugin.]";
	}

	/**
	 * A static create method used to initialize this object.
	 * @param string $name The name of this field.
	 * @param zajDb $field_def An object definition of this field as defined by {@link zajDb}
	 * @param string $class_name The class name of the model.
	 * @return zajField A zajField-descendant.
	 **/
	public static function create($name, $field_def, $class_name=''){
		// get options and type
			$options = $field_def->options;
			$type = $field_def->type;
        // load field object file
			zajLib::me()->load->file('/fields/'.$type.'.field.php');
			$field_class = 'zajfield_'.$type;
		// name will be different for virtual fields
			if(!empty($field_def->virtual)) $name = $field_def->virtual;
		// create and return
			return new $field_class($name, $options, $class_name, zajLib::me());
	}

	/**
	 * This method is called just before the input field is generated. Here you can set specific variables and such that are needed by the field's GUI control.
	 * @param array $param_array The array of parameters passed by the input field tag. This is the same as for tag definitions.
	 * @param zajCompileSource $source This is a pointer to the source file object which contains this tag.
	 * @return bool Returns true by default.
	 **/
	public function __onInputGeneration($param_array, &$source){
		// does not do anything by default
		return true;
	}

    /**
	 * This method is called just before the filter field is generated. Here you can set specific variables and such that are needed by the field's GUI control.
	 * @param array $param_array The array of parameters passed by the filter field tag. This is the same as for tag definitions.
	 * @param zajCompileSource $source This is a pointer to the source file object which contains this tag.
	 * @return bool Returns true by default.
	 **/
	public function __onFilterGeneration($param_array, &$source){

        // Generate input related stuff
        $this->__onInputGeneration($param_array, $source);

        return true;
	}

}
