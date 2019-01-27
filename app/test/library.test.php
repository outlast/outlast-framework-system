<?php

    /**
     * A standard unit test for Outlast Framework system libraries.
     **/
    class OfwLibraryTest extends zajTest {

        private $hardcoded_locale;
        private $hardcoded_locale_available;

        /**
         * Set up stuff.
         **/
        public function setUp() {
            // Lang setup
            // Set my default locale to en_US, but save my current one before...
            $this->hardcoded_locale = $this->ofw->ofwconf['locale_default'];
            $this->hardcoded_locale_available = $this->ofw->ofwconf['locale_available'];
            // Now unload lib and change the hardcoded value
            unset($this->ofw->load->loaded['library']['lang']);
            unset($this->ofw->lang);
            $this->ofw->ofwconf['locale_default'] = 'en_US';
            $this->ofw->ofwconf['locale_available'] = 'hu_HU,en_US';
        }

        /**
         * Check array library.
         */
        public function system_library_array() {
            // Test array merge
            $narray = $this->ofw->array->merge([1, 2, 3], [4]);
            ofwTestAssert::areIdentical(count($narray), 4);
            $narray = $this->ofw->array->merge([1, 2, 3], '');
            ofwTestAssert::areIdentical(count($narray), 3);
            // Test object conversion
            $oarray = ['something' => ['key' => 'value']];
            $obj = $this->ofw->array->to_object($oarray);
            ofwTestAssert::areIdentical($obj->something->key, 'value');
        }


        /**
         * Check browser library.
         */
        public function system_library_browser() {
            /**
             * // Test that any string is retuned
             * ofwTestAssert::isString($this->ofw->browser->browser);
             * // Test a specific string (mobile friendly for ipad)
             * $data = $this->ofw->browser->get('Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A403 Safari/8536.25');
             * ofwTestAssert::areIdentical('iOS', $data->platform);
             * ofwTestAssert::isFalse($data->issyndicationreader);
             * ofwTestAssert::isTrue($data->ismobiledevice);
             * // We're done...
             **/
        }

        /**
         * Check array library.
         */
        public function system_library_cache() {
            // Just load it up and do nothing
            $this->ofw->cache;
        }

        /**
         * Check compile library.
         */
        public function system_library_compile() {
            // Just load it up and do nothing
            $this->ofw->compile;
        }

        /**
         * Check config library.
         */
        public function system_library_config() {
            // Just load it up and do nothing
            $result = $this->ofw->config->load('random_nonexistant_file', false, false, false);
            ofwTestAssert::isFalse($result);
        }

        /**
         * Check cookie library.
         */
        public function system_library_cookie() {
            // Just load it up and do nothing
            $result = $this->ofw->cookie->get('random_nonexistant_cookie');
            if (!$result) {
                $result = false;
            }
            ofwTestAssert::isFalse($result);
        }

        /**
         * Check db library.
         */
        public function system_library_db() {
            // Just load it up and do nothing
            $d = $this->ofw->db->create_session();
            ofwTestAssert::isObject($d);
        }

        /**
         * Check dom library.
         */
        public function system_library_dom() {
            // Just load it up and do nothing
            $this->ofw->dom;
        }

        /**
         * Check email library.
         */
        public function system_library_email() {
            // Test email validity
            $d = $this->ofw->email->get_named_email("Mr. Name <name@example.com>");
            ofwTestAssert::areIdentical("Mr. Name", $d->name);
            ofwTestAssert::areIdentical("name@example.com", $d->email);
            $v = $this->ofw->email->valid("asdf@example.info");
            ofwTestAssert::isTrue($v);
            $v = $this->ofw->email->valid("typical.bad.example.com");
            ofwTestAssert::isFalse($v);
        }

        /**
         * Check error library.
         */
        public function system_library_error() {
            // Just load it up and do nothing
            $this->ofw->error;
        }

        /**
         * Check export library.
         */
        public function system_library_export() {
            // Just load it up and do nothing
            $this->ofw->export;
        }

        /**
         * Check feed library.
         */
        public function system_library_feed() {
            // Just load it up and do nothing
            $this->ofw->feed;
        }

        /**
         * Check file library.
         */
        public function system_library_file() {
            // Test relative path getter
            $relpath = $this->ofw->file->get_relative_path($this->ofw->basepath.'system/app');
            ofwTestAssert::areIdentical('system/app/', $relpath);
            // Jail test for files and folders
            $error = $this->ofw->file->folder_check('/var/', '', true, false);
            ofwTestAssert::isFalse($error);
            $error = $this->ofw->file->file_check('/etc/hosts', '', false);
            ofwTestAssert::isFalse($error);
            $error = $this->ofw->file->file_check('../system', '', false);
            ofwTestAssert::isFalse($error);
            $error = $this->ofw->file->file_check('/app/view/', '', false);
            ofwTestAssert::isFalse($error);
            // Valid jail test
            $file = $this->ofw->file->folder_check($this->ofw->basepath.'system/', '', true, false);
            ofwTestAssert::areIdentical($this->ofw->basepath.'system/', $file);
            $file = $this->ofw->file->folder_check('system/', '', true, false);
            ofwTestAssert::areIdentical('system/', $file);
            // File listing check
            $files = $this->ofw->file->get_files('system/doc');
            ofwTestAssert::isArray($files);
            ofwTestAssert::isTrue(in_array($this->ofw->basepath.'system/doc/doc.php', $files));
            // Folder listing check
            $folders = $this->ofw->file->get_folders('system/');
            ofwTestAssert::isArray($folders);
            ofwTestAssert::isTrue(in_array($this->ofw->basepath.'system/doc/', $folders));
            // Test download security
            $this->ofw->error->surpress_errors_during_test(true);
            $this->ofw->file->download('/etc/shadow');
            $error = $this->ofw->error->get_last('error');
            ofwTestAssert::areIdentical('Invalid file path given. /etc/shadow', $error);
            $this->ofw->file->download($this->ofw->basepath.'index.php');
            $error = $this->ofw->error->get_last('error');
            ofwTestAssert::areIdentical('Tried to download disabled extension php', $error);
            $this->ofw->error->surpress_errors_during_test(false);
            // Test automatic filename and mime
            $download = $this->ofw->file->download('system/site/img/outlast-framework-logo.png');
            ofwTestAssert::isArray($download);
            list($file_path, $download_name, $mime_type) = $download;
            ofwTestAssert::areIdentical($this->ofw->basepath.'system/site/img/outlast-framework-logo.png',
                $file_path);
            ofwTestAssert::areIdentical('outlast-framework-logo.png', $download_name);
            ofwTestAssert::areIdentical('image/png', $mime_type);
            // Test parameters filename and mime
            $download = $this->ofw->file->download('system/site/img/outlast-framework-logo.png', 'asdf.png',
                'test-mime');
            ofwTestAssert::isArray($download);
            list($file_path, $download_name, $mime_type) = $download;
            ofwTestAssert::areIdentical($this->ofw->basepath.'system/site/img/outlast-framework-logo.png',
                $file_path);
            ofwTestAssert::areIdentical('asdf.png', $download_name);
            ofwTestAssert::areIdentical('test-mime', $mime_type);

            // Check to see if app folders methods work properly
            $files = $this->ofw->file->get_all_files_in_app_folders('test/', true);
            ofwTestAssert::isTrue(in_array('library.test.php', $files));
            $files = $this->ofw->file->get_all_files_in_app_folders('controller/', true);
            ofwTestAssert::isTrue(in_array('system/api/file.ctl.php', $files));
            $folders = $this->ofw->file->get_all_folders_in_app_folders('controller/', true);
            ofwTestAssert::isTrue(in_array('system/api/', $folders));
            $file_versions = $this->ofw->file->get_all_versions_of_file_in_app_folders('conf/category.conf.ini');
            ofwTestAssert::isTrue(in_array('system/plugins/_global/conf/category.conf.ini', $file_versions));

        }

        /**
         * Check form library.
         */
        public function system_library_form() {
            // Create some fake REQUEST vars
            $_REQUEST['something'] = "myvalue";
            $_REQUEST['something_arr']['var'] = "myvalue2";
            // Let's verify they are filled
            // Standard
            $result = $this->ofw->form->filled('something');
            ofwTestAssert::isTrue($result);
            // Array has any elements
            $result = $this->ofw->form->filled('something', 'something_arr');
            ofwTestAssert::isTrue($result);
            // Array has specific elements
            $result = $this->ofw->form->filled('something', 'something_arr[var]');
            ofwTestAssert::isTrue($result);


        }

        /**
         * Check graphics library.
         */
        public function system_library_graphics() {
            // Just load it up and do nothing
            $this->ofw->graphics;
        }

        /**
         * Check import.
         */
        public function system_library_import() {
            // Just load it up and do nothing
            $this->ofw->import;
        }

        /**
         * Check model.
         */
        public function system_library_model() {
            // Just load it up and do nothing
            $this->ofw->model;
        }

        /**
         * Check plugin.
         */
        public function system_library_plugin() {
            // Just load it up and do nothing
            $this->ofw->plugin;
        }

        /**
         * Check request.
         */
        public function system_library_request() {
            // Check is_ajax
            $r = $this->ofw->request->is_ajax();
            ofwTestAssert::isFalse($r);
            // Now try when it is true
            $s = $_SERVER['HTTP_X_REQUESTED_WITH'];
            $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
            $r = $this->ofw->request->is_ajax();
            ofwTestAssert::isTrue($r);
            // Clean up
            $_SERVER['HTTP_X_REQUESTED_WITH'] = $s;
        }

        /**
         * Check sandbox.
         */
        public function system_library_sandbox() {
            // Just load it up and do nothing
            $this->ofw->sandbox;
        }

        /**
         * Check security.
         */
        public function system_library_security() {
            // Test ip in range
            $res = $this->ofw->security->ip_in_range('199.59.148.209', '199.59.148.*');
            ofwTestAssert::isTrue($res);
            $res = $this->ofw->security->ip_in_range('199.59.148.209', ['199.59.148.123', '199.59.148.209']);
            ofwTestAssert::isTrue($res);
            $res = $this->ofw->security->ip_in_range('199.59.148.209', ['127.0.0.1']);
            ofwTestAssert::isFalse($res);
        }

        /**
         * Check text file.
         */
        public function system_library_text() {
            // Should add it
            $s1 = '2';
            $s2 = '3';
            $res = $this->ofw->text->add($s1, $s2);
            ofwTestAssert::areIdentical('5', $res);

            // Should add it
            $s1 = 2.5;
            $s2 = '3';
            $res = $this->ofw->text->add($s1, $s2);
            $locale_info = localeconv();

            ofwTestAssert::areIdentical('5'.$locale_info['decimal_point'].'5', $res);

            // Should concat it
            $s1 = 'This is a ';
            $s2 = '3';
            $res = $this->ofw->text->add($s1, $s2);
            ofwTestAssert::areIdentical('This is a 3', $res);

            // Test escaping
            $s1 = 'This is a string with \'quotes"';
            $res = $this->ofw->text->escape($s1, 'javascript');
            ofwTestAssert::areIdentical('This is a string with \\\'quotes\"', $res);

            // Just load it up and do nothing
            $this->ofw->text;
        }

        /**
         * Check url file.
         */
        public function system_library_url() {
            // Let's test some valid urls
            // Simple urls
            $r = $this->ofw->url->valid('http://www.google.com/');
            ofwTestAssert::isTrue($r);
            $r = $this->ofw->url->valid('http://www.example.com/something.php');
            ofwTestAssert::isTrue($r);
            $r = $this->ofw->url->valid('http://www.example.com/something.php?example=sdf%20asd');
            ofwTestAssert::isTrue($r);
            // HTTP with user/pass, port, and query string
            $r = $this->ofw->url->valid('http://ofw:ofw@example.com:1234/asdf/asdf/?example=true&something=false');
            ofwTestAssert::isTrue($r);
            // A complex url with multiple urlencode levels
            $r = $this->ofw->url->valid('https://www.facebook.com/dialog/oauth?client_id=12123123123345456890&redirect_uri=%2F%2Flocal.asdf.com%2Fapp.asdf.com%2Fapp%2F%3Fid%3D51e7ab32ead09%26page%3Dauthenticate%26redirect%3Dapp%252F%253Fid%253D51e7ab32ead09%2526%2526page%253Df');
            ofwTestAssert::isTrue($r);
            // A url with accent marks in query string
            $r = $this->ofw->url->valid('http://www.google.com/?search=példa');
            ofwTestAssert::isTrue($r);
            // A url with accent marks in address
            $r = $this->ofw->url->valid('http://www.áccént.com/');
            ofwTestAssert::isTrue($r);
            // Some localhost, and ip examples
            $r = $this->ofw->url->valid('http://localhost/asdf/example.php');
            ofwTestAssert::isTrue($r);
            $r = $this->ofw->url->valid('http://127.0.0.1/asdf/example.php');
            ofwTestAssert::isTrue($r);
            $r = $this->ofw->url->valid('http://127.0.0.1:1000/asdf/example.php');
            ofwTestAssert::isTrue($r);
            // Redirect testing with validation
            $r = $this->ofw->url->valid('http://example.com/?message=Děkujeme');
            ofwTestAssert::isTrue($r);
            $r = $this->ofw->redirect('http://example.com/?message=Děkujeme');
            ofwTestAssert::areIdentical('http://example.com/?message=Děkujeme', $r);
            $r = $this->ofw->redirect('/example/?message=Děkujeme');
            ofwTestAssert::areIdentical($this->ofw->baseurl.'/example/?message=Děkujeme', $r);
            // Test with spaces in url (invalid) and with spaces in query string (valid)
            // Although invalid, this will be ok with current validation
            $r = $this->ofw->url->valid('http://ex ample.com/');
            ofwTestAssert::isTrue($r);
            // This is the more strict version
            $r = $this->ofw->url->valid('http://ex ample.com/', false);
            ofwTestAssert::isFalse($r);
            // Now let's check space in query string
            $r = $this->ofw->url->valid('http://example.com/?test=Spaces%20here%20are%20ok', false);
            ofwTestAssert::isTrue($r);
            $r = $this->ofw->url->valid('http://example.com/?test=Spaces here are ok');
            ofwTestAssert::isTrue($r);
            // Protocol-independent is valid
            $r = $this->ofw->url->valid('//localhost/asdf/example.php');
            ofwTestAssert::isTrue($r);
            // Let's test some invalid urls
            $r = $this->ofw->url->valid('/asdf/example.php');
            ofwTestAssert::isFalse($r);
            $r = $this->ofw->url->valid('chat://asdf/asdf/example.php');
            ofwTestAssert::isFalse($r);

            // Let's try path calc
            $r = $this->ofw->url->get_requestpath('https://www.google.com/');
            ofwTestAssert::areIdentical('/', $r);
            $r = $this->ofw->url->get_requestpath('https://www.google.com/asdf');
            ofwTestAssert::areIdentical('asdf/', $r);
            $r = $this->ofw->url->get_requestpath('https://www.google.com/asdf///?test=sdf');
            ofwTestAssert::areIdentical('asdf/', $r);
            $r = $this->ofw->url->get_requestpath('asdf//test');
            ofwTestAssert::areIdentical('asdf/test/', $r);

            ofwTestAssert::areIdentical($this->ofw->app.$this->ofw->mode, $this->ofw->requestpath);
        }


        /**
         * Check language library.
         **/
        public function system_library_language() {
            // Make sure that en_US is set and returned as default
            ofwTestAssert::areIdentical('en_US', $this->ofw->lang->get_default_locale());
            // So now, let's set the current locale to hu_HU and ensure we get the proper result
            $this->ofw->lang->set('hu_HU');
            $this->ofw->lang->load('system/update');
            ofwTestAssert::areIdentical('magyar', $this->ofw->lang->variable->system_update_lang);

            // Let's set it to some crazy unknown language (non-existant) and make sure it works with en_US default
            // Disable errors first
            $this->ofw->error->surpress_errors_during_test(true);
            // Run
            $this->ofw->lang->set('xx_XX');
            $this->ofw->lang->load('system/update');
            ofwTestAssert::areIdentical('english', $this->ofw->lang->variable->system_update_lang);
            // Enable errors
            $this->ofw->error->surpress_errors_during_test(false);

            // We're done...
        }

        /**
         * Reset stuff, cleanup.
         **/
        public function tearDown() {
            // Lang teardown
            // Set my default locale to en_US, but save my current one before...
            unset($this->ofw->load->loaded['library']['lang']);
            unset($this->ofw->lang);
            $this->ofw->ofwconf['locale_default'] = $this->hardcoded_locale;
            $this->ofw->ofwconf['locale_available'] = $this->hardcoded_locale_available;
        }
    }