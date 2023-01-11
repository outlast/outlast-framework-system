<?php
/**
 * Field definition for defining fields.
 * @package Fields
 * @subpackage BuiltinFields
 **/
class zajfield_polymorphic extends zajField {
	// name, options - these are passed to constructor and available here!
	const in_database = true;		// boolean - true if this field is stored in database		
	const use_validation = false;	// boolean - true if data should be validated before saving
	const use_get = true;			// boolean - true if preprocessing required before getting data
	const use_save = true;			// boolean - true if preprocessing required before saving data
	const use_duplicate = false;	// boolean - true if data should be duplicated when duplicate() is called
	const use_filter = true;			// boolean - true if fetcher needs to be modified
	const use_export = true;		// boolean - true if preprocessing required before exporting data
	const disable_export = false;	// boolean - true if you want this field to be excluded from exports
	const search_field = false;		// boolean - true if this field is used during search()
	const edit_template = 'field/manytoone.field.html';	// string - the edit template, false if not used
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
					'field' => $this->name.'_class',
					'type' => 'varchar',
					'option' => array(
						0 => 50,
					),
 					'key' => 'MUL',
					'default' => '',
					'extra' => '',
					'comment' => 'manytoone',
			);
			$fields[$this->name] = array(
					'field' => $this->name.'_id',
					'type' => 'varchar',
					'option' => array(
						0 => 50,
					),
 					'key' => 'MUL',
					'default' => '',
					'extra' => '',
					'comment' => 'manytoone',
			);
			$fields[$this->name] = array(
					'field' => $this->name.'_field',
					'type' => 'varchar',
					'option' => array(
						0 => 50,
					),
 					'key' => 'MUL',
					'default' => '',
					'extra' => '',
					'comment' => 'manytoone',
			);
		return $fields;
	}

		/**
	 * Check to see if input data is valid.
	 * @param mixed $input The input data.
	 * @return boolean Returns true if validation was successful, false otherwise.
	 **/
	public function validation(mixed $input) : bool {
		if(empty($input)) return false;
		return true;
	}

	/**
	 * Preprocess the data before returning the data from the database.
	 * @param mixed $data The first parameter is the input data.
	 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
	 * @return mixed Return the data that should be in the variable.
	 **/
	public function get(mixed $data, zajModel &$object) : mixed {
		// Get unprocessed data fields (since data does not contain anything)
			/** @var zajModel $class_name */
			$class_name = $object->data->get_unprocessed($this->name."_class");
			$id = $object->data->get_unprocessed($this->name."_id");
		return $class_name::fetch($id);
	}

	/**
	 * Preprocess the data before saving to the database.
	 * @param mixed $data The first parameter is the input data.
	 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
	 * @return array Returns an array where the first parameter is the database update, the second is the object update, third is key/value pair of any other fields that need to be updated
	 **/
	public function save(mixed $data, zajModel &$object) : mixed {
		// Only accepts zajModel objects
		if(!zajModel::is_instance_of_me($data)){
			return zajLib::me()->error('Problem found on '.$object->table_name.'.'.$this->name.': Polymorphic connections only accept single model objects!');
		}
		else{
			/** @var zajModel $data */
			$other_fields = [
				$this->name.'_class'=>$data->class_name,
				$this->name.'_id'=>$data->id,
				$this->name.'_field'=>$this->name,
			];
			// Explicitly return false as the first parameter to prevent db update
			return [false, $data, $other_fields];
		}
	}

	/**
	 * Preprocess the data and convert it to a string before exporting.
	 * @param mixed $data The data to process. This will typically be whatever is returned by {@link get()}
	 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
	 * @return string|array Returns a string ready for export column. If you return an array of strings, then the data will be parsed into multiple columns with 'columnname_arraykey' as the name.
	 */
    public function export(mixed $data, zajModel &$object) : string|array {
		// Decide how to format it
			if(!empty($data->name)) $data = $data->name.' ('.$data->class_name.' - '.$data->id.')';
			else $data = $data->id;
		return $data;
	}

	/**
	 * This is called when a filter() or exclude() methods are run on this field. It is actually executed only when the query is being built.
	 * @param zajFetcher $fetcher A pointer to the "parent" fetcher which is being filtered.
	 * @param array $filter An array of values specifying what type of filter this is.
	 * @return string Returns a filter SQL string.
	 **/
	public function filter(zajFetcher &$fetcher, array $filter) : bool|string {
		// break up filter
			list($field, $value, $logic, $type) = $filter;
		// assemble code
			// @todo IMPLEMENT FILTER!

        return false;
        /**
			// if value is a fetcher
			if(zajFetcher::is_instance_of_me($value)){
				// get my other query
					$other_fetcher = $value->limit(false)->sort(false);
					$query = '('.$other_fetcher->get_query().')';
				// figure out how to connect me
					if($logic=='NOT LIKE' || $logic=='!=' || $logic=='!==') $logic = "NOT IN";
					else $logic = "IN";
				// generate query and return
					return "model.`$field` $logic $query";
			}
			elseif(is_array($value)){
				// get my other query
					$query = '("'.join('","', $value).'")';
				// figure out how to connect me
					if($logic=='NOT LIKE' || $logic=='!=' || $logic=='!==') $logic = "NOT IN";
					else $logic = "IN";
				// generate query and return
					return "model.`$field` $logic $query";
			}
			else{
				// Possible values: object, string, boolean false
					if(zajModel::is_instance_of_me($value)) $value = $value->id;
					elseif($value === false) return "0"; // Return no filter if boolean false
					elseif(!is_string($value) && !is_integer($value)) return zajLib::me()->error("Invalid value given for filter/exclude of fetcher object for $this->class_name/$field! Must be a string, a model object, or a fetcher object!");
				// All is ok, now simply return
					return "model.`$field` $logic '".zajLib::me()->db->escape($value)."'";
			}
		 **/
	}

}