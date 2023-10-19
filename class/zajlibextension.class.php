<?php

/**
 * An abstract class extended by library class files.
 * @package Base
 **/
abstract class zajLibExtension{

	/**
	 * A reference to the global zajlib object.
     * @deprecated Use ofw instead!
	 * @var zajLib
	 **/
	protected $zajlib;

    /**
     * A reference to the singleton OFW object.
     * @var zajLib
     **/
    protected $ofw;

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
	 * @param zajLib $ofw A reference to the global zajlib object.
	 * @param string $library_name The name of the system library.
	 **/
	public function __construct(&$ofw, $library_name){
		// set my system library
		$this->system_library = $library_name;
		// set my parent
		$this->ofw = $ofw;
		$this->zajlib = $this->ofw;
	}

	/**
	 * A magic method used to display an error message if the method is not available.
	 * @param string $method The method to call.
	 * @param array $args An array of arguments.
	 **/
	public function __call($method, $args){
		// throw warning
		$this->ofw->warning("The method $method is not available in library {$this->system_library}!");
	}

}

