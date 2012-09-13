<?php
/**
 * This library performs various language and encoding related conversions. It also enables loading language files and changing the current language.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Library
 **/

// Load config file
	$GLOBALS['zajlib']->load->library('config');


class zajlib_lang extends zajlib_config {
	
	/**
	 * Sets the current locale. The default is set in the config file site/index.php.
	 **/
	 	private $current_locale;

	/**
	 * Extend the config file loading mechanism.
	 **/
		protected $dest_path = 'cache/lang/';	// string - subfolder where compiled conf files are stored (cannot be changed)
		protected $conf_path = 'lang/';			// string - default subfolder where uncompiled conf files are stored
		protected $type_of_file = 'language';	// string - the name of the file type this is (either configuration or language)

	/**
	 * Creates a new language library.
	 **/
	public function __construct(&$zajlib, $system_library) {
		parent::__construct($zajlib, $system_library);
		// set my default locale
			$this->set();
	}

	/**
	 * Methods for loading and changing current language.
	 **/

		/**
		 * Get the current locale.
		 * @return string The locale code of the current language.
		 **/
		function get(){
			// Return the current locale language
				return $this->current_locale;
		}

		/**
		 * Get the current two-letter language code based on the current locale.
		 * @return string The language code based on current locale.
		 **/
		function get_by_code(){
			// Return the current locale language
				return substr($this->current_locale, 0, 2);
		}

		/**
		 * Change locale language to a new one.
		 * @param string $new_language If set, it will try to choose this locale. Otherwise the default locale will be chosen.
		 * @return string Returns the name of the locale that was set.
		 **/
	 	function set($new_language = false){
	 		// Language can be set to any of the locales and by default the default locale is chosen
	 			if(!empty($new_language)) $available_locales = explode(',', $this->zajlib->zajconf['locale_available']);
	 		// Check to see if the language to be set is not false and is in locales available. If problem, set to default locale.
	 			if(!empty($new_language) && in_array($new_language, $this->zajlib->zajconf['locale_available'])){
	 				$this->current_locale = $new_language;
	 			}
	 			else $this->current_locale = $this->zajlib->zajconf['locale_default'];
	 		// Return new locale
	 			return $this->current_locale;
		}

		/**
		 * Set the current language (locale) using a two-letter language code. In case two or more locales use the same two letter code, the first will be chosen. If possible, use {@link $this->set()} instead.
		 * @param string $new_language If set, it will try to choose this language. Otherwise the default langauge will be chosen based on the default locale.
		 * @return string The two-letter language code based on current locale.
		 **/
		function set_by_code($new_language = false){
			if(!empty($new_language)){
			// Let's see if we have a compatible locale
	 			$available_locales = explode(',', $this->zajlib->zajconf['locale_available']);
	 			foreach($available_locales as $l){
	 				// If found, set the locale and return me
	 				$lcompare = substr($l, 0, 2);
	 				if($lcompare == $new_language){
	 					$this->set($l);
	 					return $lcompare;
	 				}
	 			}
	 		}
	 		// Not found, set to default locale and return it
	 			return substr($this->set(), 0, 2);
		}

		/**
		 * Automatically set the locale based on a number of factors.
		 * @return string The automatically selected locale.
		 **/
		function auto(){
			// TODO: implement this based on current codes
		}

	/**
	 * Override my load method for loading language files
	 **/
		/**
		 * Loads a langauge file at runtime. The file name can be specified two ways: either the specific ini file or just the name with the locale and extension automatic.
		 *
		 * For example: if you specify 'admin_shop' as the first parameter with en_US as the locale, the file lang/admin/shop.en_US.lang.ini will be loaded.
		 *
		 * @param string $name_OR_source_path The name of the file (without locale or ini extension) or the specific ini file to load.
		 * @param string $section The section to compile.
		 * @param boolean $force_compile This will force recompile even if a cached version already exists.
		 **/
		public function load($name_OR_source_path, $section=false, $force_compile=false){
			// First let's see if . is not found in path. If so, this is a name, so figure out what source path is based on current locale
				if(strstr($name_OR_source_path, '.') === false) $name_OR_source_path = $name_OR_source_path.'.'.$this->get().'.lang.ini';
			// Now just load the file as if it were a usual config and return
				return parent::load($name_OR_source_path, $section, $force_compile);
		}

	/**
	 * Other language-specific methods.
	 **/

		/**
		 * Converts a string to their standard latin-1 alphabet counterparts.
		 * @param string $str The original accented UTF string.
		 * @return string Returns a string without accents.
		 **/
		function convert_eng($str, $strip_newlines = true){
			// now try to translate all characters to iso1
				$str = $this->convert($str, "ISO-8859-1", "UTF-8");
			// now remove newlines
				if($strip_newlines){
					$str = str_ireplace("\n", " ", $str);
					$str = str_ireplace("\r", "", $str);
				}
			return $str;
		}
		
		/**
		 * Strip non-alpha-numerical characters from a string.
		 * @param string $str The original accented string.
		 * @param boolean $convert_accents If set to true, accented characters will be converted before everything is stripped.
		 * @return string Returns a string without accents and only alpha-numerical characters
		 **/
		function strip_chars($str, $convert_accents = true){
			// convert accents?
				if($convert_accents) $str = $this->convert_eng($str);
			// now strip all non-alphanum
				$str = preg_replace("/[^[:alnum:]]/", "", $str);
			return $str;
		}
		
		/**
		 * Converts a string from one encoding to another.
		 * @param string $str The original string.
		 * @param string $to The destination encoding. ISO latin 1 by default.
		 * @param string $from The original encoding. UTF-8 by default.	 
		 * @param boolean $use_iconv Will force the function to use iconv. This will work on some systems, not on others.
		 * @return string Returns a string converted to the new encoding.
		 **/
		function convert($str, $to="ISO-8859-1", $from="UTF-8", $use_iconv = false){
			// try to convert using iconv
				if($use_iconv) return iconv($from, $to."//TRANSLIT//IGNORE", $str);
			// try to convert using html entities and manual conversions. you may add additional characters here for more languages!
				else{
					$text = mb_convert_encoding($str,'HTML-ENTITIES',$from);
					$text = preg_replace(
						array('/&szlig;/','/&(..)lig;/', '/&([aouAOU])uml;/','/&#337;/', '/&#336;/', '/&#369;/', '/&#368;/', '/&(.)[^;]*;/'),
						array('ss',"$1","$1".'e', 'o', 'O', 'u', 'U', "$1"),
					$text);
					return $text;
				}
			return $str;
		}
	
		/**
		 * UTF-safe substring. This is now depricated, use the built-in mb_substr instead.
		 * @param string $str The original accented string.
		 * @param integer $from The original accented string.
		 * @param integer $len The original accented string.	 
		 * @todo Depricated! Remove this from 1.0
		 **/
		function utf8_substr($str,$from,$len){
			return mb_substr($str, $from, $len);
		}
		
		/**
		 * Currency display. Depricated.
		 * @todo Remove this from 1.0
		 **/
		function currency($num){
			global $lang;
			return number_format($num, $lang[currency_valto],$lang[currency_tized],$lang[currency_ezer]);
		}
		
		
		/**
		 * Convert from central european ISO to UTF
		 * @todo Remove this from 1.0
		 **/
		function ISO2UTF($str){
			return iconv("ISO-8859-2", "UTF-8", $str);
		}
	
		/**
		 * Convert from UTF to central european ISO
		 * @todo Remove this from 1.0
		 **/
		function UTF2ISO($str){
			return iconv("UTF-8", "ISO-8859-2", $str);
		}	
		/**
		 * Replaces language-related sections in a string, but this is depricated so don't use!
		 * @todo Remove this from version 1.0
		 **/
		function replace($search, $replace, $subject){
			return str_ireplace("%".$search."%", $replace, $subject);
		}
	
}




?>