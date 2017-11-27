<?php
/**
 * Form helper library is inteded to validate field input during saves.
 * @author Aron Budinszky <aron@outlast.hu>
 * @version 3.0
 * @package Library
 **/

define('OFW_VALIDATION_DEFAULT_ERROR_MESSAGES', true);
define('OFW_VALIDATION_RETURN_ERROR_MESSAGES', false);
		
class zajlib_form extends zajLibExtension {

		/**
		 * Check require fields - will return false if any of the fields specified were not filled out (are empty).
		 * @optional param string $field1 The name of the first field.
		 * @optional param string $field2 The name of the second field.
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
		 * @param boolean|array $error_messages This can be an array of error messages in the same order as fields. Also, OFW_VALIDATION_RETURN_ERROR_MESSAGES means that no error messages are shown, only an array of invalid fields is returned. Defaults to OFW_VALIDATION_DEFAULT_ERROR_MESSAGES which displays the default validation error messages.
		 * @param boolean|array $values A key/value array of values to validate. The key must be the name of the field, the value is whatever you want to test. If not given, the $_REQUEST array is used.
		 * @return boolean|array Returns an array of invalid fields, so an empty array if all is valid. If $error_messages is an array or is false, then json is returned.
		 **/
		public function validate($class_name, $fields, $error_messages = OFW_VALIDATION_DEFAULT_ERROR_MESSAGES, $values = false){
			// Let's make sure the data is consistent
				if(is_string($fields)) $fields = [$fields];
				if(is_string($error_messages)) $error_messages = [$error_messages];
			// Get the model for this class
				/** @var zajModel $class_name */
				$model = $class_name::__model();
			// Now time to check all
				$invalid_fields = [];
				foreach($fields as $key=>$field){
					// If values are given, use that. Otherwise, default to the $_REQUEST array.
						if($values !== false) $req_value = $values[$field];
						else $req_value = $_REQUEST[$field];
					// Now validate!
						$result = $model->$field->get_field($class_name)->validation($req_value);
					// If it fails, set message in invalid fields
						if($result === false){
							// Let's figure out what our error message is
								// If error messages explicitly stated, use that!
								if(is_array($error_messages)) $message = $error_messages[$key];
								// Otherwise get the default error message @todo implement this!
								else $message = "Field error (default message)";
							// Set message
								$invalid_fields[$field] = $message;
						}
				}
			// Should we display errors? Or return?
				if($error_messages !== false){
					// If no invalid fields found
						if(count($invalid_fields) == 0) return [];
					// Display the errors using standardized json data
						else{
							return $this->zajlib->json([
									'status'=>'error',
									'errors'=>$invalid_fields
								]
							);
						}
				}
			// We chose to return the fields, so do that!
				else return $invalid_fields;
		}

}