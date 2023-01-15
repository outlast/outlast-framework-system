<?php
    /**
     * Model data access and loading.
     * The zajData class is a helper class which allows the end user to retrieve, set, and save the model field data. The class handles the various data
     *  types and loads additional helper classes (from plugins/fields/) if needed. It also helps out with cacheing.
     * @author Aron Budinszky <aron@outlast.hu>
     * @version 3.0
     * @package Model
     * @subpackage Database
     */

    /**
     * The basic fields that all zajModel objects have.
     * @property string $name The name of the object.
     * @property string $status The current status.
     * @property string $id The id of the object.
     * @property integer $ordernum The order number (autoincremented).
     * @property integer $time_create The time when the object was created.
     * @property integer $time_edit The time when the object was modified.
     * @property boolean $unit_test True if the object was created during a unit test.
     **/
    class zajData {
        // Instance variables
        /**
         * A reference to the "parent" object.
         * @var zajModel
         **/
        private zajModel $zajobject;
        /**
         * An array of my loaded data.
         * @var array
         **/
        private array $data = [];
        /**
         * The database connection session object.
         * @var zajlib_db_session|ofw_db_mock
         **/
        private zajlib_db_session|ofw_db_mock $db;
        /**
         * The array of data which has been loaded. Each element is either true or false.
         * @var array
         **/
        private array $loaded = [];
        /**
         * The array of data which has been modified. Each element is either true or false.
         * @var array
         **/
        private array $modified = [];
        /**
         * This is set to true if the data has been loaded from the database.
         * @var boolean
         **/
        private bool $fetched = false;
        /**
         * This is true if the {@link zajModel} object exists in the database.
         * @var boolean
         **/
        private bool $exists;
        /**
         * If set to true, the modified data will be returned when requested.
         * @var boolean
         * @todo Need a nicer way of accessing this data!
         **/
        public bool $__autosave = false;

        /**
         * Create the zajData object. This should never be used manually, but instead should be accessed through the model via $model_object->data.
         **/
        public function __construct(&$zajobject, $withMockDb = false) {
            // set my "parent" object
            $this->zajobject =& $zajobject;
            if ($withMockDb && $this->zajobject->ofw->test->is_running()) {
                $this->db = $withMockDb;        // use mock db session

                return true;
            } else {
                // create my db session
                $this->db = $this->zajobject->ofw->db->create_session();        // create my own database session
                // get row data from db
                return $this->reload();
            }
        }

        /**
         * Reload the entire object data from the database.
         **/
        public function reload(): bool {
            // reset everything
            $this->reset();
            // load from db
            $result = $this->db->select("SELECT * FROM `{$this->zajobject->table_name}` WHERE `{$this->zajobject->id_column}`='".addslashes($this->zajobject->id)."' LIMIT 1",
                true);
            // set exists to true (if any rows returned)
            $this->exists = (boolean)$this->db->get_num_rows();
            if (!$this->exists) {
                $this->data = [];
            } else {
                $this->data = $result ?? [];
            }
            // set to loaded from db
            $this->fetched = true;

            return $this->exists;
        }

        /**
         * Return true if this exists in the database, false otherwise.
         * @return boolean Returns true or false.
         **/
        public function exists(): bool {
            return $this->exists;
        }

        /**
         * Save all fields to the database. If pre-processing is required, then call in the field's helper class.
         **/
        public function save(): bool {
            // if i dont exist, init...but if it fails, return
            if (!$this->exists && !$this->init()) {
                return $this->zajobject->ofw->warning('save failed! could not initialize object in database!');
            }
            // if nothing modified, then return
            if (count($this->modified) <= 0) {
                return true;
            }

            // run through all modified variables
            list($dbupdate, $objupdate) = $this->process_updates($this->modified);

            // update in database
            $objupdate['time_edit'] = $dbupdate['time_edit'] = time(); // set edit time
            $this->db->edit($this->zajobject->table_name, $this->zajobject->id_column, $this->zajobject->id, $dbupdate);

            // merge $data with $objudpate and reset
            /// @TODO The objupdate merge never happens. This is not added for now as it might break things.
            $this->reset();
            $this->reset_modified();
            return true;
        }

        /**
         * Process updates and return an array of db and object updates
         *
         * @param array $fields_data This is the `data` or `modified` array containing the data fields and their values.
         * @return array The first element is the $dbupdate array (includes all processed data ready for db update).
         *                  The second element is the $objupdate array intended to update the `data` array's values.
         */
        private function process_updates(array $fields_data): array {

            // Empty updates
            $dbupdate = $objupdate = [];

            foreach ($fields_data as $name => $value) {
                // is preprocessing required for save?
                if ($this->zajobject->model->{$name}->use_save || $this->zajobject->model->{$name}->virtual) {
                    // load my field object

                    $field_object = zajField::create($name, $this->zajobject->model->$name);
                    $save_value = $field_object->save($value, $this->zajobject);
                    // process save
                    $dbupdate[$field_object->name] = $save_value[0];
                    $objupdate[$field_object->name] = $save_value[1];
                    if (array_key_exists(2, $save_value)) {
                        $additional_updates = $save_value[2];
                    }
                    // any additional fields for db update?
                    if (!empty($additional_updates) && is_array($additional_updates)) {
                        foreach ($additional_updates as $k => $v) {
                            $dbupdate[$k] = $v;
                        }
                    }
                    // if db update is prevented (by in_database setting or by explicit boolean false return)
                    if ($dbupdate[$field_object->name] === false || !$field_object::in_database) {
                        unset($dbupdate[$field_object->name]);
                    }
                } else {
                    // simply set to value
                    $dbupdate[$name] = $objupdate[$name] = $value;
                }
                // if objupdate is not dbupdate, then this is not loaded
                // It is now the specific task of use_save enabled fields to explicitly unload() if needed...
            }

            return [$dbupdate, $objupdate];
        }

        /**
         * Reset the loaded.
         **/
        public function reset(): bool {
            // reset loaded array
            $this->loaded = [];
            // reset data
            $this->data = [];
            // reset fetched status
            $this->fetched = false;
            return true;
        }

        /**
         * Reset modified.
         */
        public function reset_modified() : bool {
            // reset modified array
            $this->modified = [];
            return true;
        }

        /**
         * Delete this row from the database.
         * @param boolean $permanent Set to true if the delete should be permanent. Otherwise, by default, it will set the status to deleted.
         **/
        public function delete(bool $permanent = false) : void {
            if ($permanent) {
                $this->db->delete($this->zajobject->table_name, $this->zajobject->id_column, $this->zajobject->id);
            } else {
                $this->__set("status", "deleted");
                $this->save();
            }
        }

        /**
         * Initialize fields prior to inserting into the database.
         **/
        private function init() : bool {

            // All other fields' default values need to be processed through their respective save methods
            $default_field_values = [];
            foreach ($this->data as $key => $value) {
                // If the field is modified, it will be saved to the db anyway, so no need to save anything for this round
                if (!array_key_exists($key, $this->modified)) {
                    // Run the value through the save processing
                    $default_field_values[$key] = $value;
                }
            }

            // Init global default fields
            $global_default_field_values = [
                'time_create' => $this->data['time_create'] ?? time(),
                'ordernum' => MYSQL_MAX_PLUS,
                'id' => $this->zajobject->id
            ];

            // Merge with data
            $this->data['time_create'] = $global_default_field_values['time_create'];
            $this->data['ordernum'] = $global_default_field_values['ordernum'];
            $this->data['id'] =  $global_default_field_values['id'];

            // Process all non-global default values
            list($db_ready_default_field_values, $_) = $this->process_updates($default_field_values);

            // Merge values ready for db with global defaults
            $db_update_field_values = array_merge($db_ready_default_field_values, $global_default_field_values);

            // Save to db
            $result = $this->db->add($this->zajobject->table_name, $db_update_field_values);
            if (!$result) {
                return $this->zajobject->ofw->warning("SQL SAVE ERROR: ".$this->db->get_error()." / ".$this->db->get_last_query()." <a href='{$this->zajobject->ofw->baseurl}update/database/'>Update needed?</a>");
            }

            // Cleanup and done
            $this->exists = true;
            return true;
        }

        /**
         * Return all or a specific field's unprocessed data.
         * @param string $field_name If field_name is specified, only that field name will be returned.
         * @return mixed Returns the data or an array of data.
         **/
        public function get_unprocessed(string $field_name = '') : mixed {
            if ($field_name) {
                return $this->data[$field_name];
            } else {
                return $this->data;
            }
        }

        /**
         * Return JSON-encoded unprocessed data.
         * @todo Add a parameter to return process JSON data.
         **/
        public function json() : string {
            return json_encode($this->data);
        }

        /**
         * Magic method for retrieving specific fields from the data class. Since most of the time we use the data class to retrieve field data this is what is called
         *  most often via $model_object->data->field_name. If pre-processing is required, the data will be processed first and then sent to the end user.
         * @todo Make this more effecient via cacheing, especially if pre-processing is required.
         **/
        public function __get(string $name) : mixed {
            // check for error
            if (!$this->zajobject->model->$name) {
                return $this->zajobject->ofw->warning("Cannot get value of '$name'. field '$name' does not exist in model '{$this->zajobject->class_name}'!");
            }
            // do i need to reload the data?
            if (!$this->fetched) {
                $this->reload();
            }

            // if it still does not exist, return the default value
            if (!$this->exists) {
                $field_object = zajField::create($name, $this->zajobject->model->$name);
                $this->data[$name] = $field_object->get_default($this->zajobject);
            } else {
                // is preprocessing required for get?
                if (empty($this->loaded[$name]) && ($this->zajobject->model->{$name}->use_get || $this->zajobject->model->{$name}->virtual)) {
                    // load my field object
                    $field_object = zajField::create($name, $this->zajobject->model->$name);
                    // if no value, set to null (avoids notices)
                    if (empty($this->data[$field_object->name])) {
                        $this->data[$field_object->name] = null;
                    }
                    // process get
                    $this->data[$name] = $field_object->get($this->data[$field_object->name], $this->zajobject);
                }
            }

            // It has been loaded!
            $this->loaded[$name] = true;

            // Turn off autosave
            $autosavemode = $this->__autosave;
            $this->__autosave = false;
            // if modified has been requested...
            if ($autosavemode && isset($this->modified[$name])) {
                return $this->modified[$name];
            } // else return the data
            else {
                if (isset($this->data) && array_key_exists($name, $this->data)) {
                    return $this->data[$name];
                } else {
                    return null;
                }
            }
        }

        /**
         * Magic method used for modifying the data in specific fields. This is most often accessed via {@link zajModel->set()} method.
         * @param string $name The name of the field to set.
         * @param mixed $value Any kind of variable that the field accepts.
         **/
        public function __set(string $name, mixed $value) : void {
            // check for error
            if (!$this->zajobject->model->$name) {
                $this->zajobject->ofw->warning("cannot set value of '$name'. field '$name' does not exist in model '{$this->zajobject->class_name}'!");
                return;
            }

            // set the data
            $this->modified[$name] = $value;
        }

        /**
         * Unload a specific field (will trigger reload during next).
         **/
        public function unload(string $name) : void {
            // unset the data
            $this->loaded[$name] = false;
        }

        /**
         * Get the array of modified fields (or a specific field).
         * @param string|bool $specific_field If not set, this method will return an array of data fields. Otherwise, it will return the specific field in question.
         * @return ?array|?string The array of modified fields or a specific modified field.
         **/
        public function get_modified(string|bool $specific_field = false) : array|string|null {
            // return full array if requested
            if ($specific_field === false) {
                return $this->modified;
            } // return specific key
            else {
                return array_key_exists($specific_field, $this->modified) ? $this->modified[$specific_field] : null;
            }
        }

        /**
         * Returns true or false depending on whether the specified field has been modified. Modification means that set() has been used, but it has not yet been saved.
         * @param string $specific_field This is a string representing the name of the field in question.
         * @return bool
         */
        public function is_modified(string $specific_field) : bool {
            return array_key_exists($specific_field, $this->modified);
        }

        /**
         * Magic method used to warn the user if he/she tried to use zajData as a string.
         * @ignore
         **/
        public function __toString() : string {
            $this->zajobject->ofw->warning('Tried using zajData object as a String!');
            return '';
        }

    }