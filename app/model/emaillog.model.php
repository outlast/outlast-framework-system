<?php
/**
 * A class for logging sent emails.
 */

/**
 * Class EmailLog
 * @property EmailLogData $data
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

	/**
	 * Creates a new object from parameters.
	 * @param string $subject Subject of email.
	 * @param string $from From email address.
	 * @param string $to To email address.
	 * @param string $body_html Body html.
	 * @param string $body_txt Body text.
	 * @param string $bounce Bounce email to.
	 * @param string $bcc Bcc email address.
	 * @param string|array|stdClass $header Additional headers.
	 * @param string $status Status of email. Can be sent or failed (or new/deleted).
	 * @param string $log The log message.
	 */
	public static function create_from_email($subject, $from, $to, $body_html, $body_txt, $bounce, $bcc, $header, $status, $log){
		$emaillog = EmailLog::create();
		$emaillog->set('subject', $subject);
		$emaillog->set('from', $from);
		$emaillog->set('to', $to);
		$emaillog->set('html_body', $body_html);
		$emaillog->set('text_body', $body_txt);
		$emaillog->set('bounceto', $bounce);
		$emaillog->set('bcc', $bcc);
		$emaillog->set('headers', $header);
		$emaillog->set('status', $status);
		$emaillog->set('log', $log);
		$emaillog->save();
	}
}