<?php
/**
 * Helper functions related to dynamically loading/unloading plugins and checking plugin status. Since plugins do cause some overhead for most operations, unnecessary plugins should be loaded dynamically.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Library
 **/

class zajlib_plugin extends zajLibExtension {

	/**
	 * Checks to see if a plugin is loaded.
	 * @param string $name The name of the plugin.
	 * @return boolean Returns true if the plugin is loaded, false otherwise.
	 **/
	public function is_loaded($name){
		return in_array($name, $GLOBALS['zaj_plugin_apps']);
	}

	/**
	 * Alias of {@link is_loaded()}
	 **/
	public function is_enabled($name){ return $this->is_loaded($name); }
	
	/**
	 * Dynamically load a plugin.
	 * @param string $name The name of the plugin to be loaded.
	 * @todo Implement this!
	 * @return boolean Returns true if the plugin was loaded successfully, false otherwise. In case of failure, a warning will also be issued.
	 **/
	public function load($name){
		// Check to see if plugin exists, warning if not
		
		// Load plugin and return true
		
	}

	/**
	 * Dynamically unload a plugin.
	 * @param string $name The name of the plugin to be unloaded.
	 * @todo Implement this!
	 * @return boolean Returns true if the plugin was unloaded successfully, false if no such plugin was yet loaded.
	 **/
	public function unload($name){
		// Check to see if plugin loaded
			if(!$this->is_loaded($name)) return false;
		// Unload plugin and return true
			
		
	}	
}


	
?>