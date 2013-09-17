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
    public function setUp(){
		// Lang setup
			// Set my default locale to en_US, but save my current one before...
				$this->hardcoded_locale = $this->zajlib->zajconf['locale_default'];
				$this->hardcoded_locale_available = $this->zajlib->zajconf['locale_available'];
			// Now unload lib and change the hardcoded value
				unset($this->zajlib->load->loaded['library']['lang']);
				unset($this->zajlib->lang);
				$this->zajlib->zajconf['locale_default'] = 'en_US';
				$this->zajlib->zajconf['locale_available'] = 'hu_HU,en_US';
    }

	/**
	 * Check array library.
	 */
	public function system_library_array(){
		// Test array merge
			$narray = $this->zajlib->array->merge(array(1, 2, 3), array(4));
			zajTestAssert::areIdentical(count($narray), 4);
			$narray = $this->zajlib->array->merge(array(1, 2, 3), '');
			zajTestAssert::areIdentical(count($narray), 3);
		// Test object conversion
			$oarray = array('something'=>array('key'=>'value'));
			$obj = $this->zajlib->array->to_object($oarray);
			zajTestAssert::areIdentical($obj->something->key, 'value');
	}


	/**
	 * Check browser library.
	 */
	public function system_library_browser(){
		// Test that any string is retuned
			zajTestAssert::isString($this->zajlib->browser->browser);
		// Test a specific string (mobile friendly for ipad)
			$data = $this->zajlib->browser->get('Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A403 Safari/8536.25');
			zajTestAssert::areIdentical('iOS', $data->platform);
			zajTestAssert::isFalse($data->issyndicationreader);
			zajTestAssert::isTrue($data->ismobiledevice);
		// We're done...
	}

	/**
	 * Check array library.
	 */
	public function system_library_cache(){
		// Just load it up and do nothing
		$this->zajlib->cache;
	}

	/**
	 * Check compile library.
	 */
	public function system_library_compile(){
		// Just load it up and do nothing
		$this->zajlib->compile;
	}

	/**
	 * Check config library.
	 */
	public function system_library_config(){
		// Just load it up and do nothing
			$result = $this->zajlib->config->load('random_nonexistant_file', false, false, false);
			zajTestAssert::isFalse($result);
	}

	/**
	 * Check cookie library.
	 */
	public function system_library_cookie(){
		// Just load it up and do nothing
		$result = $this->zajlib->cookie->get('random_nonexistant_cookie');
		if(!$result) $result = false;
		zajTestAssert::isFalse($result);
	}

	/**
	 * Check db library.
	 */
	public function system_library_db(){
		// Just load it up and do nothing
		$d = $this->zajlib->db->create_session();
		zajTestAssert::isObject($d);
	}

	/**
	 * Check dom library.
	 */
	public function system_library_dom(){
		// Just load it up and do nothing
		$this->zajlib->dom;
	}

	/**
	 * Check email library.
	 */
	public function system_library_email(){
		// Test email validity
		$d = $this->zajlib->email->get_named_email("Mr. Name <name@example.com>");
		zajTestAssert::areIdentical("Mr. Name", $d->name);
		zajTestAssert::areIdentical("name@example.com", $d->email);
		$v = $this->zajlib->email->valid("asdf@example.info");
		zajTestAssert::isTrue($v);
		$v = $this->zajlib->email->valid("typical.bad.example.com");
		zajTestAssert::isFalse($v);
	}

	/**
	 * Check error library.
	 */
	public function system_library_error(){
		// Just load it up and do nothing
		$this->zajlib->error;
	}

	/**
	 * Check export library.
	 */
	public function system_library_export(){
		// Just load it up and do nothing
		$this->zajlib->export;
	}

	/**
	 * Check feed library.
	 */
	public function system_library_feed(){
		// Just load it up and do nothing
		$this->zajlib->feed;
	}

	/**
	 * Check file library.
	 */
	public function system_library_file(){
		// Test relative path getter
			$relpath = $this->zajlib->file->get_relative_path($this->zajlib->basepath.'system/app');
			zajTestAssert::areIdentical('system/app/', $relpath);
		// Jail test for files and folders
			$error = $this->zajlib->file->folder_check('/var/', '', true, false);
			zajTestAssert::isFalse($error);
			$error = $this->zajlib->file->file_check('/etc/hosts', '', false);
			zajTestAssert::isFalse($error);
			$error = $this->zajlib->file->file_check('../system', '', false);
			zajTestAssert::isFalse($error);
			$error = $this->zajlib->file->file_check('/app/view/', '', false);
			zajTestAssert::isFalse($error);
		// Valid jail test
			$file = $this->zajlib->file->folder_check($this->zajlib->basepath.'system/', '', true, false);
			zajTestAssert::areIdentical($this->zajlib->basepath.'system/', $file);
			$file = $this->zajlib->file->folder_check('system/', '', true, false);
			zajTestAssert::areIdentical('system/', $file);
		// File listing check
			$files = $this->zajlib->file->get_files('system/doc');
			zajTestAssert::isArray($files);
			zajTestAssert::isTrue(in_array($this->zajlib->basepath.'system/doc//doc.php', $files));
		// Folder listing check
			$folders = $this->zajlib->file->get_folders('system/');
			zajTestAssert::isArray($folders);
			zajTestAssert::isTrue(in_array($this->zajlib->basepath.'system//doc/', $folders));
	}

	/**
	 * Check form library.
	 */
	public function system_library_form(){
		// Create some fake REQUEST vars
			$_REQUEST['something'] = "myvalue";
			$_REQUEST['something_arr']['var'] = "myvalue2";
		// Let's verify they are filled
			// Standard
			$result = $this->zajlib->form->filled('something');
			zajTestAssert::isTrue($result);
			// Array has any elements
			$result = $this->zajlib->form->filled('something', 'something_arr');
			zajTestAssert::isTrue($result);
			// Array has specific elements
			$result = $this->zajlib->form->filled('something', 'something_arr[var]');
			zajTestAssert::isTrue($result);



	}

	/**
	 * Check graphics library.
	 */
	public function system_library_graphics(){
		// Just load it up and do nothing
		$this->zajlib->graphics;
	}

	/**
	 * Check import.
	 */
	public function system_library_import(){
		// Just load it up and do nothing
		$this->zajlib->import;
	}

	/**
	 * Check model.
	 */
	public function system_library_model(){
		// Just load it up and do nothing
		$this->zajlib->model;
	}

	/**
	 * Check plugin.
	 */
	public function system_library_plugin(){
		// Just load it up and do nothing
		$this->zajlib->plugin;
	}

	/**
	 * Check request.
	 */
	public function system_library_request(){
		// Just load it up and do nothing
		$this->zajlib->request;
	}

	/**
	 * Check sandbox.
	 */
	public function system_library_sandbox(){
		// Just load it up and do nothing
		$this->zajlib->sandbox;
	}

	/**
	 * Check security.
	 */
	public function system_library_security(){
		// Just load it up and do nothing
		$this->zajlib->security;
	}

	/**
	 * Check text file.
	 */
	public function system_library_text(){
		// Just load it up and do nothing
		$this->zajlib->text;
	}

	/**
	 * Check url file.
	 */
	public function system_library_url(){
		// Let's test some valid urls
			// Simple urls
				$r = $this->zajlib->url->valid('http://www.google.com/');
				zajTestAssert::isTrue($r);
				$r = $this->zajlib->url->valid('http://www.example.com/something.php');
				zajTestAssert::isTrue($r);
				$r = $this->zajlib->url->valid('http://www.example.com/something.php?example=sdf%20asd');
				zajTestAssert::isTrue($r);
			// HTTP with user/pass, port, and query string
				$r = $this->zajlib->url->valid('http://ofw:ofw@example.com:1234/asdf/asdf/?example=true&something=false');
				zajTestAssert::isTrue($r);
			// A complex url with multiple urlencode levels
				$r = $this->zajlib->url->valid('https://www.facebook.com/dialog/oauth?client_id=12123123123345456890&redirect_uri=%2F%2Flocal.asdf.com%2Fapp.asdf.com%2Fapp%2F%3Fid%3D51e7ab32ead09%26page%3Dauthenticate%26redirect%3Dapp%252F%253Fid%253D51e7ab32ead09%2526%2526page%253Df');
				zajTestAssert::isTrue($r);
			// A url with accent marks in query string
				$r = $this->zajlib->url->valid('http://www.google.com/?search=példa');
				zajTestAssert::isTrue($r);
			// A url with accent marks in address
				$r = $this->zajlib->url->valid('http://www.áccént.com/');
				zajTestAssert::isTrue($r);
			// Some localhost, and ip examples
				$r = $this->zajlib->url->valid('http://localhost/asdf/example.php');
				zajTestAssert::isTrue($r);
				$r = $this->zajlib->url->valid('http://127.0.0.1/asdf/example.php');
				zajTestAssert::isTrue($r);
				$r = $this->zajlib->url->valid('http://127.0.0.1:1000/asdf/example.php');
				zajTestAssert::isTrue($r);
			// Redirect testing with validation
				$r = $this->zajlib->url->valid('http://example.com/?message=Děkujeme');
				zajTestAssert::isTrue($r);
				$r = $this->zajlib->redirect('http://example.com/?message=Děkujeme');
				zajTestAssert::areIdentical('http://example.com/?message=Děkujeme', $r);
				$r = $this->zajlib->redirect('/example/?message=Děkujeme');
				zajTestAssert::areIdentical($this->zajlib->baseurl.'/example/?message=Děkujeme', $r);
			// Test with spaces in url (invalid) and with spaces in query string (valid)
				// Although invalid, this will be ok with current validation
				$r = $this->zajlib->url->valid('http://ex ample.com/');
				zajTestAssert::isTrue($r);
				// This is the more strict version
				$r = $this->zajlib->url->valid('http://ex ample.com/', false);
				zajTestAssert::isFalse($r);
				// Now let's check space in query string
				$r = $this->zajlib->url->valid('http://example.com/?test=Spaces%20here%20are%20ok', false);
				zajTestAssert::isTrue($r);
				$r = $this->zajlib->url->valid('http://example.com/?test=Spaces here are ok');
				zajTestAssert::isTrue($r);
		// Let's test some invalid urls
				$r = $this->zajlib->url->valid('/asdf/example.php');
				zajTestAssert::isFalse($r);
				$r = $this->zajlib->url->valid('chat://asdf/asdf/example.php');
				zajTestAssert::isFalse($r);
				$r = $this->zajlib->url->valid('//localhost/asdf/example.php');
				zajTestAssert::isFalse($r);
	}


	/**
	 * Check language library.
	 **/
	public function system_library_language(){
		// Make sure that en_US is set and returned as default
		zajTestAssert::areIdentical('en_US', $this->zajlib->lang->get_default_locale());
		// So now, let's set the current locale to hu_HU and ensure we get the proper result
		$this->zajlib->lang->set('hu_HU');
		$this->zajlib->lang->load('system/update');
		zajTestAssert::areIdentical('magyar', $this->zajlib->lang->variable->system_update_lang);

		// Let's set it to some crazy unknown language (non-existant) and make sure it works with en_US default
		$this->zajlib->lang->set('xx_XX');
		$this->zajlib->lang->load('system/update');
		zajTestAssert::areIdentical('english', $this->zajlib->lang->variable->system_update_lang);

		// We're done...
	}

	/**
	 * Reset stuff, cleanup.
	 **/
    public function tearDown(){
    	// Lang teardown
			// Set my default locale to en_US, but save my current one before...
				unset($this->zajlib->load->loaded['library']['lang']);
				unset($this->zajlib->lang);
				$this->zajlib->zajconf['locale_default'] = $this->hardcoded_locale;
				$this->zajlib->zajconf['locale_available'] = $this->hardcoded_locale_available;
    }
}