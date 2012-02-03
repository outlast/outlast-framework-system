<?php
/**
 * This library performs various language and encoding related conversions.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Library
 **/

// Load config file
	$GLOBALS['zajlib']->load->library('config');


class zajlib_lang extends zajlib_config {

	/**
	 * Extend the config file loading mechanism.
	 **/
		protected $dest_path = 'cache/lang/';	// string - subfolder where compiled conf files are stored (cannot be changed)
		protected $conf_path = 'lang/';			// string - default subfolder where uncompiled conf files are stored
		protected $type_of_file = 'language';	// string - the name of the file type this is (either configuration or language)

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