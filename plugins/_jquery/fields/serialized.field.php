<?php

	/**
	 * Field definition for serialized. An serialized field is useful for storing arrays or objects that we do not need as explicitly separate connected models. For example a simple log or some such could be stored here. Any array is actually converted and stored as an object for easier reference via the template language...
	 * @package Fields
	 * @subpackage BuiltinFields
	 **/
	class zajfield_serialized extends zajField {
		// name, options - these are passed to constructor and available here!
		const in_database = true;        // boolean - true if this field is stored in database
		const use_validation = false;    // boolean - true if data should be validated before saving
		const use_get = true;            // boolean - true if preprocessing required before getting data
		const use_save = true;            // boolean - true if preprocessing required before saving data
		const use_duplicate = true;        // boolean - true if data should be duplicated when duplicate() is called
		const use_filter = false;        // boolean - true if fetch is modified
		const disable_export = false;    // boolean - true if you want this field to be excluded from exports
		const search_field = true;        // boolean - true if this field is used during search()
		const edit_template = 'field/serialized.field.html';    // string - the edit template, false if not used
		const show_template = false;    // string - used on displaying the data via the appropriate tag (n/a)

		// Construct
		public function __construct($name, $options, $class_name, &$zajlib) {
			// set default options
			// no default options
			// call parent constructor
			parent::__construct(__CLASS__, $name, $options, $class_name, $zajlib);
		}

		/**
		 * Defines the structure and type of this field in the mysql database.
		 * @return array Returns in array with the database definition.
		 **/
		public function database() {
			// define each field
			$fields[$this->name] = [
				'field'   => $this->name,
				'type'    => 'mediumtext',
				'option'  => [],
				'key'     => '',
				'default' => false,
				'extra'   => '',
				'comment' => 'serialized',
			];

			return $fields;
		}

		/**
		 * Check to see if input data is valid.
		 * @param mixed $input The input data.
		 * @return boolean Returns true if validation was successful, false otherwise.
		 **/
		public function validation($input) {
			return true;
		}

		/**
		 * Preprocess the data before returning the data from the database.
		 * @param mixed $data The first parameter is the input data.
		 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
		 * @return mixed Return the data that should be in the variable.
		 **/
		public function get($data, &$object) {
			$result = unserialize($data);
			if (!$result) {
				$result = (object)[];
			}

			return $result;
		}

		/**
		 * Preprocess the data before saving to the database.
		 * @param mixed $data The first parameter is the input data.
		 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
		 * @return array Returns an array where the first parameter is the database update, the second is the object update
		 * @todo Fix where second parameter is actually taken into account! Or just remove it...
		 **/
		public function save($data, &$object) {
			// First let's check if this is a special array
			if (is_array($data) && array_key_exists('mozajik_field', $data) && !empty($data['mozajik_field'])) {
				$sdata = [];
                if (array_key_exists('key', $data['mozajik_field']) && array_key_exists('value', $data['mozajik_field'])) {
                    foreach ($data['mozajik_field']['key'] as $key => $value) {
                        $sdata[$value] = $data['mozajik_field']['value'][$key];
                    }
                }
				$data = $sdata;
			}
			// Now let's check if this is a special array coming from a key/value form
			if (is_array($data) && array_key_exists('key', $data) && array_key_exists('value', $data) && is_array($data['key']) && is_array($data['value'])) {
				$sdata = [];
				foreach ($data['key'] as $key => $value) {
					$sdata[$value] = $data['value'][$key];
				}
				$data = $sdata;
			}
			// Standard array, so serialize
			if (is_array($data)) {
				$newdata = serialize($this->ofw->array->to_object($data));
			} else if (is_object($data)) {
				$newdata = serialize($data);
			} else {
				$newdata = $data;
			}

			return [$newdata, $data];
		}

	}