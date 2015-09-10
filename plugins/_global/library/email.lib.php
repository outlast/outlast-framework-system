<?php
/**
 * Send emails in various formats.
 * @author Aron Budinszky <aron@outlast.hu>
 * @version 3.0
 * @package Library
 **/

define('POSTMARK_API_SEND_URL', 'https://api.postmarkapp.com/email');
define('MANDRILL_API_SEND_URL', 'https://mandrillapp.com/api/1.0/messages/send.json');

class zajlib_email extends zajLibExtension {

	/**
	 * Send a single or multiple text-based emails in ISO or UTF encoding. Typically you should use template lib's email sender to send HTML.
	 * @param string $from The email which is displayed as the from field. Supports "My Name <me@example.com>" format.
	 * @param string|array $to The email to which this message should be sent. You can specify multiple emails as comma-separated list or as array. Supports "My Name <me@example.com>" format.
	 * @param string $subject A string with the email's subject.
	 * @param string $body The email's body.
	 * @param bool|string $sendcopyto If set, a copy of the email will be sent (bcc) to the specified email address. By default, no copy is sent.
	 * @param bool|string $bounceto If set, the email will bounce to this address. By default, bounces are recorded by SendGrid.
	 * @param bool|string $additional_headers Not yet supported in this version of the plugin.
	 * @param bool|string $html_body The body in HTML.
	 * @return boolean True if successful, false otherwise. Only returns the result of the last one sent. To get results for multiple recipients, use send_single().
	 */
	public function send($from, $to, $subject, $body, $sendcopyto = false, $bounceto = false, $additional_headers = false, $html_body = false){
		// Figure out my recipients
		if(is_array($to)) $recipients = $to;
		else $recipients = explode(',', $to);
		// Result is false by default until any one is sent successfully
		$result = false;
		if(!is_array($recipients)) return $this->zajlib->warning("Invalid recipients specified for email send method. Must be an array, a string, or a comma-separated list.");
		// Now send to each recipient, but bcc only to first
		foreach($recipients as $recipient){
			// Send a single message
			$result = $this->send_single($from, $recipient, $subject, $body, $sendcopyto, $bounceto, $additional_headers, $html_body);
			// Don't send BCC after first one
			$sendcopyto = false;
		}
		return $result;
	}

	/**
	 * Send a single text-based email in ISO or UTF encoding. Only use this if you require success reports for each recipient.
	 * @todo Reenable standard mail() method but with notice.
	 * @todo Add additional headers and bounceto support.
	 * @param string $from The email which is displayed as the from field. Supports "My Name <me@example.com>" format.
	 * @param string|array $to The email to which this message should be sent. You can specify multiple emails as comma-separated list or as array. Supports "My Name <me@example.com>" format.
	 * @param string $subject A string with the email's subject.
	 * @param string $body The email's body.
	 * @param bool|string $sendcopyto If set, a copy of the email will be sent (bcc) to the specified email address. By default, no copy is sent.
	 * @param bool|string $bounceto If set, the email will bounce to this address. By default, bounces are recorded by the API you use.
	 * @param bool|string $additional_headers Not yet supported in this version of the plugin.
	 * @param bool|string $html_body The body in HTML.
	 * @return boolean True if successful, false otherwise.
	 */
	public function send_single($from, $to, $subject, $body, $sendcopyto = false, $bounceto = false, $additional_headers = false, $html_body = false){
		// Load up my info
		$this->zajlib->config->load('email_smtp.conf.ini');
		// Get my email data for from and to
		$from_data = $this->get_named_email($from);
		$to_data = $this->get_named_email($to);
		// Check if $to is valid
		if(!$this->valid($to_data->email, true)) return $this->zajlib->warning("Invalid email provided in To: ".$to);
		if(!$this->valid($from_data->email, true)) return $this->zajlib->warning("Invalid email provided in From: ".$from);

		// Email API required
		if($this->zajlib->config->variable->email_use_api){
			// Choose which API to use
			switch($this->zajlib->config->variable->email_provider){
				case 'postmark':
					if($html_body) $responses = $this->postmark($from, $to, $subject, $html_body, $sendcopyto);
					else $responses = $this->postmark($from, $to, $subject, $body, $sendcopyto);
					if($this->zajlib->zajconf['mysql_enabled']){
						if($responses->Message == 'OK') $status = 'sent';
						else $status = 'failed';
						EmailLog::create_from_email($subject, $from, $to, $html_body, strip_tags($html_body), $bounceto, $sendcopyto, $additional_headers, $status, json_encode($responses));
					}
					if($responses->Message == 'OK') return true;
					break;
				case 'mandrill':
				default:
					if($html_body) $responses = $this->mandrill($from, $to, $subject, $html_body, $sendcopyto);
					else $responses = $this->mandrill($from, $to, $subject, $body, $sendcopyto);
					if($this->zajlib->zajconf['mysql_enabled']){
						if(empty($responses->code)) $status = 'sent';
						else $status = 'failed';
						EmailLog::create_from_email($subject, $from, $to, $html_body, strip_tags($html_body), $bounceto, $sendcopyto, $additional_headers, $status, json_encode($responses));
					}
					if(empty($responses->code)) return true;
					break;
			}


		}
		else return $this->zajlib->warning("Failed to send email to $to, no email API activated.");
		return $this->zajlib->warning("Failed to send email to $to ".print_r($responses, true));
	}

	/**
	 * Send an email via Mandrill API.
	 * @link https://mandrillapp.com/api/docs/messages.html
	 * @param string $from The sender's email address in "John Doe <john@doe.com>" format.
	 * @param string $to The recipient of the email.
	 * @param string $subject The subject of the email.
	 * @param string $body The body of the email in HTML format.
	 * @param bool|string $bcc Add a BCC recipient or leave as false to skip this.
	 * @param bool|string $tag Tagging if you want the email to show up with a flag in Postmark API reports.
	 * @return object The answer from the API.
	 */
	public function mandrill($from, $to, $subject, $body, $bcc = false, $tag = false){
		// Create defaults
		if(empty($tag)) $tag = $this->zajlib->domain;
		// Build my headers
		$pheader = array(
			'Accept'=>'application/json',
			'Content-type'=>'application/json',
			'User-Agent'=>'Outlast Framework',
		);
		// HTML body
		$txtbody = strip_tags($body);

		// Separate emails
		$from = $this->get_named_email($from);
		$to = $this->get_named_email($to);
		// Now build my body
		// https://mandrillapp.com/api/docs/messages.html
		$pbody = array(
			'key'=> $this->zajlib->config->variable->email_api_key,
			'message'=>array(
				'from_name'=>$from->name,
				'from_email'=>$from->email,
				'to'=>array(
					array(
						'name'=>$to->name,
						'email'=>$to->email,
					),
				),
				'subject'=>$subject,
				'html'=>$body,
				'text'=>$txtbody,
				'bcc_address'=>$bcc,
				'tags'=>array($tag),
			),
			'async'=>true,
		);
		// Add bcc if needed
		if(!empty($bcc)) $pbody['Bcc'] = $bcc;
		// Post it and return it!
		return json_decode($this->zajlib->request->post(MANDRILL_API_SEND_URL, json_encode($pbody), false, $pheader));
	}

	/**
	 * Send an email via Postmark API.
	 * @link http://developer.postmarkapp.com/developer-build.html
	 * @param string $from The sender's email address in "John Doe <john@doe.com>" format.
	 * @param string $to The recipient of the email.
	 * @param string $subject The subject of the email.
	 * @param string $body The body of the email in HTML format.
	 * @param bool|string $bcc Add a BCC recipient or leave as false to skip this.
	 * @param bool|string $tag Tagging if you want the email to show up with a flag in Postmark API reports.
	 * @return object The answer from the API.
	 */
	public function postmark($from, $to, $subject, $body, $bcc = false, $tag = false){
		// Create defaults
		if(empty($tag)) $tag = $this->zajlib->domain;
		// Build my headers
		$pheader = array(
			'Accept'=>'application/json',
			'Content-type'=>'application/json',
			'X-Postmark-Server-Token'=>$this->zajlib->config->variable->email_api_key,
		);

		// HTML body
		$txtbody = strip_tags($body);

		// Now build my body
		// {From: 'sender@example.com', To: 'receiver@example.com', Subject: 'Postmark test', HtmlBody: '<html><body><strong>Hello</strong> dear Postmark user.</body></html>'}
		$pbody = array(
			'From'=> $from,
			'To'=>$to,
			'Subject'=>$subject,
			'HtmlBody'=>$body,
			'TextBody'=>$txtbody,
			'Tag'=>$tag,
		);
		// Add bcc if needed
		if(!empty($bcc)) $pbody['Bcc'] = $bcc;
		// Post it and return it!
		return json_decode($this->zajlib->request->post(POSTMARK_API_SEND_URL, json_encode($pbody), false, $pheader));
	}

	/**
	 * Send a UTF-encoded HTML email.
	 * @param string $from The email which is displayed as the from field.
	 * @param string $to The email to which this message should be sent.
	 * @param string $subject A string with the email's subject.
	 * @param string $body The email's body which should be in HTML.
	 * @param bool|string $sendcopyto If set, a copy of the email will be sent (bcc) to the specified email address. By default, no copy is sent.
	 * @param bool|string $bounceto If set, the email will bounce to this address. By default, bounces are ignored and not sent anywhere.
	 * @param bool|string $body_text If set, text-version will be set to this.
	 * @return boolean True if successful, false otherwise.
	 */
	public function send_html($from, $to, $subject, $body, $sendcopyto = false, $bounceto = false, $body_text = false){
		// Create a plain text version (if not set by default)
		if(empty($body_text)) $body_text = strip_tags($this->zajlib->text->brtonl($body));
		// Now send
		return $this->send($from, $to, $subject, $body_text, $sendcopyto, $bounceto, false, $body);
	}

	/**
	 * Parse an email address in "Mr. Name <name@example.com>" format. Returns an object.
	 * @param string $email_address_with_name The email address to parse.
	 * @return stdClass Returns an object {'name'=>'Mr. Name', 'email'=>'name@example.com'}. If no name specified, the 'name' property will be empty.
	 **/
	public function get_named_email($email_address_with_name){
		// Parse an email first via regexp (if in format My Name <name@example.com>)
		$result = preg_match_all('/([^<]*)<([^>]*)/', $email_address_with_name, $arr, PREG_SET_ORDER);
		// Create my return object
		$email_data = (object) array();
		// If result found then parse it now
		if($result){
			$email_data->name = trim($arr[0][1]);
			$email_data->email = trim($arr[0][2]);
		}
		else{
			$email_data->name = '';
			$email_data->email = trim($email_address_with_name);
		}
		return $email_data;
	}

	/**
	 * Checks and returns true if the email address is valid. You can specify whether to allow "Name <test@test.com>" formatting.
	 * @param string $email The email address to test.
	 * @param boolean $allow_named_format Set to true if you want to allow named format. False by default.
	 * @return boolean Returns true if the email is valid, false otherwise.
	 **/
	public function valid($email, $allow_named_format = false){
		// If allow named format
		if($allow_named_format){
			$email_data = $this->get_named_email($email);
			$email = $email_data->email;
		}
		// Now check and return 
		return (boolean) preg_match('/^[_A-z0-9-]+(\.[_A-z0-9-]+)*@[A-z0-9-]+(\.[A-z0-9-]+)*(\.[A-z]{2,10})$/', $email);
	}
}
