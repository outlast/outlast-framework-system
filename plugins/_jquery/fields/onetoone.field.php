<?php

    /**
     * Field definition which defines a one to one relationship between models.
     * @package Fields
     * @subpackage BuiltinFields
     **/
    class zajfield_onetoone extends zajField {
        // name, options - these are passed to constructor and available here!
        const in_database = true;        // boolean - true if this field is stored in database
        const use_validation = false;    // boolean - true if data should be validated before saving
        const use_get = true;            // boolean - true if preprocessing required before getting data
        const use_save = true;            // boolean - true if preprocessing required before saving data
        const use_duplicate = false;    // boolean - true if data should be duplicated when duplicate() is called
        const use_filter = true;        // boolean - true if fetcher needs to be modified
        const use_export = true;        // boolean - true if preprocessing required before exporting data
        const disable_export = false;    // boolean - true if you want this field to be excluded from exports
        const search_field = false;        // boolean - true if this field is used during search()
        const edit_template = 'field/onetoone.field.html';    // string - the edit template, false if not used
        const show_template = false;    // string - used on displaying the data via the appropriate tag (n/a)

        // Construct
        public function __construct($name, $options, $class_name, &$zajlib) {
            // set default options
            // relation fields dont really have options, they're parameters
            if (empty($options[0])) {
                return zajLib::me()->error("Required parameter 1 missing for field $name!");
            }
            // array parameters
            if (is_array($options[0])) {
                $options = $options[0];
            } else {    // depricated
                $options['model'] = $options[0];
                if (!empty($options[1])) {
                    $options['field'] = $options[1];
                    unset($options[1]);
                }
                unset($options[0]);
            }

            // call parent constructor
            return parent::__construct(__CLASS__, $name, $options, $class_name, $zajlib);
        }

        /**
         * Get the field pointing to me from the other side.
         * @return array An array of zajField objects with the field name as the key.
         */
        private function get_other_fields() {

            /** @var zajModel $class_name */

            // If I am the primary, then the other side needs to be secondary (and only one)
            if (empty($this->options['field'])) {
                $fields = [];
                $class_name = $this->options['model'];
                $other_model = $class_name::__model();
                /**  @var zajField $field */
                $other_side_fields = 0;
                foreach ($other_model as $field_name => $field) {
                    if ($field->type == 'onetoone' && ($field->options['model'] == $this->class_name || $field->options[0] == $this->class_name) && ($field->options['field'] == $this->name || $field->options[1] == $this->name)) {
                        $fields[$field_name] = $field;
                    }
                }

                return $fields;

            } else {
                $class_name = $this->options['model'];
                $field_name = $this->options['field'];

                return [$field_name => $class_name::__field($field_name)];
            }

        }


        /**
         * Check to see if the field settings are valid. Run during database update.
         * @return boolean|string Returns false if all is well, returns an error string if something is up.
         **/
        public function get_settings_validation_errors() {

            // If I am the primary, then the other side needs to be secondary (and only one)
            if (empty($this->options['field'])) {
                // Resume as object if id
                /** @var zajModel $class_name */
                $class_name = $this->options['model'];
                /**  @var zajField $field */
                $field_models = $this->get_other_fields();
                if (count($field_models) < 1) {
                    return 'The other side of a onetoone needs to exist at least once. Check the '.$class_name.' model for misconfiguration!';
                }
                if (count($field_models) > 1) {
                    return 'The other side of a onetoone can only be defined once. Check the '.$class_name.' model for misconfiguration!';
                }

            } else {
                // If I am the secondary, the other side needs to be primary
                /** @var zajModel $class_name */
                $class_name = $this->options['model'];
                $field_name = $this->options['field'];
                $field_models = $this->get_other_fields();
                $field_model = $field_models[$field_name];
                if ($field_model->class_name != $class_name || $field_model->type != 'onetoone' || !empty($field_model->options[1]) || !empty($field_model->options['field'])) {
                    return 'The other side of a secondary onetoone needs to be a primary onetoone. Check this field or the '.$class_name.' model for misconfiguration!';
                }
            }

            return false;

        }

        /**
         * Defines the structure and type of this field in the mysql database.
         * @return array Returns in array with the database definition.
         **/
        public function database() {

            // define each field
            $fields = [];
            if (empty($this->options['field'])) {
                $fields[$this->name] = [
                    'field'   => $this->name,
                    'type'    => 'varchar',
                    'option'  => [
                        0 => 50,
                    ],
                    'key'     => 'MUL',
                    'default' => $this->options['default'],
                    'extra'   => '',
                    'comment' => 'onetoone',
                ];
            }

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
            return zajFetcher::onetoone($object->class_name, $this->name, $data, $object);
        }

        /**
         * Preprocess the data before saving to the database.
         * @param mixed $data The first parameter is the input data.
         * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
         * @return array Returns an array where the first parameter is the database update, the second is the object update
         * @todo Fix where second parameter is actually taken into account! Or just remove it...
         **/
        public function save($data, &$object) {

            // Validate data
            if (!is_string($data) && !zajModel::is_instance_of_me($data) && !is_bool($data)) {
                $this->ofw->warning("Unknown data value set for onetoone field {$this->class_name}.{$this->name}.");

                return [false, $data];
            }

            // Decide which side this is
            if (empty($this->options['field'])) {
                // Primary side

                // Get the other field data
                /** @var zajModel $other_class_name */
                $other_class_name = $this->options['model'];
                $other_fields = $this->get_other_fields();
                // @todo Replace with array_key_first() in 7.3+
                $other_field_name = reset(array_keys($other_fields));
                $other_field = $other_fields[$other_field_name];

                // Resume as object if id
                if (!is_bool($data)) {
                    /** @var zajModel $data */
                    $newdata = $other_class_name::fetch($data);
                    if (!$newdata) {
                        $this->ofw->warning("Unknown id set for object of type {$other_class_name} for onetoone field {$this->class_name}.{$this->name}.");
                    } else {
                        $data = $newdata;
                    }
                } else {
                    // False value sets to empty
                    if ($data === false) {
                        $old_other_object = $object->data->{$this->name};
                        if ($other_field && $old_other_object) {
                            $old_other_object->data->unload($other_field_name);
                        }

                        return ['', false];
                    } else {
                        $this->ofw->warning("Invalid data set for object of type {$other_class_name} for onetoone field {$this->class_name}.{$this->name}: $data");

                        return ['', false];
                    }
                }

                // Regular old data
                if ($other_field) {
                    $data->data->unload($other_field_name);
                }

                return [$data->id, $data];

            } else {
                // Secondary side

                $class_name = $this->options['model'];
                $field_name = $this->options['field'];

                if ($data === false) {
                    $old_other_object = $object->data->{$this->name};
                    if ($old_other_object) {
                        $old_other_object->set($field_name, false)->save();
                    }
                } else {
                    /** @var zajModel $other_object */
                    $other_object = $class_name::fetch($data);
                    if ($other_object) {
                        $other_object->set($field_name, $object)->save();
                    }
                }

                // Do not save anything here
                return [false, $data];

            }

        }

        /**
         * This is called when a filter() or exclude() methods are run on this field. It is actually executed only when the query is being built.
         * @param zajFetcher $fetcher A pointer to the "parent" fetcher which is being filtered.
         * @param array $filter An array of values specifying what type of filter this is.
         * @return bool|string Returns false by default; this will use the default filter. Otherwise it can return the filter SQL string.
         */
        public function filter(&$fetcher, $filter) {

            // break up filter
            list($field, $value, $logic, $type) = $filter;

            // other fetcher's field
            $other_field = $this->options['field'];

            // If we are on the primary side, just use the default
            if (empty($other_field)) {
                return false;   // Will revert to the default implementation
            }

            // Otherwise we are on the secondary side...

            // if value is a fetcher
            if (zajFetcher::is_instance_of_me($value)) {
                // get my other query
                /** @var zajFetcher $other_fetcher */
                $other_fetcher = $value->limit(false)->sort(false);
                // add field source
                $other_fetcher->add_field_source('model.'.$other_field, 'other_field', true);
            } // else value is an id
            else {
                $model = $this->options['model'];
                $other_fetcher = $model::fetch();
                // filter the other fetcher
                $other_fetcher->filter('id', $value)->limit(false)->sort(false);
                $other_fetcher->add_field_source('model.'.$other_field, 'other_field', true);
            }
            // add source
            $as_name = strtolower('sub_'.$this->class_name.'_'.$this->options['model'].'_'.$this->name);
            $fetcher->add_source('('.$other_fetcher->get_query().')', $as_name);
            $fetcher->group('id');

            // create local query
            return "$as_name.other_field = model.id";
        }


        /**
         * Preprocess the data and convert it to a string before exporting.
         * @param mixed $data The data to process. This will typically be whatever is returned by {@link get()}
         * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
         * @return string|array Returns a string ready for export column. If you return an array of strings, then the data will be parsed into multiple columns with 'columnname_arraykey' as the name.
         */
        public function export($data, &$object) {
            // Decide how to format it
            if (!empty($data->name)) {
                $data = $data->name.' ('.$data->id.')';
            } else {
                $data = $data->id;
            }

            return $data;
        }
    }