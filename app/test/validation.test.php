<?php
	/**
	 * A standard unit test for Outlast Framework automatic validation
	 **/
	class OfwValidationTest extends zajTest {

		/**
		 * Set up stuff.
		 **/
		public function setUp(){
			// Disabled if mysql not enabled
				if(!$this->zajlib->zajconf['mysql_enabled']) return false;
			// Load up my _test plugin (if not already done)
				$this->zajlib->plugin->load('_test', true, true);
		}

		/**
		 * Check validation.
		 */
		public function system_validation_test(){
			// Disabled if mysql not enabled
				if(!$this->zajlib->zajconf['mysql_enabled']) return false;
			// Explicitly pass the value and error
				$result = $this->zajlib->form->validate('OfwTestModel', ['email'], ['Invalid email!'], ['email' =>'asdf']);
				zajTestAssert::areIdentical('{"status":"error","errors":{"email":"Invalid email!"}}', $result);
			// Explicitly pass the value and success
				$result = $this->zajlib->form->validate('OfwTestModel', ['email'], ['Invalid email!'], ['email' =>'test@example.com']);
				zajTestAssert::areIdentical([], $result);
			// Set the request invalid value and try this way
				$_REQUEST['email'] = 'asdf';
				$result = $this->zajlib->form->validate('OfwTestModel', ['email'], ['Invalid email!']);
				zajTestAssert::areIdentical('{"status":"error","errors":{"email":"Invalid email!"}}', $result);
			// The request is invalid, and valdiation should return error messages
				$result = $this->zajlib->form->validate('OfwTestModel', ['email'], OFW_VALIDATION_RETURN_ERROR_MESSAGES);
				zajTestAssert::areIdentical(['email'=>'Field error (default message)'], $result);
			// Set the request valid value and try this way
				$_REQUEST['email'] = 'test@example.com';
				$result = $this->zajlib->form->validate('OfwTestModel', ['email'], ['Invalid email!']);
				zajTestAssert::areIdentical([], $result);
			// The request is valid, and valdiation should return error messages
				$result = $this->zajlib->form->validate('OfwTestModel', ['email'], OFW_VALIDATION_RETURN_ERROR_MESSAGES);
				zajTestAssert::areIdentical([], $result);

			// Test the single field method with error
				$result = $this->zajlib->form->validate('OfwTestModel', 'email', 'Invalid email!', ['email' =>'asdf']);
				zajTestAssert::areIdentical('{"status":"error","errors":{"email":"Invalid email!"}}', $result);
			// Test the single field method with success
				$result = $this->zajlib->form->validate('OfwTestModel', 'email', 'Invalid email!', ['email' =>'test@example.com']);
				zajTestAssert::areIdentical([], $result);

			return true;
		}

		/**
		 * Reset stuff, cleanup.
		 **/
		public function tearDown(){
			// Disabled if mysql not enabled
				if(!$this->zajlib->zajconf['mysql_enabled']) return false;
		}

	}