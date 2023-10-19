<?php

    /**
     * Basic field structure is stored in this class. This is a static class used to create the field array structure.
     * @package Base
     * @property string $type The name of the data type. Each data type must be defined az a zajField class.
     * @property array $options An associated array of options. Options can be set as arguments
     * @property string $virtual A virtual field (alias) pointing to another.
     * @property boolean $in_database True if this is stored in database.
     * @property boolean $use_validation True if it has a custom validation method.
     * @property boolean $use_get True if it has a custom get() method.
     * @property boolean $use_save True if it has a custom save() method.
     * @property boolean $use_duplicate True if it has a custom duplicate() method.
     * @property boolean $use_filter True if it has a custom filter() method.
     * @property boolean $use_export True if it has a custom export() method.
     * @property boolean $disable_export True if export is disabled on this field. This is used in export helper.
     * @property boolean $search_field True if this field should be included in a search().
     * @property boolean|string $edit_template The path of the template which should be displayed for {% input %} editors. If none, set to false.
     * @property boolean|string $show_template The path of the template which should be used when simply showing data from this field. If none, set to false.
     * @method zajDb unsigned(boolean $true_if_unsigned = true) Set unsigned to true for numeric types.
     * @method zajDb default(mixed $default_value) Specify a default value for this field.
     * @method zajDb validate(boolean $validate_or_not) Set the use_validation setting for this field. (not fully supported yet)
     * @method zajDb validation($validation_function) Override the validation function for this field. (not fully supported yet)
     * Types:
     * @method static zajfield_file file()
     * @method static zajfield_files files()
     **/
    class zajDb {
        /**
         * @var string A virtual field (alias) pointing to another.
         */
        public $virtual = false;

        /**
         * This method returns the type and structure of the field definition in an array format.
         **/
        public static function __callStatic($method, $args) {
            // Create my db field
            $zdb = new zajDb();
            // Create my datastructure
            $zdb->type = $method;
            $zdb->options = $args;
            // Now load my settings file
            $cname = 'zajfield_'.$method;
            $result = zajLib::me()->load->file("fields/$method.field.php", false);
            if (!$result) {
                zajLib::me()->error("Field type '$method' is not defined. Was there a typo? Are you missing the field definition plugin file?");
            }
            // Set my settings
            /* @var zajField $cname */
            $zdb->in_database = $cname::in_database;
            $zdb->use_validation = $cname::use_validation;
            $zdb->use_get = $cname::use_get;
            $zdb->use_save = $cname::use_save;
            $zdb->use_duplicate = $cname::use_duplicate;
            $zdb->use_filter = $cname::use_filter;
            $zdb->use_export = $cname::use_export;
            $zdb->disable_export = $cname::disable_export;
            $zdb->search_field = $cname::search_field;
            $zdb->edit_template = $cname::edit_template;
            $zdb->show_template = $cname::show_template;

            // return
            return $zdb;
        }

        /**
         * This method allows you to specify this as an alias field that points to another.
         * @param string $field_name The name of another field in the model. Must have the same data type.
         * @return zajDb Will always return itself.
         * @todo Get rid of this method since it's just an option and should be handled by __call().
         **/
        public function alias($field_name) {
            $this->virtual = $field_name;

            return $this;
        }

        /**
         * @deprecated
         */
        public function virtual($field_name) {
            return $this->alias($field_name);
        }

        /**
         * This method creates a zajField object for this db field and returns it.
         * @param string $class_name The model name for which you want to create this zajField object.
         * @return zajField Will return the zajField object for this.
         **/
        public function get_field($class_name) {
            return zajField::create($this->type, $this, $class_name);
        }

        /**
         * The call magic method can be used to set all other options specific to fields.
         * The method name ends up being used as an option. Single arguments are set as values. Multiple arguments are set as stdClass objects. If no parameters are sent, the value defaults to true.
         **/
        public function __call($method, $args) {
            // Convert argument into true if no args or to a single value if single arg
            if (count($args) <= 0) {
                $args = true;
            } else if (count($args) <= 1) {
                $args = $args[0];
            }
            // Now set options
            $this->options[$method] = $args;

            return $this;
        }
    }
