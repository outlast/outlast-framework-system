<?php
/**
 * A basic test model.
 * @package Model
 * @subpackage BuiltinModels
 **/

/**
 * Class OfwTest
 * @property OfwTestAnotherData $data
 */
class OfwTestAnother extends zajModel {

    static function __model(stdClass $fields = new stdClass()) : stdClass {

        // Fake manytomany connection
        $fields->ofwtests = zajDb::manytomany('OfwTestModel', 'ofwtestanothers');

		// end of custom fields definition
		/////////////////////////////////////////

		// do not modify the line below!
        return parent::__model($fields);
	}

}
