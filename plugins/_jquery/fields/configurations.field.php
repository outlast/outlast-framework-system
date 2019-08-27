<?php

    /**
     * Field definition for configurations (select a list of comma separated values from a config file).
     * @package Fields
     * @subpackage User
     **/
    class zajfield_configurations extends zajField {
        // name, options - these are passed to constructor and available here!
        const in_database = true;        // boolean - true if this field is stored in database
        const use_validation = false;    // boolean - true if data should be validated before saving
        const use_get = true;            // boolean - true if pre-processing required before getting data
        const use_save = true;            // boolean - true if pre-processing required before saving data
        const use_duplicate = true;        // boolean - true if data should be duplicated when duplicate() is called
        const use_filter = true;        // boolean - true if fetch is modified
        const disable_export = false;    // boolean - true if you want this field to be excluded from exports
        const search_field = false;        // boolean - true if this field is used during search()
        const edit_template = 'field/configurations.field.html';        // string - the edit template, false if not used
        const filter_template = 'field/configurations.filter.html';    // string - the filter template
        const show_template = false;    // string - used on displaying the data via the appropriate tag (n/a)

        // Construct
        public function __construct($name, $options, $class_name, &$ofw) {
            // set default options
            // no default options
            // call parent constructor
            parent::__construct(__CLASS__, $name, $options, $class_name, $ofw);
        }

        /**
         * Defines the structure and type of this field in the mysql database.
         * @return array Returns in array with the database definition.
         **/
        public function database() {
            // define each field
            $fields[$this->name] = [
                'field'   => $this->name,
                'type'    => 'text',
                'option'  => [],
                'key'     => '',
                'default' => false,
                'extra'   => '',
                'comment' => 'configurations',
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
            $result = json_decode($data);
            if (!$result) {
                $result = (object)[];
            }

            return $result;
        }

        /**
         * Returns the default value before an object is created and saved to the database.
         * @param zajModel $object This parameter is a pointer to the actual object for which the default is being fetched. It is possible that the object does not yet exist.
         * @return mixed Returns an empty list.
         */
        public function get_default(&$object) {
            if (is_object($this->options['default'])) {
                return $this->options['default'];
            } else {
                return (object)[];
            }
        }

        /**
         * Preprocess the data before saving to the database.
         * @param mixed $data The first parameter is the input data.
         * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
         * @return array Returns an array where the first parameter is the database update, the second is the object update
         **/
        public function save($data, &$object) {
            // Standard array, so serialize
            if ($data && is_array($data)) {
                $newdata = json_encode($data);
            } elseif(json_decode($data)) {
                $newdata = $data;
            } else {
                $newdata = "";
            }

            return [$newdata, $data];
        }

        /**
         * This is called when a filter() or exclude() methods are run on this field. It is actually executed only when the query is being built.
         * @param zajFetcher $fetcher A pointer to the "parent" fetcher which is being filtered.
         * @param array $filter An array of values specifying what type of filter this is.
         * @return bool
         **/
        public function filter(&$fetcher, $filter) {
            // break up filter
            list($field, $value, $logic, $type) = $filter;

            // escape value and allow to search in
            if($value != "") {
                $value = "%".addslashes($value)."%";
            }

            // filter return
            return "`$field` $logic '$value'";
        }

        /**
         * This method is called just before the input field is generated. Here you can set specific variables and such that are needed by the field's GUI control.
         * @param array $param_array The array of parameters passed by the input field tag. This is the same as for tag definitions.
         * @param zajCompileSource $source This is a pointer to the source file object which contains this tag.
         * @return bool
         */
        public function __onInputGeneration($param_array, &$source) {
            $this->ofw->compile->write('<?php $this->ofw->config->load("'.$this->options['file'].'", "'.$this->options['section'].'"); $this->ofw->variable->field->choices = explode(",", $this->ofw->config->variable->'.$this->options['key'].'); ?>');
            return true;
        }

    }