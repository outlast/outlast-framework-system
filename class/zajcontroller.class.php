<?php
/**
 * The abstract controller base class.
 * @author Aron Budinszky <aron@outlast.hu>
 * @version 3.0
 * @package Controller
 * @subpackage Base
 */
 
/**
 * The abstract controller base class.
 * @method mixed __load($request, $optional_parameters=[]) EVENT. Executed each time the given controller is loaded. Optional parameters can be passed with load->controller().
 * @method mixed __error($request, $optional_parameters=[]) EVENT. Executed when no valid controller app/method was found!
 * @package Controller
 * @subpackage Base
 **/
abstract class zajController{
	/**
	 * A reference to the global zajlib object.
	 * @var zajLib
	 **/
	var $zajlib;		// the global zajlib
	/**
	 * The name of the current app.
	 * @var string
	 **/
	var $name;			// name of the app
	
	/**
	 * Creates a new controller object.
	 * @param zajLib $zajlib A reference to the global zajlib object.
	 * @param string $name The name of the app.
	 **/
	function __construct(&$zajlib, $name){
		$this->zajlib = $zajlib;
		$this->name = $name;
	}

	/**
	 * Magic method which calls the appropriate method within the given controller class.
	 **/
	function __call($name, $arguments){
		// if not in debug mode, call the __error on current app
		if(method_exists($this, "__error")) return $this->__error($name, $arguments);
		// else just call the standard ofw error
		else return $this->zajlib->error("application request ($this->name/$name) could not be processed. no matching application control method found!");
	}	

}