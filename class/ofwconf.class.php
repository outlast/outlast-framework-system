<?php

    /**
     * A class storing Outlast Framework configuration.
     * @package Base
     * For descriptions, see site/index.php.
     * @property boolean $debug_mode
     * @property [string] $debug_mode_domains
     * @property string $root_folder
     * @property string $site_folder
     * @property string $default_app
     * @property string $default_mode
     * @property [string] $plugin_apps
     * @property [string] $system_apps
     * @property boolean $mysql_enabled
     * @property string $mysql_server
     * @property string $mysql_encoding
     * @property string $mysql_user
     * @property string $mysql_password
     * @property string $mysql_db
     * @property [string] $mysql_ignore_tables
     * @property boolean $update_enabled
     * @property string $update_appname
     * @property string $update_user
     * @property string $update_password
     * @property boolean $error_log_enabled
     * @property boolean $error_log_notices
     * @property boolean $error_log_backtrace
     * @property string $error_log_file
     * @property boolean $jserror_log_enabled
     * @property string $jserror_log_file
     * @property boolean $feature_xss_protection_enabled
     * @property boolean $feature_csrf_protection_enabled
     * @property boolean $feature_model_decoration_enabled
     * @property string $locale_default
     * @property string $locale_available
     * @property string $locale_admin
     * @property integer $config_file_version
     * @property string $locale_numeric
     * @property integer $plupload_photo_maxwidth
     * @property integer $plupload_photo_maxheight
     * @property integer $plupload_photo_maxuploadwidth
     * @property integer $plupload_photo_maxphotosize
     * @property integer $plupload_photo_maxfilesize
     * @property integer $plupload_files_maxfilesize
     **/
    class OfwConf implements ArrayAccess {

        /**
         * @var array A key/value array of configuration values.
         */
        private $values;

        /**
         * Creates a new ofw conf instance from an existing array.
         * @param array $values
         */
        public function __construct($values) {
            $this->values = $values;
        }

        /**
         * ArrayAccess implementation to check if offset exists.
         * @param string $offset
         * @return bool
         */
        public function offsetExists($offset) {
            return key_exists($offset, $this->values);
        }

        /**
         * ArrayAccess implementation to get offset value.
         * @param string $offset
         * @return mixed
         */
        public function offsetGet($offset) {
            return $this->values[$offset];
        }

        /**
         * ArrayAccess implementation to set offset value.
         * @param string $offset
         * @param mixed $value
         */
        public function offsetSet($offset, $value) {
            $this->values[$offset] = $value;
        }

        /**
         * ArrayAccess implementation to unset offset.
         * @param string $offset
         */
        public function offsetUnset($offset) {
            unset($this->values[$offset]);
        }

        /**
         * Wrapper to allow regular object property access to values.
         * @param string $offset
         * @return mixed
         */
        public function __get($offset) {
            return $this->values[$offset];
        }

        /**
         * Wrapper to allow regular object property access to values.
         * @param string $offset
         * @param mixed $value
         */
        public function __set($offset, $value) {
            $this->values[$offset] = $value;
        }

        /**
         * Wrapper to make sure isset/empty works properly.
         * @param $name
         * @return bool Returns true if the item is set.
         */
        public function __isset($name) {
            return array_key_exists($name, $this->values);
        }

    }