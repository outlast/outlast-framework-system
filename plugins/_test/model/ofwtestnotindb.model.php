<?php
/**
 * A basic test model.
 * @package Model
 * @subpackage BuiltinModels
 **/

/**
 * Class OfwTestNotInDb
 */
class OfwTestNotInDb extends zajModel {

    // Just so it is not written to db
    public static $in_database = false;

    // Fake not exists
    public $exists = false;

    public static function __model($f = false) {

        /////////////////////////////////////////
        // begin custom fields definition:
        if ($f === false) {
            $f = new stdClass();
        }

        // end of custom fields definition
        /////////////////////////////////////////

        // do not modify the line below!
        return parent::__model($f);
    }
}
