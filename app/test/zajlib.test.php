<?php
/**
 * A standard unit test for Outlast Framework system libraries.
 **/
class OfwZajlibTest extends zajTest {

	private $server_original;
	private $zajlib_original;

	/**
	 * Set up stuff.
	 **/
    public function setUp(){
    	// Save
    		$this->server_original = $_SERVER;
    		$this->zajlib_original = $this->zajlib;

    }

	/**
	 * Check zajlib baseurl detection.
	 */
	public function baseurl_detection(){
		// Set some fake info
			$_SERVER['OFW_BASEURL'] = 'http://www.example.com/';
			$_SERVER['HTTPS'] = 'off';
		// Create
			$z = new zajLib('/', $this->zajlib->zajconf);
			zajTestAssert::areIdentical('http:', $z->protocol);
			zajTestAssert::areIdentical('example.com', $z->domain);
			zajTestAssert::areIdentical('', $z->subdomain);
			zajTestAssert::areIdentical('//www.example.com/', $z->baseurl);
			//@todo find a solution to check fullurl
			//zajTestAssert::areIdentical('//www.example.com/update/test/', $z->fullurl);

		// Set some fake info again
			$_SERVER['OFW_BASEURL'] = 'https://test.example.com/asdf/';
			$_SERVER['HTTPS'] = 'on';
		// Create
			$z = new zajLib('/', $this->zajlib->zajconf);
			zajTestAssert::areIdentical('https:', $z->protocol);
			zajTestAssert::areIdentical('example.com', $z->domain);
			zajTestAssert::areIdentical('test', $z->subdomain);
			zajTestAssert::areIdentical('//test.example.com/asdf/', $z->baseurl);
			//zajTestAssert::areIdentical('//test.example.com/asdf/update/test/', $z->fullurl);
	}

	/**
	 * Check load methods.
	 */
	public function load_libs_and_models(){
		// Try to load a non-existant library with fail as false
		$result = $this->zajlib->load->library('this_library_will_not_exist', false, false);
		zajTestAssert::isFalse($result);

		// Try to load a non-existant library with fail as true (default)
		$this->zajlib->error->surpress_errors_during_test(true);
		$result = $this->zajlib->load->library('this_library_will_not_exist');
		$last_error = $this->zajlib->error->get_last('error');
    	zajTestAssert::areIdentical("Tried to auto-load library (this_library_will_not_exist), but failed: library file not found!", $last_error);
		$this->zajlib->error->surpress_errors_during_test(false);
		zajTestAssert::isFalse($result);


		// Try to load an existing library
		$result = $this->zajlib->load->library('text');
		zajTestAssert::isObject($result);

		// Try to load a non-existant model
		$result = $this->zajlib->load->model('this_model_will_not_exist', false, false);
		zajTestAssert::isFalse($result);
	}

	/**
	 * Reset stuff, cleanup.
	 **/
    public function tearDown(){
    	// Restore
    		$_SERVER = $this->server_original;
    		$this->zajlib = $this->zajlib_original;

    }
}