<?php
	/**
	 * A standard unit test for Outlast Framework database changes
	 **/
	class OfwDbTest extends zajTest {

		/**
		 * Set up stuff.
		 **/
		public function setUp(){
			$this->dbname = $this->zajlib->zajconf['mysql_db'];
		}

		/**
		 * Check if certain fields exist.
		 */
		public function photo_does_timepath_exist(){
			// Disabled if mysql not enabled
				if(!$this->zajlib->zajconf['mysql_enabled']) return false;
			// Get timepath
				$r = $this->zajlib->db->query("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '{$this->dbname}' AND TABLE_NAME = 'photo' AND COLUMN_NAME = 'timepath'")->next();
			// Assert that it exists
				zajTestAssert::areIdentical('timepath', $r->COLUMN_NAME);
		}

		/**
		 * Check if certain fields exist.
		 */
		public function photo_does_filesize_exist(){
			// Disabled if mysql not enabled
				if(!$this->zajlib->zajconf['mysql_enabled']) return false;
			// Get timepath
				$r = $this->zajlib->db->query("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '{$this->dbname}' AND TABLE_NAME = 'photo' AND COLUMN_NAME = 'filesizes'")->next();
			// Assert that it exists
				zajTestAssert::areIdentical('filesizes', $r->COLUMN_NAME);
		}


		/**
		 * Reset stuff, cleanup.
		 **/
		public function tearDown(){
		}

	}