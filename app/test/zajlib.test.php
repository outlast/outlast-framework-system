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
		// Create
			$z = new zajLib('/', $this->zajlib->zajconf);
			zajTestAssert::areIdentical('http:', $z->protocol);
			zajTestAssert::areIdentical('example.com', $z->domain);
			zajTestAssert::areIdentical('', $z->subdomain);
			zajTestAssert::areIdentical('//www.example.com/', $z->baseurl);
			zajTestAssert::areIdentical('//www.example.com/update/test/', $z->fullurl);

		// Set some fake info
			$_SERVER['OFW_BASEURL'] = 'https://test.example.com/asdf/';
		// Create
			$z = new zajLib('/', $this->zajlib->zajconf);
			zajTestAssert::areIdentical('https:', $z->protocol);
			zajTestAssert::areIdentical('example.com', $z->domain);
			zajTestAssert::areIdentical('test', $z->subdomain);
			zajTestAssert::areIdentical('//test.example.com/asdf/', $z->baseurl);
			zajTestAssert::areIdentical('//test.example.com/asdf/update/test/', $z->fullurl);
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