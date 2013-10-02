<?php
/**
 * Just a default test controller.
 * @package Controller
 * @subpackage BuiltinControllers
 **/

	class zajapp__test extends zajController{
		/**
		 * Load method.
		 **/
		function __load(){

		}

		/**
		 * Plugin loader method.
		 **/
		function __plugin(){
			return "__plugin working!";
		}
	}