<?php

    /**
     * Field definition for configuration (select a list of comma separated values from a config file).
     * @package Fields
     * @subpackage User
     **/
    class zajfield_configuration extends zajField {
        // name, options - these are passed to constructor and available here!
        const in_database = true;        // boolean - true if this field is stored in database
        const use_validation = false;    // boolean - true if data should be validated before saving
        const use_get = false;            // boolean - true if pre-processing required before getting data
        const use_save = false;            // boolean - true if pre-processing required before saving data
        const use_duplicate = true;       // boolean - true if data should be duplicated when duplicate() is called
        const use_filter = false;        // boolean - true if fetch is modified
        const disable_export = false;    // boolean - true if you want this field to be excluded from exports
        const search_field = false;        // boolean - true if this field is used during search()
        const edit_template = 'field/configuration.field.html';        // string - the edit template, false if not used
        const filter_template = 'field/configuration.filter.html';    // string - the filter template
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
                'type'    => 'varchar',
                'option'  => [
                    0 => 255,
                ],
                'key'     => 'MUL',
                'default' => is_array($this->options) && array_key_exists('default', $this->options) ? $this->options['default'] : '',
                'extra'   => '',
                'comment' => 'text',
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
         * Returns the default value before an object is created and saved to the database.
         * @param zajModel $object This parameter is a pointer to the actual object for which the default is being fetched. It is possible that the object does not yet exist.
         * @return mixed Returns an empty list.
         */
        public function get_default(&$object) {
            if (is_array($this->options) && array_key_exists('default', $this->options) && is_object($this->options['default'])) {
                return $this->options['default'];
            } else {
                return "";
            }
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