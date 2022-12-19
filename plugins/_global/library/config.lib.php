<?php
    /**
     * This library handles the loading and compiling of configuration and language files.
     * @author Aron Budinszky <aron@outlast.hu>
     * @version 3.0
     * @package Library
     **/

    $GLOBALS['regexp_config_variable'] = "";
    $GLOBALS['regexp_config_comment'] = "//";

    /**
     * @property zajlibConfigVariable $variable The config variables.
     * @property stdClass $section The config variables broken into sections.
     */
    class  zajlib_config extends zajLibExtension {
        protected $dest_path = 'cache/conf/';        // string - subfolder where compiled conf files are stored (cannot be changed)
        protected $conf_path = 'conf/';            // string - default subfolder where uncompiled conf files are stored
        protected $type_of_file = 'configuration';// string - the name of the file type this is (either configuration or language)
        protected $loaded_files = [];        // array - all the files loaded with load()
        protected $debug_stats = [];            // array - contains debug stats about current compiled file
        protected $destination_files = [];    // array - an array of files to write to
        /**
         * object - config variables are stored here
         **/
        private $variable;
        private $section;

        /**
         * Creates a new zajlib_config
         * @param zajLib $zajlib A reference to the global zajlib object.
         * @param string $system_library The name of the system library.
         **/
        public function __construct(&$zajlib, $system_library) {
            // call parent
            parent::__construct($zajlib, $system_library);
            // init variables
            $this->variable = new stdClass();
            $this->section = new stdClass();
            $this->variable->section = &$this->section;
        }
	
        /**
         * Loads a configuration or language file at runtime.
         * @param string $source_path The source of the configuration file relative to the conf folder.
         * @param string|bool $section The section to compile.
         * @param boolean $force_set This will force setting of variables even if the same file / section was previously loaded.
         * @param boolean $fail_on_error If set to true (the default), it will fail with error.
         * @return bool Returns true if successful, false otherwise.
         */
        public function load($source_path, $section = false, $force_set = false, $fail_on_error = true) {
            // check chroot
            if (strpos($source_path, '..') !== false) {
                return $this->ofw->error($this->type_of_file.' source file must be relative to conf path.');
            }
            // generate section
            if ($section) {
                $fsection = '.'.$section;
            } else {
                $fsection = '';
            }
            // allow names without .conf.ini
            if (strstr($source_path, '.') === false) {
                $source_path = $source_path.'.conf.ini';
            }

            // create full file name
            $file_name = $this->ofw->basepath.$this->dest_path.$source_path.$fsection.'.php';

            // was it already loaded?
            if (!$force_set && !empty($this->loaded_files[$file_name])) {
                return true;
            }
            // does it exist? if not, compile now!
            $result = true;
            $force_compile = false;
            if ($force_compile || $this->ofw->debug_mode || !file_exists($file_name)) {
                $result = $this->compile($source_path, $fail_on_error);
            }
            // If compile failed or if include fails
            if (!$result || !(@include($file_name))) {
                if ($fail_on_error) {
                    return $this->error("Could not load ".$this->type_of_file." file $source_path / $section! Section not found ($file_name)!");
                } else {
                    return false;
                }
            }
            // set as loaded
            $this->loaded_files[$file_name] = true;

            return true;
        }

        /**
         * My getter method.
         **/
        public function __get($name) {
            if ($name == 'variable') {
                return $this->ofw->config->variable;
            }
            if ($name == 'section') {
                return $this->ofw->config->section;
            }

            return $this->$name;
        }

        /**
         * Set a specific variable in the global scope. You should use this only on rare occasion, for system-specific development!
         * @param string $key The variable key.
         * @param mixed $value The value.
         */
        public function set_variable($key, $value) {
            $this->variable->$key = $value;
        }

        /**
         * Unset a specific variable in the global scope. You should use this only on rare occasion, for system-specific development!
         * @param string $key The variable key.
         */
        public function unset_variable($key) {
            unset($this->variable->$key);
        }

        /**
         * Sets the key/value variable object. Be careful, this overwrites the entire current setting. Because conf and lang are actually the same (just separated by name) lang values will also be overwritten.
         * @param stdClass $variables The key/value pairs to use for the new variable.
         * @param stdClass $section The multi-dimensional key/value pairs to use for the new section variables.
         * @return bool Always returns true.
         */
        public function set_variables($variables, $section) {
            $this->variable = $variables;
            $this->section = $section;

            return true;
        }

        /**
         * Sets the key/value variable object. Be careful, this overwrites the entire current setting. Because conf and lang are actually the same (just separated by name) lang values will also be overwritten.
         * @return bool Always returns true.
         */
        public function reset_variables() {
            $this->loaded_files = [];
            $this->variable = new stdClass();
            $this->section = new stdClass();

            return true;
        }

        /**
         * Compiles a configuration file. Source_path should be relative to the conf path set by set_folder (conf/ by default). You should not call this method manually.
         * @param string $source_path The source of the configuration file relative to the conf folder.
         * @param boolean $fail_on_error If set to true (the default), it will fail with error.
         * @return boolean Returns true if successful, false otherwise.
         * @todo Make this private?
         **/
        public function compile($source_path, $fail_on_error = true) {
            // Search for my source file
            $full_path = $this->ofw->load->file($this->conf_path.$source_path, false, false);
            if ($full_path === false) {
                if ($fail_on_error) {
                    return $this->ofw->error($this->type_of_file.' file failed to load. The file '.$source_path.' could not be found in any of the local or plugin folders.');
                } else {
                    return false;
                }
            } else {
                $full_path = $this->ofw->basepath.$full_path;
            }
            // add the global output file
            $this->ofw->load->library('file');
            $global_file = $this->ofw->basepath.$this->dest_path.$source_path.'.php';
            $this->ofw->file->create_path_for($global_file);
            $this->add_file($global_file);
            $section_file = false;
            $current_section = false;
            $global_scope = '';
            // start debug stats
            $this->debug_stats['source'] = $full_path;
            $this->debug_stats['line'] = 1;
            // now open and run through all the lines
            $fsource = fopen($full_path, 'r');
            while (!feof($fsource)) {
                // grab a trimmed line
                $line = trim(fgets($fsource));
                // lets see what kind of line is this?
                switch (substr($line, 0, 1)) {
                    case false:        // it's an empty line, ignore!
                    case '#':        // it's a comment, ignore!
                        break;
                    case '[':        // it's a section marker, remove previous section file, add new section file
                        // remove previous if there is one
                        if ($section_file) {
                            $this->remove_file($section_file);
                        }
                        // add new one
                        $section = trim($line, '[]');
                        if (preg_replace('/^[a-zA-Z_][a-zA-Z0-9_]*/', '', $section) != '') {
                            $this->error('Illegal section definition. A-z, numbers, and _ allowed!');
                        }
                        $current_section = $section;
                        $section_file = $this->ofw->basepath.$this->dest_path.$source_path.'.'.$section.'.php';
                        $this->add_file($section_file, $global_scope);
                        $current_line = 'if (!property_exists($this->ofw->config->section, "'.$section.'")) $this->ofw->config->section->'.$section.' = new stdClass();';
                        $this->write_line($current_line."\n");
                        break;
                    default:        // it's a variable line
                        // let's first process the data
                        $vardata = $this->process_variable($line);

                        // are we in a section?
                        if ($current_section != '' && $current_section != false) {
                            $current_line = $this->section_variable_to_php($vardata, $current_section);
                        } else {
                            $current_line = $this->global_variable_to_php($vardata);
                        }

                        // check if problems
                        if ($current_line === false) {
                            break;
                        }
                        // write this data
                        $this->write_line($current_line);

                        // while not in any section, add the current line to the "global" scope
                        if (!$section_file) {
                            $global_scope .= $current_line;
                        }

                }
                $this->debug_stats['line']++;
            }
            $this->remove_all_files();

            return true;
        }

        /**
         * Turn a section variable into php data. The generated php data also includes global_variable_to_php(), so you do not need to call that if in a section.
         * @param array $vardata The variable data as returned from process_variable()
         * @param string $section The name of the section we are in.
         * @return string|boolean Returns the new data or boolean false if error.
         */
        public function section_variable_to_php($vardata, $section) {
            $varcontent = $vardata['varcontent'];
            $varname = $vardata['varname'];
            // generate variable
            // treat booleans and numbers separately
            if ($varcontent == 'false' || $varcontent == 'true' || is_numeric($varcontent)) {
                $current_line = '$this->ofw->config->section->'.$section.'->'.$varname.' = $this->ofw->config->variable->'.$varname.' = '.addslashes($varcontent).";\n";
            } else {
                $current_line = '$this->ofw->config->section->'.$section.'->'.$varname.' = $this->ofw->config->variable->'.$varname.' = \''.str_ireplace("'",
                        "\\'", $varcontent)."';\n";
            }

            return $current_line;
        }

        /**
         * Turn a global variable into php data.
         * @param array $vardata The variable data as returned from process_variable()
         * @return string|boolean Returns the new data or boolean false if error.
         */
        public function global_variable_to_php($vardata) {
            $varcontent = $vardata['varcontent'];
            $varname = $vardata['varname'];
            // generate variable
            // treat booleans and numbers separately
            if ($varcontent == 'false' || $varcontent == 'true' || is_numeric($varcontent)) {
                $current_line = '$this->ofw->config->variable->'.$varname.' = '.addslashes($varcontent).";\n";
            } else {
                $current_line = '$this->ofw->config->variable->'.$varname.' = \''.str_ireplace("'", "\\'",
                        $varcontent)."';\n";
            }

            return $current_line;
        }

        /**
         * Process a standard variable line using regex and turn it into usable data.
         * @param string $line The line data.
         * @return array|boolean Returns an array of data (varcontent, varname) or boolean false if error.
         */
        public function process_variable($line) {
            // separate by =
            list($varname, $varcontent) = explode('=', $line, 2);
            $varname = trim($varname);
            $varcontent = trim($varcontent);
            // is varname not valid?
            if (preg_replace('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', '', $varname) != '') {
                $this->error('Invalid variable found!');

                return false;
            }
            // reserved varname?
            if ($varname == 'ofw' || $varname == 'section') {
                $this->error('You tried to use a reserved variable (section or ofw)!');

                return false;
            }

            // check for other malicious stuff (php tags)
            if (strpos($varcontent, '?>') !== false) {
                $this->error('Illegal characters found in variable content');
            }
            if (strpos($varcontent, '<?') !== false) {
                $this->error('Illegal characters found in variable content');
            }

            return ['varcontent' => $varcontent, 'varname' => $varname];
        }

        /**
         * Adds a configuration output file.
         * @param string $file_name The name of the file.
         * @param string $global_scope An optional string of content that all section files should contain (it is any content before any section marker).
         * @return resource Returns the file pointer to the destination file.
         **/
        private function add_file($file_name, $global_scope = '') {
            $this->destination_files[$file_name] = fopen($file_name, 'w');
            fputs($this->destination_files[$file_name], "<?php\n".$global_scope);

            return $this->destination_files[$file_name];
        }

        /**
         * Removes a configuration output file.
         * @param string $file_name The name of the file.
         * @return boolean Returns true.
         **/
        private function remove_file($file_name) {
            fclose($this->destination_files[$file_name]);
            unset($this->destination_files[$file_name]);

            return true;
        }

        /**
         * Removes all configuration output files.
         **/
        private function remove_all_files() {
            // run through and remove all
            foreach ($this->destination_files as $file_name => $file_pointer) {
                $this->remove_file($file_name);
            }
        }

        /**
         * Write a line to all output files
         * @param string $line_content The content of the line.
         * @return integer The number of files that the output was written to.
         **/
        private function write_line($line_content) {
            // run through all the files
            $file_counter = 0;
            foreach ($this->destination_files as $file_name => $file_pointer) {
                fputs($file_pointer, $line_content);
                $file_counter++;
            }

            return $file_counter;
        }

        /**
         * Display a compile warning.
         * @param string $message Display this message.
         * @param array|bool $debug_stats If set, these debug stats will be displayed (instead of the default which is $this->debug_stats).
         */
        public function warning($message, $debug_stats = false) {
            // get the object debug_stats
            if (!is_array($debug_stats)) {
                $debug_stats = $this->debug_stats;
            }
            // send zajlib warning if in live mode, otherwise just echo
            if ($this->ofw->debug_mode) {
                echo $this->type_of_file." file compile warning: $message (file: $debug_stats[source] / line: $debug_stats[line])<br/>";
            } else {
                $this->ofw->warning("Warning during ".$this->type_of_file." file compile: $message (file: $debug_stats[source] / line: $debug_stats[line])");
            }
        }

        /**
         * Display a fatal compile error and exit.
         * @param string $message Display this message.
         * @param array|bool $debug_stats If set, these debug stats will be displayed (instead of the default which is $this->debug_stats).
         */
        public function error($message, $debug_stats = false) {
            // get the object debug_stats
            if (!is_array($debug_stats)) {
                $debug_stats = $this->debug_stats;
            }
            // send to zajlib error
            $this->ofw->error("Fatal ".$this->type_of_file." file compile error: $message (file: $debug_stats[source] / line: $debug_stats[line])");
            exit;
        }

    }
