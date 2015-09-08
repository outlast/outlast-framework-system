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

	public static function create_from_email($subject, $from, $to, $body_html = "", $body_txt = "", $bounce = "", $bcc = "", $header = ""){
		$emaillog = EmailLog::create();
		$emaillog->set('subject', $subject);
		$emaillog->set('from', $from);
		$emaillog->set('to', $to);
		if($body_html != "")
			$emaillog->set('text_html', $body_html);
		if($body_txt != ""){
			$emaillog->set('text_body', $body_txt);
		}
		if($bounce != ""){
			$emaillog->set('bounceto', $bounce);
		}
		if($bcc != ""){
			$emaillog->set('bcc', $bcc);
		}
		if($header != ""){
			$emaillog->set('headers', $header);
		}
		$emaillog->save();
	}
}