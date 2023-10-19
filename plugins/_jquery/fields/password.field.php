<?php

    /**
     * Field definition for secure password storage fields.
     * @package Fields
     * @subpackage BuiltinFields
     **/
    class zajfield_password extends zajField {
        // name, options - these are passed to constructor and available here!
        const in_database = true;        // boolean - true if this field is stored in database
        const use_validation = true;    // boolean - true if data should be validated before saving
        const use_get = false;            // boolean - true if preprocessing required before getting data
        const use_save = true;            // boolean - true if preprocessing required before saving data
        const use_duplicate = true;        // boolean - true if data should be duplicated when duplicate() is called
        const use_filter = false;        // boolean - true if fetcher needs to be modified
        const disable_export = true;    // boolean - true if you want this field to be excluded from exports
        const search_field = false;        // boolean - true if this field is used during search()
        const edit_template = 'field/password.field.html';    // string - the edit template, false if not used
        const show_template = false;    // string - used on displaying the data via the appropriate tag (n/a)

        // Construct
        public function __construct($name, $options, $class_name) {
            // set default options
            // for backwards compatibility (deprecated)
            if (!empty($options[0])) {
                $options['length'] = $options[0];
                unset($options[0]);
            }
            // default length
            if (empty($options['length'])) {
                $options['length'] = 255;
            }
            // call parent constructor
            parent::__construct(__CLASS__, $name, $options, $class_name);
        }

        /**
         * Defines the structure and type of this field in the mysql database.
         * @return array Returns in array with the database definition.
         **/
        public function database() : array {
            // define each field
            $fields[$this->name] = [
                'field'   => $this->name,
                'type'    => 'varchar',
                'option'  => [
                    0 => $this->options['length'] ?? 255,
                ],
                'key'     => 'MUL',
                'default' => $this->options['default'] ?? null,
                'extra'   => '',
                'comment' => 'password',
            ];

            return $fields;
        }

        /**
         * Check to see if input data is valid.
         * @param mixed $input The input data.
         * @return boolean Returns true if validation was successful, false otherwise.
         **/
        public function validation(mixed $input) : bool  {
            // Validate password security
            if (strlen($input) < 8) {
                return false;
            }
            if (strlen($input) > 72) {
                return false;
            }
            // Password needs cap, small, and numbers
            if (!preg_match('/^(?=.*[0-9])(?=.*[a-zA-Z])[a-zA-Z0-9]+$/', $input)) {
                return false;
            }

            // All is ok
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
         **/
        public function save(mixed $data, zajModel &$object) : mixed {
            // encode
            $data = password_hash($data, PASSWORD_DEFAULT);
            // return
            return [$data, $data];
        }

    }