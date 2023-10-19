<?php
    /**
     * Library helps Outlast Framework report nice, informative errors.
     * @author Aron Budinszky <aron@outlast.hu>
     * @version 3.0
     * @package Library
     **/

    class zajlib_error extends zajLibExtension {

        /**
         * @var int The total number of errors
         */
        private $num_of_error = 0;

        /**
         * @var string The last error's text.
         */
        private $last_error_text = '';

        /**
         * @var string The last warning's text.
         */
        private $last_warning_text = '';

        /**
         * @var string The last warning's text.
         */
        private $last_deprecated_text = '';

        /**
         * @var string The last notice's text.
         */
        private $last_notice_text = '';

        /**
         * @var bool You can disable the errors temporarily, but only during testing.
         */
        private $errors_disabled_during_test = false;


        /**
         * Send a fatal error message.
         * @param string $message The reported message.
         * @param boolean $display_to_users Display the error message to users. You can use this if the error message conveys information without exposing security issues. Typically no information is better in terms of security.
         * @return boolean Always returns false unless test is running and errors are surpressed.
         **/
        public function error($message, $display_to_users = false) {
            // Log my error
            $this->log($message, 'error', $display_to_users);
            // If tests are running and errors surpessed, let it pass
            if ($this->errors_disabled_during_test && $this->ofw->test->is_running()) {
                return true;
            }
            // Fatal error, so exit
            exit(1);
        }

        /**
         * Sends a warning message.
         * @param string $message The reported message.
         * @return boolean Always returns false unless test is running and errors are surpressed.
         **/
        public function warning($message) {
            // Log my error
            $this->log($message, 'warning');

            return false;
        }

        /**
         * Sends a deprecation message.
         * @param string $message The reported message.
         * @return boolean Always returns false unless test is running and errors are surpressed.
         **/
        public function deprecated($message) {
            // Log my error
            $this->log($message, 'deprecated');

            return false;
        }

        /**
         * Sends a notice message.
         * @param string $message The reported message.
         * @return boolean Always returns false unless test is running and errors are surpressed.
         **/
        public function notice($message) {
            // Log my error
            $this->log($message, 'notice');

            return false;
        }

        /**
         * Get the last text.
         * @param string $errorlevel Can be 'error', 'warning', or 'notice' to specify the mode of reporting.
         * @return string The last error, warning, or notice string.
         */
        public function get_last($errorlevel = 'error') {
            switch ($errorlevel) {
                case 'notice':
                    return $this->last_notice_text;
                case 'warning':
                    return $this->last_warning_text;
                case 'error':
                default:
                    return $this->last_error_text;
            }
        }

        /**
         * Set error reporting during a test.
         * @param boolean $errors_disabled_during_test If set to true, errors and warnings will not be displayed or logged.
         * @return boolean The previous errors_disabled_during_test value.
         */
        public function surpress_errors_during_test($errors_disabled_during_test) {
            // Save current value for return
            $current_value = $this->errors_disabled_during_test;
            // Set to new
            $this->errors_disabled_during_test = $errors_disabled_during_test;

            return $current_value;
        }

        /**
         * Returns the current error disable status.
         * @return boolean The current errors_disabled_during_test value.
         */
        public function are_errors_surpressed_during_test() {
            return $this->errors_disabled_during_test;
        }

        /**
         * Log the error to the database and to the screen (if in debug mode)
         * @param string $errortext
         * @param string $errorlevel Can be 'error', 'warning', or 'notice' to specify the mode of reporting.
         * @param boolean $display_to_users If set to true, the error message will display to users. This is not recommended for most errors, defaults to false.
         * @return boolean Will return true if logging was successful.
         */
        private function log($errortext, $errorlevel = 'error', $display_to_users = false) {
            // Save my text
            switch ($errorlevel) {
                case 'notice':
                    $this->last_notice_text = $errortext;
                    break;
                case 'warning':
                    $this->last_warning_text = $errortext;
                    break;
                case 'deprecated':
                    $this->last_deprecated_text = $errortext;
                    break;
                case 'error':
                default:
                    $this->last_error_text = $errortext;
                    break;
            }
            // If errors are disabled
            if ($this->errors_disabled_during_test && $this->ofw->test->is_running()) {
                return true;
            }

            // generate a backtrace
            $backtrace = debug_backtrace(false);
            // increment number of errors
            $this->num_of_error++;

            // now create array
            $error_details = [
                'errorlevel' => $errorlevel,
                'errortext'  => $errortext,
            ];
            // set first level backtrace
            if (!empty($backtrace[2])) {
                $error_details['func'] = $backtrace[2]['function'];
                $error_details['file'] = $backtrace[1]['file'];
                $error_details['line'] = $backtrace[1]['line'];
                if (!empty($backtrace[2]['class'])) {
                    $error_details['class'] = $backtrace[2]['class'];
                }
            }
            // Update error text
            $original_error_text = $errortext;

            if ($this->ofw->compile_started && isset($this->ofw->variable->ofw->tmp) && is_object($this->ofw->variable->ofw->tmp->compile_source_debug ?? null) && !empty($this->ofw->variable->ofw->tmp->compile_source_debug->line_number)) {
                $errortext .= " (error triggered from template line ".$this->ofw->variable->ofw->tmp->compile_source_debug->line_number." of ".$this->ofw->variable->ofw->tmp->compile_source_debug->file_path.")";
            } else {
                $errortext .= " (error triggered from ".$error_details['line']." of ".$error_details['file'].")";
            }

            $error_details['errortext'] = $errortext;

            // remove the first entry
            $backtrace = array_slice($backtrace, 2);

            // process backtrace (remove long classes, make human readable)
            foreach ($backtrace as $key => $element) {
                // if call user function
                if ($element['function'] == 'call_user_func_array') {
                    $element['args'] = $element['args'][0];
                }
                //remove objects from argument list
                if (!empty($element['args']) && is_array($element['args'])) {
                    $backtrace[$key]['args'] = $this->clean_backtrace($element['args']);
                }
            }
            // now serialize and set full backtrace
            //$error_details['backtrace'] = @serialize($backtrace);

            // now add to file or db
            $error_details['time_create'] = time();
            $error_details['id'] = uniqid("");

            // which protocol?
            if (zajLib::me()->https) {
                $protocol = 'https:';
            } else {
                $protocol = 'http:';
            }
            // anything POSTed?
            if (!empty($_POST)) {
                $post_data = "[POST]";
            } else {
                $post_data = "[GET]";
            }
            // is there a referer?
            if (!empty($this->ofw->request->client_referer())) {
                $referer = " [REFERER: ".$this->ofw->request->client_referer()."]";
            } else {
                $referer = " [direct]";
            }
            // are we in debug mode?
            if (zajLib::me()->debug_mode) {
                $debug_mode = " [DEBUG_MODE]";
            } else {
                $debug_mode = "";
            }
            // write to error_log
            $this->file_log("[".$this->client_ip()."] [".$protocol.zajLib::me()->fullrequest."] $post_data [Outlast Framework $errorlevel - ".$errortext."]".$referer.$debug_mode);

            // log the backtrace?
            if (zajLib::me()->ofwconf['error_log_backtrace']) {
                $this->file_log("Backtrace:\n".print_r($backtrace, true));
            }

            // only print if it is fatal error or debug mode
            if ($errorlevel == 'error' || zajLib::me()->debug_mode) {
                // if its an error, its a 500 error
                if ($errorlevel == 'error') {
                    @header('HTTP/1.1 500 Internal Server Error');
                }

                // generate error text
                if (!zajLib::me()->debug_mode) {
                    if ($display_to_users) {
                        $errortext = "Sorry, there has been a system error: ".$original_error_text;
                    } else {
                        $errortext = "Sorry, there has been a system error. The webmaster has been notified of the issue.";
                    }
                }

                // decide what to display
                $uid = $error_details['id'];
                print "<div style='border: 2px red solid; padding: 5px; font-family: Arial; font-size: 13px;'>$errortext";

                // For debug mode display full backtrace
                if (zajLib::me()->debug_mode) {
                    print " <a href='#' onclick=\"document.getElementById('error_$uid').style.display='block';\">details</a><pre id='error_$uid' style='width: 98%; font-size: 13px; border: 1px solid black; overflow: scroll; display: none;'>";
                    print_r($backtrace);
                    print "</pre>";
                }
                print "</div>";
            }

            return true;
        }

        /**
         * Get the client's IP address. Because of forwarding or clusters, this may actually be different from REMOTE_ADDR.
         * You should use the endpoint in request library. This is here because when an error occurs it can no longer load up request lib.
         * @ignore
         */
        public function client_ip() {
            // Try to determine IP
            $possible_keys = [
                'HTTP_CLIENT_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_FORWARDED',
                'HTTP_X_CLUSTER_CLIENT_IP',
                'HTTP_FORWARDED_FOR',
                'HTTP_FORWARDED',
                'REMOTE_ADDR',
            ];
            foreach ($possible_keys as $key) {
                if (array_key_exists($key, $_SERVER) === true) {
                    foreach (explode(',', $_SERVER[$key]) as $ip) {
                        return trim($ip); // just to be safe
                    }
                }
            }

            // Return empty
            return '';
        }

        /**
         * Logs the message to a file.
         * @param string $message The message to be logged.
         * @return boolean Returns true if successful, false otherwise.
         **/
        private function file_log($message) {
            // Is logging to a specific file enabled?
            if (zajLib::me()->zajconf['error_log_enabled'] && !empty(zajLib::me()->zajconf['error_log_file'])) {
                return @error_log('['.date("Y.m.d. G:i:s").'] '.$message."\n", 3,
                    zajLib::me()->zajconf['error_log_file']);
            } else {
                return @error_log($message);
            }
        }


        /**
         * Recursively cleans objects so that they are not fully displayed.
         * @param array $backtrace An array of backtrace items.
         * @return array Returns a cleaned array where objects are replaced with [Object] ObjectName
         **/
        private function clean_backtrace($backtrace) {
            foreach ($backtrace as $argkey => $arg) {
                if (is_object($arg)) {
                    $backtrace[$argkey] = '[Object] '.get_class($arg);
                }
                if (is_array($arg)) {
                    $backtrace[$argkey] = $this->clean_backtrace($arg);
                }
            }

            return $backtrace;
        }
    }