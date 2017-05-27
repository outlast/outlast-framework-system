<?php

/**
 * An abstract class extended by library class files.
 * @package Base
 * @property zajLib $zajlib
 **/
abstract class zajLibExtension{
	/**
	 * A reference to the global zajlib object.
	 * @var zajLib
	 **/
	protected $zajlib;
	/**
	 * A string which stores the name of my system library.
	 * @var string
	 **/
	protected $system_library;
	/**
	 * Stores any options that were created when loading the library. See second param $optional_parameters of {@link zajLibLoader->library()}.
	 * $var array
	 **/
	public $options;

	/**
	 * Creates a new {@link zajLibExtension}
	 * @param zajLib $zajlib A reference to the global zajlib object.
	 * @param string $system_library The name of the system library.
	 **/
	public function __construct(&$zajlib, $system_library){
		// set my system library
		$this->system_library = $system_library;
		// set my parent
		$this->zajlib =& $zajlib;
	}

	/**
	 * A magic method used to display an error message if the method is not available.
	 * @param string $method The method to call.
	 * @param array $args An array of arguments.
	 **/
	public function __call($method, $args){
		// throw warning
		$this->zajlib->warning("The method $method is not available in library {$this->system_library}!");
	}
}

