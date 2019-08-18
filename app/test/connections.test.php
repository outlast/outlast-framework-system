<?php

    // Load up mocks and models
    include(zajLib::me()->basepath.'system/plugins/_test/mock/ofw_db.mock.php');

	/**
	 * A standard unit test for Outlast Framework model connections
	 **/
	class OfwConnectionsTest extends ofwTest {

		/**
		 * Set up stuff.
		 **/
		public function setUp(){
			// Disabled if mysql not enabled
				if(!$this->ofw->ofwconf['mysql_enabled']) return false;
			// Load up my _test plugin (if not already done)
				$this->ofw->plugin->load('_test', true, true);

			return true;
		}

		/**
		 * Check connections.
		 */
		public function system_connections_is_connected(){
			// Disabled if mysql not enabled
			if(!$this->ofw->ofwconf['mysql_enabled']) return false;

            /** @var OfwTestModel $ofwtest */
            /** @var OfwTestAnother $ofwtestanother */
            $ofwtest = OfwTestModel::create();
            $ofwtestanother = OfwTestAnother::create();
            $db = new ofw_db_mock();
            $db2 = new ofw_db_mock();
            $ofwtest->set_mock_database($db);
            $ofwtestanother->set_mock_database($db2);
            $ofwtest->data->ofwtestanothers->set_mock_database($db);
            $ofwtest->data->ofwtestanothers->is_connected($ofwtestanother);

            // Should generate the same from the opposite direction
            $ofwtestanother->data->ofwtests->set_mock_database($db2);
            $ofwtestanother->data->ofwtests->is_connected($ofwtest);

            $sql = "SELECT COUNT(*) as c FROM connection_ofwtestmodel_ofwtestanother WHERE id1='{$ofwtest->id}' AND id2='{$ofwtestanother->id}' AND field='ofwtestanothers'";
            ofwTestAssert::areIdentical($sql, $db->last_query);
            $sql2 = "SELECT COUNT(*) as c FROM connection_ofwtestmodel_ofwtestanother WHERE id2='{$ofwtestanother->id}' AND id1='{$ofwtest->id}' AND field='ofwtestanothers'";
            ofwTestAssert::areIdentical($sql2, $db2->last_query);

			return true;
		}

        /**
         * Check saving one to ones
         */
        public function system_connections_onetoone() {
			// Disabled if mysql not enabled
			if(!$this->ofw->ofwconf['mysql_enabled']) return false;

			// Create two test items
			$log = EmailLog::create();
			$log->set('subject', 'Test 1')->save();
			$version = MozajikVersion::create();
			$version->set('major', 20)->save();

			// Try to set from parent side
			$log->set('version', $version)->save();
			ofwTestAssert::areIdentical($version->id, $log->data->version->id);
			ofwTestAssert::areIdentical($log->id, $version->data->log->id);
			$log->set('version', false)->save();
			ofwTestAssert::isFalse($log->data->version);
			$version->data->unload('log');
			ofwTestAssert::isFalse($version->data->log);

			// Now try to set from child side
			$version->set('log', $log)->save();
			ofwTestAssert::areIdentical($version->id, $log->data->version->id);
			ofwTestAssert::areIdentical($log->id, $version->data->log->id);
			$version->set('log', false)->save();
			ofwTestAssert::isFalse($version->data->log);
			$log->data->reload();
			ofwTestAssert::isFalse($log->data->version);

			return true;

        }


		/**
		 * Check reordering
		 */
		public function system_connections_reordering(){
			// Disabled if mysql not enabled
			if(!$this->ofw->ofwconf['mysql_enabled']) return false;

            /** @var OfwTestModel $ofwtest */
            $ofwtest = OfwTestModel::create();
            $db = new ofw_db_mock();
            $ofwtest->set_mock_database($db);
            $ofwtest->data->ofwtestanothers->set_mock_database($db);
            $ofwtest->data->ofwtestanothers->reorder([3, 2, 1]);


            $sql = "SELECT id2 as id, order2 as ordernum FROM connection_ofwtestmodel_ofwtestanother WHERE id1='$ofwtest->id' AND id2 IN ('3', '2', '1') ORDER BY order2 ASC";
            ofwTestAssert::areIdentical($sql, $db->last_query);

            return true;
        }

		/**
		 * Reset stuff, cleanup.
		 **/
		public function tearDown(){
			// Disabled if mysql not enabled
            if(!$this->ofw->ofwconf['mysql_enabled']) return false;
            return true;
		}

	}