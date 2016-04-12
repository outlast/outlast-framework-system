<?php
/**
 * Methods related to manipulating text.
 * @author Aron Budinszky <aron@outlast.hu>
 * @version 3.0
 * @package Library
 **/

class zajlib_text extends zajLibExtension {

	/**
	 * Converts new line characters to <br /> tags.
	 * @param string $str The original string.
	 * @return string The string with tags.
	 **/
	public static function nltobr($str){
		$str = str_replace ("\n", "<br />", $str);
		$str = str_replace ("\r", "", $str);
		return $str;
	}

	/**
	 * Converts <br /> tags to new lines.
	 * @param string $str The original string.
	 * @return string The string without tags.
	 **/
	public static function brtonl($str){
		$str = str_replace ("<br>", "\n", $str);
		$str = str_replace ("<br />", "\n", $str);
		return $str;
	}

	/**
	 * Removes newlines from the string.
	 * @param string $str The original string.
	 * @return string The string without tags.
	 **/
	public static function remove_nl($str){
		$str = str_replace ("\n", " ", $str);
		$str = str_replace ("\r", "", $str);
		return $str;	
	}
	
	/**
	 * Strips pre words such as 'the' and 'a'.
	 * @param string $str The original string.
	 * @return string The string without the pre words.
	 * @todo Move this to a plugin.
	 **/
	public function strip_pre_words($string){
		$string = mb_strtolower($string);
		$conData = str_replace("a ", "", $string);
		$conData = str_replace("az ", "", $conData);
		$conData = str_replace("the ", "", $conData);
		$conData = str_replace("dj ", "", $conData);
		$conData = str_replace("dj. ", "", $conData);
		$conData = str_replace("\"", "", $conData);
		return $conData;
	}

	/**
	 * Escape dangerous characters using various built-in or expanded methods.
	 * @param string $string The original string.
	 * @param string $method The method of escaping. Can be a number of values, see docs. Defaults to htmlspecialchars
	 * @return string The escaped, safe string.
	 * @todo fix javascript to be based on django docs
	 **/
	public function escape($string, $method){
		switch($method){
			case 'htmlentities':
			case 'htmlall':
				$string = htmlentities($string, ENT_QUOTES, 'UTF-8', false);
				break;
			case 'decode':
				$string = html_entity_decode($string);
				break;
			case 'url':
				$string = urlencode($string);
				break;
			case 'shellcmd':
				$string = escapeshellcmd($string);
				break;
			case 'shellarg':
				$string = escapeshellarg($string);
				break;
			case 'quotes':
			case 'javascript':
			case 'js':
				$string = str_replace('"','\"',$string);
				$string = str_replace("'","\\'",$string);
				$string = str_replace("\\n"," ",$string);
				$string = str_replace("\\r","",$string);
				break;
			case 'mail':
				$string = str_replace('@',' [at] ',$string);
				$string = str_replace('.',' [dot] ',$string);
				break;
			case 'htmlquotes':
				$string = str_replace('"','&quot;',$string);
				$string = str_replace("'",'&#039;',$string);
				break;
			case 'urlpathinfo':
			case 'hex':
			case 'hexentity':
				$string = 'This filter not yet supported.';
				break;
			case 'htmlspecialchars':
			case 'html':
			default:
				$string = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
				break;
		}
		return $string;
	}

	/**
	 * Adds a text to another text. Different from concatenation in the sense that if both texts are numeric, they are added mathematically.
	 * @param string $first The left string.
	 * @param string $second The right string.
	 * @return string The string version of the concatenated or added string. 
	 */
	public function add($first, $second){
		if(is_numeric($first) && is_numeric($second)) return (string) ($first + $second);
		else return $first.$second;
	}
	
	/**
	 * Truncates a string to length. Depricated.
	 * @param string $string The original string.
	 * @param integer $length The length to truncate to.
	 * @return string The truncated string.
	 * @todo This is no longer needed here, since this is done in the template tag 'truncate'.
	 **/
	public function cut_me($string, $length){
		if(strlen($string) > $length) $string = mb_substr($string, 0, $length-2)."...";
	    return $string;
	}
	
	/**
	 * Convert text urls to links.
	 * @param string $text The text to convert.
	 * @param integer $truncate Truncate long links in text to shorter version (www.facebook.com/asd) to this many characters.
	 * @return string The updated string with urls in place.
	 **/
	public function urlize($text, $truncate = false){
		return $this->get_auto_link($text, $truncate);
	}

	///////////////////////////////////////////////////////////////////////////////////////////
	// Tag conversions
	public function convertTagToURL($text, $tag, $link){
	 $text = ereg_replace("<$tag>([A-z0-9ÁÉÓÖŐÜŰÚÍéáűőúöüóí&'\?!:/\. \-]*)</$tag>", "<b><a href=\"$link\\1\">\\1</a></b>", $text);
	 return $text;
	}
	public function convertTagToIMG($text, $tag, $align='left'){
	 	$parts = explode("<$tag>", $text);
	 	if(count($parts) > 1){
	 		// get all images
	 		foreach($parts as $part){
	 			$data = explode("</$tag>", $part);
	 			$imgurl[] = $data[0];
	 		}
	 		// process all images
	 		foreach($imgurl as $img){
				$sizedata = @getimagesize($img);
				$width = $sizedata[0];
				$height = $sizedata[1];
				$text = str_replace("<$tag>$img</$tag>", "<a href=\"javascript:newFixedWindow('$img','dszimgviewer',$width,$height);\"><img src='$img' width='200' border='0' align='$align'></a>", $text);
			}
	 	}
	 	return $text;
	}
	
	/**
	 * Depricated version
	 * @ignore
	 **/
	public function get_auto_link($text, $truncate = false){
	  if(strip_tags($text) == $text){
	  	$text = ereg_replace('((www\.)([a-zA-Z0-9@:%_.~#-\?&]+[a-zA-Z0-9@:%_~#\?&/]))', "http://\\1", $text);
	  	$text = ereg_replace('((ftp://|http://|https://){2})([a-zA-Z0-9@:%_.~#-\?&]+[a-zA-Z0-9@:%_~#\?&/])', "http://\\3", $text);
	  	$text = ereg_replace('(((ftp://|http://|https://){1})[a-zA-Z0-9@:%_.~#-\?&]+[a-zA-Z0-9@:%_~#\?&/])', "<A HREF=\"\\1\" TARGET=\"_blank\">\\1</A>", $text);
	  	$text = ereg_replace('([_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3})',"<A HREF=\"mailto:\\1\">\\1</A>", $text);
	  }
	  return $text;
	}
	
	///////////////////////////////////////////////////////////////////////////////////////////
	// Unique number
	public function unique_number(){
		$uab=57;
		$lab=48;
		
		$mic= microtime();
		$smic= substr($mic,1,2); 
		$emic= substr($mic,4,6); 
		
		$ch= (mt_rand()%($uab-$lab))+$lab;
		  
		$po= strpos($emic, chr($ch));
		
		$emica=substr($emic,0,$po);
		$emicb=substr($emic,$po,strlen($emic));
		$out=substr($emica.$smic.$emicb, 1).rand(0, 9);
			
		return $out;
	}
	
	///////////////////////////////////////////////////////////////////////////////////////////
	// Is this in that?
	public function is_this_in_that($thisone, $thatone){
		if(mb_strrpos($thatone, $thisone) === false) return false;
		else return true;
	}
	
	///////////////////////////////////////////////////////////////////////////////////////////
	// Str to proper (utf support)
	public function str_to_proper($str){
		return mb_convert_case(mb_strtolower($str), MB_CASE_TITLE);
	}

}