<?php
/**
 * A standard unit test for Outlast Framework database changes
 **/
class OfwLangTest extends ofwTest {

	private $configvars, $configsection;
	private $locales_available;
	private $locale_default;
	private $locale_set;

	/**
	 * Set up stuff.
	 **/
	public function setUp(){
		// Save all variables (for restore afterwards)
			$this->configvars = $this->ofw->lang->variable;
			$this->configsection = $this->ofw->lang->section;
			$this->locale_set = $this->ofw->lang->get();
			$this->locales_available = $this->ofw->ofwconf['locale_available'];
			$this->locale_default = $this->ofw->ofwconf['locale_default'];
		// Now set locale
			$this->ofw->ofwconf['locale_default'] = 'hu_HU';
			$this->ofw->ofwconf['locale_available'] = 'fr_FR,hu_HU,en_US';
			$this->ofw->lang->reload_locale_settings();
			$this->ofw->lang->set('hu_HU');
	}

	/**
	 * Check lang file section loading.
	 */
	public function system_language_sections(){
		$this->ofw->lang->load('system/fields', 'files');
		// Let's see if we loaded everything properly
		ofwTestAssert::areIdentical($this->ofw->config->variable->system_field_files_upload, $this->ofw->lang->variable->system_field_files_upload);
		ofwTestAssert::areIdentical($this->ofw->config->variable->section->files->system_field_files_upload, $this->ofw->lang->variable->system_field_files_upload);
		ofwTestAssert::areIdentical("Fájlok feltöltése", $this->ofw->lang->variable->system_field_files_upload);
		ofwTestAssert::areIdentical($this->ofw->config->variable->section->files->system_field_files_upload, $this->ofw->lang->section->files->system_field_files_upload);
	}


    /**
     * Test locale templates
     */
    public function system_template_variations(){
        $this->ofw->lang->set('hu_HU');
        $returned_content = $this->ofw->template->show('system/test/test_locale.html', false, true);
        ofwTestAssert::areIdentical('Hungarian.', $returned_content);

        $this->ofw->lang->set('fr_FR');
        $returned_content = $this->ofw->template->show('system/test/test_locale.html', false, true);
        ofwTestAssert::areIdentical('Default locale.', $returned_content);

        // @todo {% extends %} not yet supported!
        //$this->ofw->lang->set('en_US');
        //$returned_content = $this->ofw->template->show('system/test/test_locale_extends.html', false, true);
        //ofwTestAssert::areIdentical('English. With more.', $returned_content);
    }
	/**
	 * Check if auto loading works.
	 */
	public function system_language_auto(){
		// Get my current
			$tld = $this->ofw->tld;
			$subdomain = $this->ofw->subdomain;
			unset($_GET['locale']);
			unset($_GET['disable_locale_cookie']);
			unset($_COOKIE['ofw_locale']);
		// No setting means that default is set
			$this->ofw->tld = 'com';
			$this->ofw->subdomain = 'www';
			$setting = $this->ofw->lang->auto();
			ofwTestAssert::areIdentical($this->ofw->lang->get_default_locale(), $setting);
		// Set my tld (should be stronger than query string)
			$this->ofw->tld = 'hu';
			$setting = $this->ofw->lang->auto();
			ofwTestAssert::areIdentical('hu_HU', $setting);
		// Set my subdomain (should be stronger than tld)
			$this->ofw->subdomain = 'en';
			$setting = $this->ofw->lang->auto();
			ofwTestAssert::areIdentical('en_US', $setting);
		// Set my query string (should be strong than tld or subdomain)
			$_GET['locale'] = 'fr_FR';
			$setting = $this->ofw->lang->auto();
			ofwTestAssert::areIdentical('fr_FR', $setting);
		// Reset tld and subdomain and other cleanup
			$this->ofw->subdomain = $subdomain;
			$this->ofw->tld = $tld;
	}


	/**
	 * Check if certain fields exist.
	 */
	public function system_language_file_variables(){
	    // Verify app level lang
        $my_files = $this->ofw->file->get_files('app/lang/', true);
        foreach($my_files as $f){
            $file = str_ireplace('app/lang/', '', $this->ofw->file->get_relative_path($f));
            $fdata = explode('.', $file);
            // Check for old data
            if(strlen($fdata[1]) < 5) $this->ofw->test->notice("Found old language file format: ".$file);
            else{
                $file = trim($fdata[0], '/');
                $this->verify_single_language_file($file);
            }
        }

		// Get all of the plugins (local lang files are in _project plugin)
		foreach($this->ofw->plugin->get_plugins('app') as $plugin){
			$my_files = $this->ofw->file->get_files('plugins/'.$plugin.'/lang/', true);
			foreach($my_files as $f){
				$file = str_ireplace('plugins/'.$plugin.'/lang/', '', $this->ofw->file->get_relative_path($f));
				$fdata = explode('.', $file);
				// Check for old data
				if(strlen($fdata[1]) < 5) $this->ofw->test->notice("Found old language file format: ".$file);
				else{
					$file = trim($fdata[0], '/');
					$this->verify_single_language_file($file);
				}
			}
		}
		// Get all of the plugins (local lang files are in _project plugin)
		foreach($this->ofw->plugin->get_plugins('system') as $plugin){
			$my_files = $this->ofw->file->get_files('system/plugins/'.$plugin.'/lang/', true);
			foreach($my_files as $f){
				$file = str_ireplace('system/plugins/'.$plugin.'/lang/', '', $this->ofw->file->get_relative_path($f));
				$fdata = explode('.', $file);
				// Check for old data
				if(strlen($fdata[1]) < 5) $this->ofw->test->notice("Found old language file format: ".$file);
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
		$this->ofw->lang->reset_variables();
		// Load up a language file explicitly for default lang
		$default_locale = $this->ofw->lang->get_default_locale();
		$res = $this->ofw->lang->load($name.'.'.$default_locale.'.lang.ini', false, false, false);
		if(!$res) $this->ofw->test->notice("<strong>Could not find default locale lang file!</strong> Not having lang files for default locale may cause fatal errors. We could not find this one: $name.$default_locale.lang.ini.");
		else{
			$default_array = (array) $this->ofw->lang->variable;
			// Load up a language file explicitly
			foreach($this->ofw->lang->get_locales() as $locale){
				if($locale != $default_locale){
					$this->ofw->lang->reset_variables();
					$file = $this->ofw->lang->load($name.'.'.$locale.'.lang.ini', false, false, false);
					if($file){
						$my_array = (array) $this->ofw->lang->variable;
						$diff_keys = array_diff_key($default_array, $my_array);
						if(count($diff_keys) > 0){
							$this->ofw->test->notice("Not all translations from $default_locale found in $locale ($name). Missing the following keys: ".join(', ', array_keys($diff_keys)));
						}
						$rev_diff_keys = array_diff_key($my_array, $default_array);
						if(count($rev_diff_keys) > 0){
							$this->ofw->test->notice("Some translations in $locale are not found in the default $default_locale locale ($name). Missing the following keys: ".join(', ', array_keys($rev_diff_keys)));
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
			$this->ofw->ofwconf['locale_default'] = $this->locale_default;
			$this->ofw->ofwconf['locale_available'] = $this->locales_available;
			$this->ofw->lang->reload_locale_settings();
			$this->ofw->lang->set($this->locale_set);

		// Clear lang variable
			$this->ofw->lang->reset_variables();
		// Restore language variables
			$this->ofw->lang->set_variables($this->configvars, $this->configsection);
	}

}