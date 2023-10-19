<?php
/**
 * Field definition for time fields.
 * @package Fields
 * @subpackage BuiltinFields
 **/
class zajfield_time extends zajField {
	// name, options - these are passed to constructor and available here!
	const in_database = true;		// boolean - true if this field is stored in database		
	const use_validation = false;	// boolean - true if data should be validated before saving
	const use_get = false;			// boolean - true if preprocessing required before getting data
	const use_save = true;			// boolean - true if preprocessing required before saving data
	const use_duplicate = true;		// boolean - true if data should be duplicated when duplicate() is called
	const use_filter = false;		// boolean - true if fetch is modified
	const use_export = true;		// boolean - true if preprocessing required before exporting data
	const disable_export = false;	// boolean - true if you want this field to be excluded from exports
	const search_field = false;		// boolean - true if this field is used during search()
	const edit_template = 'field/time.field.html';	// string - the edit template, false if not used
	const filter_template = 'field/time.filter.html';	// string - the edit template, false if not used
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
					'type' => 'int',
					'option' => array(
						0 => 11,
					),
 					'key' => 'MUL',
					'default' => 0,
					'extra' => '',
					'comment' => 'time',
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
	 * @return mixed Return the data that should be in the variable.
	 **/
	public function get(mixed $data, zajModel &$object) : mixed {
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
		if(is_array($data)){
			// date[format] and date[display] (backwards compatible
			if(!empty($data['format'])){
				$dt = date_create_from_format($data['format'], $data['display']);
				if(is_object($dt)){
					$tz = date_default_timezone_get();
					$dt->setTimezone(new DateTimeZone($tz));
					$dt->setTime(0, 0);
					$data = $dt->getTimestamp();
				}
				else $data = '';
			}
			else{
				$data = $data['value'];
			}
		}
		return array($data, $data);
	}

	/**
	 * Preprocess the data and convert it to a string before exporting.
	 * @param mixed $data The data to process. This will typically be whatever is returned by {@link get()}
	 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
	 * @return string|array Returns a string ready for export column. If you return an array of strings, then the data will be parsed into multiple columns with 'columnname_arraykey' as the name.
	 */
    public function export(mixed $data, zajModel &$object) : string|array {
		if(is_numeric($data) && $data != 0) $data = date("Y.m.d. H:i:s", $data);
		return $data;
	}


}