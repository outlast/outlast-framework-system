<?php
/**
 * Field definition for creating timestamps in the database.
 * @package Fields
 * @subpackage BuiltinFields
 **/
class zajfield_datetime extends zajField {
	// name, options - these are passed to constructor and available here!
	const in_database = true;		// boolean - true if this field is stored in database		
	const use_validation = false;	// boolean - true if data should be validated before saving
	const use_get = true;			// boolean - true if preprocessing required before getting data
	const use_save = true;			// boolean - true if preprocessing required before saving data
	const use_duplicate = true;		// boolean - true if data should be duplicated when duplicate() is called
	const use_filter = false;		// boolean - true if fetch is modified
	const disable_export = false;	// boolean - true if you want this field to be excluded from exports
	const search_field = false;		// boolean - true if this field is used during search()
	const edit_template = 'field/datetime.field.html';	// string - the edit template, false if not used
	const show_template = false;	// string - used on displaying the data via the appropriate tag (n/a)

	// Construct
	public function __construct($name, $options, $class_name){
		// set default options
		// no default options
		// call parent constructor
		parent::__construct(__CLASS__, $name, $options, $class_name);
	}

	/**
	 * Defines the structure and type of this field in the mysql database.
	 * @return array Returns in array with the database definition.
	 **/
    public function database() : array {
		// define each field
		$fields[$this->name] = array(
			'field' => $this->name,
			'type' => 'datetime',
			'option' => array(),
			'key' => 'MUL',
			'default' => '0000-00-00 00:00:00',
			'extra' => '',
			'comment' => 'datetime',
		);
		return $fields;
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
	 * @return integer Return the data that should be in the variable.
	 **/
    public function get(mixed $data, zajModel &$object) : mixed {
		// turn date into unix date
		$datetime = DateTime::createFromFormat("Y-m-d H:i:s", $data);
		$data = $datetime->getTimestamp();
		return $data;
	}

	/**
	 * Preprocess the data before saving to the database.
	 * @param mixed $data The first parameter is the input data.
	 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
	 * @return array Returns an array where the first parameter is the database update, the second is the object update
	 * @todo Fix where second parameter is actually taken into account! Or just remove it...
	 * @todo Remove display/format version
	 **/
    public function save(mixed $data, zajModel &$object) : mixed {
		// Convert a unix timestamp into the proper format
		if(is_numeric($data)){
			$datetime = new DateTime();
			$data = $datetime->setTimestamp($data)->format("Y-m-d H:i:s");
		}
		return array($data, $data);
	}
}