<?php
/**
 * A standard unit test for Outlast Framework system libraries.
 **/
class OfwLibraryTest extends zajTest {

	private $hardcoded_locale;

	/**
	 * Set up stuff.
	 **/
    public function setUp(){
		// Lang setup
			// Set my default locale to en_US, but save my current one before...
				$this->hardcoded_locale = $this->zajlib->zajconf['locale_default'];
			// Now unload lib and change the hardcoded value
				unset($this->zajlib->load->loaded['library']['lang']);
				unset($this->zajlib->lang);
				$this->zajlib->zajconf['locale_default'] = 'en_US';
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
		// Finally, let's set it to some crazy unknown language (non-existant) and make sure it works with en_US default
			$this->zajlib->lang->set('xx_XX');
			$this->zajlib->lang->load('system/update');
			zajTestAssert::areIdentical('english', $this->zajlib->lang->variable->system_update_lang);
		// We're done...
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
	 * Reset stuff, cleanup.
	 **/
    public function tearDown(){
    	// Lang teardown
			// Set my default locale to en_US, but save my current one before...
				unset($this->zajlib->load->loaded['library']['lang']);
				unset($this->zajlib->lang);
				$this->zajlib->zajconf['locale_default'] = $this->hardcoded_locale;
    }


}

?>