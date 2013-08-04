<?php
/**
 * Form helper library is inteded to validate field input during saves.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Library
 **/
		
class zajlib_form extends zajLibExtension {

		/**
		 * Check require fields - will return false if any of the fields specified were not filled out (are empty).
		 * @internal param string $field1 The name of the first field.
		 * @internal param string $field2 The name of the second field.
		 * @return boolean Return true if all are filled out, false if not.
		 * @todo Autoselect POST or GET instead of using REQUEST.
		 */
		public function filled(){
			// Get function arguments
				$arguments = func_get_args();
			// Run through all and make sure each is not empty
				$valid_input = true;
				foreach($arguments as $arg){
					if(empty($_REQUEST[$arg])){
						//print "$arg is empty";
						$valid_input = false;
						break;
					}
				}
			return $valid_input;
		}
		
		/**
		 * Validate fields and automatically return a result and stop execution.
		 * @param string $class_name The zajModel class whos fields we are checking.
		 * @param array $fields An array of strings with the field names.
		 * @param array|bool $request The request which to check against. By default POST or GET is automatically checked, based on which mode we are in.
		 * @return boolean Print true if all are filled out, false if not.
		 * @todo Implement this
		 **/
		public function validate($class_name, $fields, $request = false){

			// NOT YET IMPLEMENTED!
			return true;

			/**
			// Get function arguments
				$arguments = func_get_args();
			// Run through all and make sure each is not empty
				$valid_input = true;
				foreach($fields as $arg){
					if(empty($_REQUEST[$arg])){
						$valid_input = false;
						break;
					}
				}
			return $valid_input;**/
		}

}