<?php
    /**
     * This system controller handles various callbacks.
     * @package Controller
     * @subpackage BuiltinControllers
     * @todo Review the security issues for these methods.
     * @todo Disable direct access to data folder (only through PHP).
     * @todo Fix error messages to english and/or global via lang files.
     **/

    class zajapp_system extends zajController {

        /**
         * Load method is called each time any system action is executed.
         * @todo Allow a complete disabling of this controller.
         **/
        function __load() {
            // Add disable-check here!
        }

        /**
         * Logs javascript errors to a file (if enabled)
         **/
        function javascript_error() {
            // Check if logging is enabled
            if (empty(zajLib::me()->ofwconf->jserror_log_enabled) || empty(zajLib::me()->ofwconf->jserror_log_file)) {
                return $this->ofw->ajax('not logged');
            }
            // Defaults
            if (empty($_REQUEST['line'])) {
                $_REQUEST['line'] = 0;
            }
            // Intro
            $intro = 'Javascript error @ '.date('Y.m.d. H:i:s').' ('.zajLib::me()->request->client_ip().' | '.$this->ofw->request->client_agent().')';
            // Now write to file
            $errordata = "\n".$_REQUEST['message'].' in file '.$_REQUEST['url'].' on line '.$_REQUEST['line'];
            $errordata .= "\nPage: ".$_REQUEST['location']."\n\n";
            // Now write to javascript error log
            file_put_contents(zajLib::me()->ofwconf->jserror_log_file, $intro.$errordata, FILE_APPEND);

            // Return ok
            return $this->ofw->ajax('logged');
        }

    }