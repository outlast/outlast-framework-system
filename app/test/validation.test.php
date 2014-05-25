<?php
	/**
	 * A standard unit test for Outlast Framework automatic validation
	 **/
	class OfwValidationTest extends zajTest {

		/**
		 * Set up stuff.
		 **/
		public function setUp(){
		}

		/**
		 * Check validation.
		 */
		public function validation_test(){
			// Explicitly pass the value and error
				$result = $this->zajlib->form->validate('OfwTest', array('email'), array('Invalid email!'), array('email'=>'asdf'));
				zajTestAssert::areIdentical('{"status":"error","errors":{"email":"Invalid email!"}}', $result);
			// Explicitly pass the value and success
				$result = $this->zajlib->form->validate('OfwTest', array('email'), array('Invalid email!'), array('email'=>'test@example.com'));
				zajTestAssert::areIdentical(array(), $result);
			// Set the request invalid value and try this way
				$_REQUEST['email'] = 'asdf';
				$result = $this->zajlib->form->validate('OfwTest', array('email'), array('Invalid email!'));
				zajTestAssert::areIdentical('{"status":"error","errors":{"email":"Invalid email!"}}', $result);
			// The request is invalid, and valdiation should return error messages
				$result = $this->zajlib->form->validate('OfwTest', array('email'), OFW_VALIDATION_RETURN_ERROR_MESSAGES);
				zajTestAssert::areIdentical(array('email'=>'Field error (default message)'), $result);
			// Set the request valid value and try this way
				$_REQUEST['email'] = 'test@example.com';
				$result = $this->zajlib->form->validate('OfwTest', array('email'), array('Invalid email!'));
				zajTestAssert::areIdentical(array(), $result);
			// The request is valid, and valdiation should return error messages
				$result = $this->zajlib->form->validate('OfwTest', array('email'), OFW_VALIDATION_RETURN_ERROR_MESSAGES);
				zajTestAssert::areIdentical(array(), $result);

			// Test the single field method with error
				$result = $this->zajlib->form->validate('OfwTest', 'email', 'Invalid email!', array('email'=>'asdf'));
				zajTestAssert::areIdentical('{"status":"error","errors":{"email":"Invalid email!"}}', $result);
			// Test the single field method with success
				$result = $this->zajlib->form->validate('OfwTest', 'email', 'Invalid email!', array('email'=>'test@example.com'));
				zajTestAssert::areIdentical(array(), $result);

			return true;
		}

		/**
		 * Reset stuff, cleanup.
		 **/
		public function tearDown(){
		}

	}