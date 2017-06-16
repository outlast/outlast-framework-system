<?php
/**
 * The class (single object) which stores template variables.
 * @package Base
 * @todo What are the benefits of defining this class? Really, we could just have an (object) array();
 **/
class zajVariable {

	/**
	 * An array of the variable data stored herein.
	 **/
	private $data = [];

	/**
	 * Magic method to return the data.
	 **/
	public function __get($name){
		if(isset($this->data[$name])) return $this->data[$name];
		else return '';
	}

	/**
	 * Magic method to set the data.
	 **/
	public function __set($name, $value){
		$this->data[$name] = $value;
	}

	/**
	 * Magic method to return debug information
	 * @return string Returns some nice debug info.
	 **/
	public function __toDebug(){
		// Init the string
		$str = "";
		// Generate output
		foreach($this->data as $name=>$value){
			if(is_array($value) || is_object($value)){
				foreach($value as $k=>$v){
					if(is_object($v)) $str .= "\n[$name][$k] => [object]";
					else $str .= "\n[$name][$k] => ".str_replace("\n","\n\t\t",$v);
				}
			}
			else $str .= "\n[$name] => $value";
		}
		return $str;
	}
}