<?php
/**
 * Form helper library is inteded to validate field input during saves.
 * @author Aron Budinszky <aron@outlast.hu>
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
					// Parse it to see if array element (ex: myarg[]=value). Only supports a single dimension for now
					preg_match("/([^\\[]+)\\[([^\\[]+)\\]/", $arg, $matches);
					// No matches, so it's standard, non-array check
					if(count($matches) == 0){
						if(empty($_REQUEST[$arg])){
							$valid_input = false;
							break;
						}
					}
					// Do have matches, so it's a single dimensional check
					else{
						if(empty($_REQUEST[$matches[1]][$matches[2]])){
							$valid_input = false;
							break;
						}
					}
				}
			return $valid_input;
		}
		
		/**
		 * Validate fields and automatically return a result and stop execution.
		 * @param string $class_name The zajModel class whos fields we are checking.
		 * @param string|array $fields A string or an array of strings with the field name(s).
		 * @param string|array $requests A string or an array of strings for the request variable(s) which to check against. This defaults to the same as the field names provided.
		 * @return boolean Print true if all are filled out, false if not.
		 **/
		public function validate($class_name, $fields, $requests = ''){
			// Let's make sure the data is consistent
				if(is_string($fields)) $fields = array($fields);
				if(is_string($requests)) $requests = array($requests);
			// Get the model for this class
				$model = $class_name::__model();
			// Now time to check all
				$valid_input = true;
				foreach($fields as $key=>$field){
					// My request
						$req = $requests[$key];
					// Default to my same name
						if(empty($req)) $req = $field;
					// Get actual value
						$req_value = $_REQUEST[$req];
					// Now validate!
						$result = $model->$field->get_field($class_name)->validation($req_value);
					// If it fails, just return now
						if($result === false){
							$valid_input = false;
							break;
						}

				}
			return $valid_input;
		}

}