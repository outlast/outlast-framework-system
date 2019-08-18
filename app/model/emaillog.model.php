<?php
/**
 * A class for logging sent emails.
 */

/**
 * Class EmailLog
 * @property EmailLogData $data
 */
class EmailLog extends zajModel {

	/**
	 * __model function. creates the database fields available for objects of this class.
	 * @param bool|stdClass $f The field's object generated by the child class.
 	 * @return stdClass Returns an object containing the field settings as parameters.
	 */
	static function __model($f = false){
		/////////////////////////////////////////
		// begin custom fields definition:
        if($f === false) $f = new stdClass();

		$f->subject = zajDb::name();
		$f->from = zajDb::text();
		$f->to = zajDb::text();
		$f->text_body = zajDb::textarea();
		$f->html_body = zajDb::textarea();
		$f->bcc = zajDb::text();
		$f->sentat = zajDb::text();
		$f->headers = zajDb::json();
		$f->status = zajDb::select(array('new', 'sent', 'failed', 'deleted'));
		$f->log = zajDb::textarea();

		$f->bounceto = zajDb::text();

		// just for testing
		$f->version = zajDb::onetoone('MozajikVersion');


		// do not modify the line below!
        return parent::__model($f);
	}

	/**
	 * Disable cache.
	 */
	public function __beforeCache(){
		return false;
	}

	/**
	 * Creates a new object from parameters.
	 * @param string $subject Subject of email.
	 * @param string $from From email address.
	 * @param string $to To email address.
	 * @param string $body The email body.
	 * @param string $bcc Bcc email address.
	 * @param string|array|stdClass $additional_headers Additional headers.
	 * @param integer $sentat
	 * @param string $status Status of email. Can be sent or failed (or new/deleted).
	 * @param string $log The log message.
	 */
	public static function create_from_email($subject, $from, $to, $body, $bcc, $additional_headers, $sentat, $status, $log){
		$emaillog = EmailLog::create();
		$emaillog->set('subject', $subject);
		$emaillog->set('from', $from);
		$emaillog->set('to', $to);
		$emaillog->set('html_body', $body);
		//$emaillog->set('text_body', $body_txt);
		// @todo Uncomment this after a few months...
		//if(!$sentat) $sentat = time();
		//$emaillog->set('sentat', $sentat);
		$emaillog->set('bcc', $bcc);
		$emaillog->set('headers', $additional_headers);
		$emaillog->set('status', $status);
		$emaillog->set('log', $log);
		$emaillog->save();
	}
}