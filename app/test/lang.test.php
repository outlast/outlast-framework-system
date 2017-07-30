<?php
/**
 * A standard unit test for Outlast Framework database changes
 **/
class OfwLangTest extends zajTest {

	private $configvars, $configsection;
	private $locales_available;
	private $locale_default;
	private $locale_set;

	/**
	 * Set up stuff.
	 **/
	public function setUp(){
		// Save all variables (for restore afterwards)
			$this->configvars = $this->zajlib->lang->variable;
			$this->configsection = $this->zajlib->lang->section;
			$this->locale_set = $this->zajlib->lang->get();
			$this->locales_available = $this->zajlib->zajconf['locale_available'];
			$this->locale_default = $this->zajlib->zajconf['locale_default'];
		// Now set locale
			$this->zajlib->zajconf['locale_default'] = 'hu_HU';
			$this->zajlib->zajconf['locale_available'] = 'fr_FR,hu_HU,en_US';
			$this->zajlib->lang->reload_locale_settings();
			$this->zajlib->lang->set('hu_HU');
	}

	/**
	 * Check lang file section loading.
	 */
	public function system_language_sections(){
		$this->zajlib->lang->load('system/fields', 'files');
		// Let's see if we loaded everything properly
		zajTestAssert::areIdentical($this->zajlib->config->variable->system_field_files_upload, $this->zajlib->lang->variable->system_field_files_upload);
		zajTestAssert::areIdentical($this->zajlib->config->variable->section->files->system_field_files_upload, $this->zajlib->lang->variable->system_field_files_upload);
		zajTestAssert::areIdentical("Fájlok feltöltése", $this->zajlib->lang->variable->system_field_files_upload);
		zajTestAssert::areIdentical($this->zajlib->config->variable->section->files->system_field_files_upload, $this->zajlib->lang->section->files->system_field_files_upload);
	}


    /**
     * Test locale templates
     */
    public function system_template_variations(){
        $this->zajlib->lang->set('hu_HU');
        $returned_content = $this->zajlib->template->show('system/test/test_locale.html', false, true);
        zajTestAssert::areIdentical('Hungarian.', $returned_content);

        $this->zajlib->lang->set('fr_FR');
        $returned_content = $this->zajlib->template->show('system/test/test_locale.html', false, true);
        zajTestAssert::areIdentical('Default locale.', $returned_content);

        // @todo {% extends %} not yet supported!
        //$this->zajlib->lang->set('en_US');
        //$returned_content = $this->zajlib->template->show('system/test/test_locale_extends.html', false, true);
        //zajTestAssert::areIdentical('English. With more.', $returned_content);
    }
	/**
	 * Check if auto loading works.
	 */
	public function system_language_auto(){
		// Get my current
			$tld = $this->zajlib->tld;
			$subdomain = $this->zajlib->subdomain;
			unset($_GET['locale']);
			unset($_GET['disable_locale_cookie']);
			unset($_COOKIE['ofw_locale']);
		// No setting means that default is set
			$this->zajlib->tld = 'com';
			$this->zajlib->subdomain = 'www';
			$setting = $this->zajlib->lang->auto();
			zajTestAssert::areIdentical($this->zajlib->lang->get_default_locale(), $setting);
		// Set my tld (should be stronger than query string)
			$this->zajlib->tld = 'hu';
			$setting = $this->zajlib->lang->auto();
			zajTestAssert::areIdentical('hu_HU', $setting);
		// Set my subdomain (should be stronger than tld)
			$this->zajlib->subdomain = 'en';
			$setting = $this->zajlib->lang->auto();
			zajTestAssert::areIdentical('en_US', $setting);
		// Set my query string (should be strong than tld or subdomain)
			$_GET['locale'] = 'fr_FR';
			$setting = $this->zajlib->lang->auto();
			zajTestAssert::areIdentical('fr_FR', $setting);
		// Reset tld and subdomain and other cleanup
			$this->zajlib->subdomain = $subdomain;
			$this->zajlib->tld = $tld;
	}


	/**
	 * Check if certain fields exist.
	 */
	public function system_language_file_variables(){
	    // Verify app level lang
        $my_files = $this->zajlib->file->get_files('app/lang/', true);
        foreach($my_files as $f){
            $file = str_ireplace('app/lang/', '', $this->zajlib->file->get_relative_path($f));
            $fdata = explode('.', $file);
            // Check for old data
            if(strlen($fdata[1]) < 5) $this->zajlib->test->notice("Found old language file format: ".$file);
            else{
                $file = trim($fdata[0], '/');
                $this->verify_single_language_file($file);
            }
        }

		// Get all of the plugins (local lang files are in _project plugin)
		foreach($this->zajlib->plugin->get_plugins('app') as $plugin){
			$my_files = $this->zajlib->file->get_files('plugins/'.$plugin.'/lang/', true);
			foreach($my_files as $f){
				$file = str_ireplace('plugins/'.$plugin.'/lang/', '', $this->zajlib->file->get_relative_path($f));
				$fdata = explode('.', $file);
				// Check for old data
				if(strlen($fdata[1]) < 5) $this->zajlib->test->notice("Found old language file format: ".$file);
				else{
					$file = trim($fdata[0], '/');
					$this->verify_single_language_file($file);
				}
			}
		}
		// Get all of the plugins (local lang files are in _project plugin)
		foreach($this->zajlib->plugin->get_plugins('system') as $plugin){
			$my_files = $this->zajlib->file->get_files('system/plugins/'.$plugin.'/lang/', true);
			foreach($my_files as $f){
				$file = str_ireplace('system/plugins/'.$plugin.'/lang/', '', $this->zajlib->file->get_relative_path($f));
				$fdata = explode('.', $file);
				// Check for old data
				if(strlen($fdata[1]) < 5) $this->zajlib->test->notice("Found old language file format: ".$file);
				else{
					$file = trim($fdata[0], '/');
					$this->verify_single_language_file($file);
				}
			}
		}
	}

	/**
	 * This check if a specific language file is ok in all languages.
	 * @param string $name The specific lang file.
	 * @return void
	 */
	private function verify_single_language_file($name){
		// Clear lang variable
		$this->zajlib->lang->reset_variables();
		// Load up a language file explicitly for default lang
		$default_locale = $this->zajlib->lang->get_default_locale();
		$res = $this->zajlib->lang->load($name.'.'.$default_locale.'.lang.ini', false, false, false);
		if(!$res) $this->zajlib->test->notice("<strong>Could not find default locale lang file!</strong> Not having lang files for default locale may cause fatal errors. We could not find this one: $name.$default_locale.lang.ini.");
		else{
			$default_array = (array) $this->zajlib->lang->variable;
			// Load up a language file explicitly
			foreach($this->zajlib->lang->get_locales() as $locale){
				if($locale != $default_locale){
					$this->zajlib->lang->reset_variables();
					$file = $this->zajlib->lang->load($name.'.'.$locale.'.lang.ini', false, false, false);
					if($file){
						$my_array = (array) $this->zajlib->lang->variable;
						$diff_keys = array_diff_key($default_array, $my_array);
						if(count($diff_keys) > 0){
							$this->zajlib->test->notice("Not all translations from $default_locale found in $locale ($name). Missing the following keys: ".join(', ', array_keys($diff_keys)));
						}
						$rev_diff_keys = array_diff_key($my_array, $default_array);
						if(count($rev_diff_keys) > 0){
							$this->zajlib->test->notice("Some translations in $locale are not found in the default $default_locale locale ($name). Missing the following keys: ".join(', ', array_keys($rev_diff_keys)));
						}
					}

				}
			}
		}
	}


	/**
	 * Reset stuff, cleanup.
	 **/
	public function tearDown(){
		// Reset locale stuff
			$this->zajlib->zajconf['locale_default'] = $this->locale_default;
			$this->zajlib->zajconf['locale_available'] = $this->locales_available;
			$this->zajlib->lang->reload_locale_settings();
			$this->zajlib->lang->set($this->locale_set);

		// Clear lang variable
			$this->zajlib->lang->reset_variables();
		// Restore language variables
			$this->zajlib->lang->set_variables($this->configvars, $this->configsection);
	}

}