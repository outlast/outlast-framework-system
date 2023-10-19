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
    public static bool $in_database = false;

    // Fake not exists
    public bool $exists = false;

    static function __model(stdClass $fields = new stdClass()) : stdClass {

        $fields->text = zajDb::name();

        // do not modify the line below!
        return parent::__model($fields);
    }
}
