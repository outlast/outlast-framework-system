<?php
/**
 * Field definition for dates.
 * @package Fields
 * @subpackage BuiltinFields
 **/
 zajLib::me()->load->file('/fields/time.field.php');

class zajfield_date extends zajfield_time {
	// similar to time
	
	// save is different though
	const use_save = true;			// boolean - true if preprocessing required before saving data
	const use_filter = false;		// boolean - true if fetcher needs to be modified
	const use_export = true;		// boolean - true if preprocessing required before exporting data
	const search_field = false;		// boolean - true if this field is used during search()
	const edit_template = 'field/date.field.html';	// string - the edit template, false if not used

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
	public function save($data, &$object){
		if(is_array($data)){
			// date[format] and date[display] (backwards compatible
			if(!empty($data['format']) && !empty($data['display'])){
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
				$data = array_key_exists('value', $data) ? $data['value'] : "";
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
	public function export($data, &$object){
		if(is_numeric($data) && $data != 0) $data = date("Y.m.d. H:i:s", $data);
		return $data;
	}
}