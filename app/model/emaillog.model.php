<?php
/**
 * A class for log email sending.
 */

class EmailLog extends zajModel {

	///////////////////////////////////////////////////////////////
	// !Model design
	///////////////////////////////////////////////////////////////
	public static function __model(){
		// define custom database fields
		$f = (object) array();
		$f->subject = zajDb::name();
		$f->from = zajDb::text();
		$f->to = zajDb::text();
		$f->text_body = zajDb::textarea();
		$f->html_body = zajDb::textarea();
		$f->bcc = zajDb::text();
		$f->bounceto = zajDb::text();
		$f->headers = zajDb::json();
		$f->status = zajDb::select(array('new', 'sent', 'failed', 'deleted'));
		$f->log = zajDb::textarea();

		// do not modify the line below!
		$f = parent::__model(__CLASS__, $f); return $f;
	}
	/**
	 * Construction and required methods
	 */
	public function __construct($id = ""){ parent::__construct($id, __CLASS__); return true; }
	public static function __callStatic($name, $arguments){ array_unshift($arguments, __CLASS__); return call_user_func_array(array('parent', $name), $arguments); }

	///////////////////////////////////////////////////////////////
	// !Custom methods
	///////////////////////////////////////////////////////////////

	public function __afterFetch(){

	}
}