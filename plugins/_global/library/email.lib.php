<?php
/**
 * Send emails in various formats.
 * @author Aron Budinszky <aron@outlast.hu>
 * @version 3.0
 * @package Library
 **/

define('POSTMARK_API_SEND_URL', 'https://api.postmarkapp.com/email');
define('MANDRILL_API_SEND_URL', 'https://mandrillapp.com/api/1.0/messages/send.json');
define('SENDGRID_API_SEND_URL', 'https://api.sendgrid.com/api/mail.send.json');


class zajlib_email extends zajLibExtension {

	protected $postmark_warning_sent = false;

	/**
	 * Send a single or multiple text-based emails in ISO or UTF encoding. Typically you should use template lib's email sender to send HTML.
	 * @param string $from The email which is displayed as the from field. Supports "My Name <me@example.com>" format.
	 * @param string|array $to The email to which this message should be sent. You can specify multiple emails as comma-separated list or as array. Supports "My Name <me@example.com>" format.
	 * @param string $subject A string with the email's subject.
	 * @param string $body The email's body in HTML or text format.
	 * @param bool|string $bcc If set, a copy of the email will be sent (bcc) to the specified email address. By default, no copy is sent.
	 * @param bool|array $additional_headers Any additional email headers you may want to send defined as a key/value pair.
	 * @param bool|integer $send_at Unix timestamp of the delayed sending or false if no delay is needed
	 * @return boolean True if successful, false otherwise. Only returns the result of the last one sent. To get results for multiple recipients, use send_single().
	 */
	public function send($from, $to, $subject, $body, $bcc = false, $additional_headers = false, $send_at = false){
		// Figure out my recipients
		if(is_array($to)) $recipients = $to;
		else $recipients = explode(',', $to);
		// Result is false by default until any one is sent successfully
		$result = false;
		if(!is_array($recipients)) return $this->zajlib->warning("Invalid recipients specified for email send method. Must be an array, a string, or a comma-separated list.");
		// Now send to each recipient, but bcc only to first
		foreach($recipients as $recipient){
			// Send a single message
			$result = $this->send_single($from, $recipient, $subject, $body, $bcc, $additional_headers, $send_at);
			// Don't send BCC after first one
			$bcc = false;
		}
		return $result;
	}

	/**
	 * Send a single text-based email in ISO or UTF encoding. Only use this if you require success reports for each recipient.
	 * @param string $from The email which is displayed as the from field. Supports "My Name <me@example.com>" format.
	 * @param string|array $to The email to which this message should be sent. You can specify multiple emails as comma-separated list or as array. Supports "My Name <me@example.com>" format.
	 * @param string $subject A string with the email's subject.
	 * @param string $body The email's body.
	 * @param bool|string $bcc If set, a copy of the email will be sent (bcc) to the specified email address. By default, no copy is sent.
	 * @param bool|array $additional_headers Any additional email headers you may want to send defined as a key/value pair. You can send a plain text version with the key 'TextBody'.
	 * @param bool|integer $send_at Unix timestamp of the time at which to send the email (in case of delayed send) or false if no delay is needed. Not all providers support this feature.
	 * @return boolean True if successful, false otherwise.
	 */
	private function send_single($from, $to, $subject, $body, $bcc = false, $additional_headers = false, $send_at = false){
		// Load up my old legacy file name, if it exists @todo remove this eventually, as it is deprecated
		$this->zajlib->config->load('email_smtp.conf.ini', false, false, false);
		if(empty($this->zajlib->config->variable->email_use_api)){
			// Load up new one!
			$this->zajlib->config->load('email.conf.ini');
		}

		// Get my email data for from and to
		$from_data = $this->get_named_email($from);
		$to_data = $this->get_named_email($to);

		// Check if $to is valid
			// @todo We should still log the message in EmailLog, so create a separate warning method for email lib that logs, then sends warning...
		if(!$this->valid($to_data->email, true)) return $this->zajlib->warning("Invalid email provided in To: ".$to);
		if(!$this->valid($from_data->email, true)) return $this->zajlib->warning("Invalid email provided in From: ".$from);

		// Check if $send_at is valid, send warning if not
		if($send_at && !is_numeric($send_at)){
			$this->zajlib->warning('Invalid unix timestamp '.$send_at.' for "send_at" parameter!');
		}

		// Email API required
		if($this->zajlib->config->variable->email_use_api) {
			// Which email provider should we use?
			$email_provider = $this->zajlib->config->variable->email_provider;

			// Note: Plain text (if set) is stored in $additional_headers['TextBody']

			// Check if provider is supported
			if(method_exists($this, $email_provider)){
				$responses = $this->$email_provider($from, $to, $subject, $body, $bcc, $additional_headers, $send_at);
			}
			else{
				$responses = new stdClass(); // just here to avoid PhpStorm warnings!
				$this->zajlib->warning("Email delivery provider $email_provider is not supported.");
			}

			// Set API specific responses
			switch($email_provider){
				case 'postmark':
					$status_prop = $responses->Message;
					$status_ok = 'OK';
					break;
				case 'sendgrid':
					$status_prop = $responses->message;
					$status_ok = 'success';
					break;
				case 'mandrill':
					if(is_array($responses)) $status_prop = $responses[0]->status;
					else $status_prop = $responses->status;
					$status_ok = ($send_at > time())?'scheduled':'sent';
					break;
				default:
					// Failed send, since unsupported provider was requested
					$status_prop = false;
					$status_ok = true;
					break;
			}

			// Was it a success? Let's compare the status property to the ok status...
			$success = (isset($status_prop) && $status_prop == $status_ok);

			// If database is enabled, create a log entry
			if($this->zajlib->zajconf['mysql_enabled']) {
				$status = ($success) ? 'sent' : 'failed';
				EmailLog::create_from_email($subject, $from, $to, $body, $bcc, $additional_headers, $send_at, $status, json_encode($responses));
			}

			if($success) return true;
			else return $this->zajlib->warning("Failed to send email to $to ".print_r($responses, true));
		}
		else{
			// @todo Add mail() support in this case
			return $this->zajlib->warning("Failed to send email to $to, no email API activated.");
		}

	}

	/**
	 * Send an email via Mandrill API.
	 * @link https://mandrillapp.com/api/docs/messages.html
	 * @param string $from The sender's email address in "John Doe <john@doe.com>" or "john@doe.com" format.
	 * @param string $to The recipient of the email in "John Doe <john@doe.com>" or "john@doe.com" format.
	 * @param string $subject The subject of the email.
	 * @param string $body The body of the email in HTML or text format.
	 * @param bool|string $bcc Add a BCC recipient or leave as false to skip this.
	 * @param bool|array $additional_headers Any additional email headers you may want to send defined as a key/value pair. You can send a plain text version with the key 'TextBody'.
	 * @param bool|integer $send_at Unix timestamp of the delayed sending or false if no delay is needed. Not all providers support this feature.
	 * @return stdClass The decoded JSON response from the API.
	 */
	private function mandrill($from, $to, $subject, $body, $bcc = false, $additional_headers = false, $send_at = false){
		// Create defaults
		if(empty($tag)) $tag = $this->zajlib->domain;
		// Build my headers
		$pheader = array(
			'Accept'=>'application/json',
			'Content-type'=>'application/json',
			'User-Agent'=>'Outlast Framework',
		);

		// Add text body based on actual body or stripped body
		if($additional_headers !== false) $txtbody = $additional_headers['TextBody'];
		else $txtbody = strip_tags($body);

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

		// For delayed mail delivery
		if($send_at) {
			$send_date = new DateTime("@$send_at");
			$pbody['send_at'] = $send_date->format('Y-m-d H:i:s');
		}

		// Add bcc if needed
		if(!empty($bcc)) $pbody['Bcc'] = $bcc;

		// Generate curl request
		$session = curl_init(MANDRILL_API_SEND_URL);

		curl_setopt($session, CURLOPT_POST, true);
		curl_setopt($session, CURLOPT_HTTPHEADER, $pheader);
		curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($pbody));
		curl_setopt($session, CURLOPT_HEADER, false);
		curl_setopt($session, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

		// obtain response
		$response = curl_exec($session);
		curl_close($session);

		// Post it and return it!
		return json_decode($response);
	}

	/**
	 * Send an email via Postmark API.
	 * @link http://developer.postmarkapp.com/developer-build.html
	 * @param string $from The sender's email address in "John Doe <john@doe.com>" or "john@doe.com" format.
	 * @param string $to The recipient of the email in "John Doe <john@doe.com>" or "john@doe.com" format.
	 * @param string $subject The subject of the email.
	 * @param string $body The body of the email in HTML or text format.
	 * @param bool|string $bcc Add a BCC recipient or leave as false to skip this.
	 * @param bool|array $additional_headers Any additional email headers you may want to send defined as a key/value pair.
	 * @param bool|integer $send_at Unix timestamp of the delayed sending or false if no delay is needed. Not all providers support this feature.
	 * @return stdClass The decoded JSON response from the API.
	 */
	private function postmark($from, $to, $subject, $body, $bcc = false, $additional_headers = false, $send_at = false){
		// Warn if send at used with Postmark
		if(!$this->postmark_warning_sent && $send_at !== false) {
			$this->zajlib->warning("Postmark does not support delayed mail delivery!");
			$this->postmark_warning_sent = true;
		}

		// Tag with domain
		$tag = $this->zajlib->domain;
		// Build my headers
		$pheader = array(
			'Accept'=>'application/json',
			'Content-type'=>'application/json',
			'X-Postmark-Server-Token'=>$this->zajlib->config->variable->email_api_key,
		);

		// Calculate text body based on actual body or stripped body
		if($additional_headers !== false){
			$txtbody = $additional_headers['TextBody'];
			unset($additional_headers['TextBody']);
		}
		else $txtbody = strip_tags($body);

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

		// Add other headers if needed
		if($additional_headers !== false){
			// Standard headers
			if(!empty($additional_headers['Cc'])){
				$pbody['Cc'] = $additional_headers['Cc'];
				unset($additional_headers['Cc']);
			}
			if(!empty($additional_headers['Tag'])){
				$pbody['Tag'] = $additional_headers['Tag'];
				unset($additional_headers['Tag']);
			}
			if(!empty($additional_headers['ReplyTo'])){
				$pbody['ReplyTo'] = $additional_headers['ReplyTo'];
				unset($additional_headers['ReplyTo']);
			}
			if(!empty($additional_headers['Reply-To'])){
				$pbody['ReplyTo'] = $additional_headers['Reply-To'];
				unset($additional_headers['Reply-To']);
			}
			if(!empty($additional_headers['Bcc'])){
				$pbody['Bcc'] = $additional_headers['Bcc'];
				unset($additional_headers['Bcc']);
			}

			// Other headers
			if(count($additional_headers) > 0){
				$pbody['Headers'] = [];
				foreach($additional_headers as $key=>$value){
					$pbody['Headers'][] = ['Name'=>$key, 'Value'=>$value];
				}
			}
		}

		// Post it and return it!
		return json_decode($this->zajlib->request->post(POSTMARK_API_SEND_URL, json_encode($pbody), false, $pheader));
	}

	/**
	 * Send an email via SendGrid SMTP API.
	 * @link https://sendgrid.com/docs/API_Reference/SMTP_API/using_the_smtp_api.html
	 * @param string $from The sender's email address in "John Doe <john@doe.com>" or "john@doe.com" format.
	 * @param string $to The recipient of the email in "John Doe <john@doe.com>" or "john@doe.com" format.
	 * @param string $subject The subject of the email.
	 * @param string $body The body of the email in HTML or text format.
	 * @param bool|string $bcc Add a BCC recipient or leave as false to skip this.
	 * @param bool|array $additional_headers Any additional email headers you may want to send defined as a key/value pair.
	 * @param bool|integer $send_at Unix timestamp of the delayed sending or false if no delay is needed. Not all providers support this feature.
	 * @return stdClass The decoded JSON response from the API.
	 */
	private function sendgrid($from, $to, $subject, $body, $bcc = false, $additional_headers = false, $send_at = false) {
		// Separate emails
		$from = $this->get_named_email($from);
		$to = $this->get_named_email($to);

		// Calculate text body based on actual body or stripped body
		if($additional_headers !== false) $txtbody = $additional_headers['TextBody'];
		else $txtbody = strip_tags($body);

		$pbody = array(
			'api_user'=>$this->zajlib->config->variable->email_api_user,
			'api_key'=>$this->zajlib->config->variable->email_api_key,
			'from'=> $from->email,
			'fromname'=> $from->name,
			'to'=>$to->email,
			'toname'=>$to->name,
			'subject'=>$subject,
			'html'=>$body,
			'text'=>$txtbody
		);

		// Add bcc if needed
		if(!empty($bcc)) $pbody['Bcc'] = $bcc;

		// Delayed send requested?
		if($send_at) {
			$pbody['x-smtpapi'] = json_encode(array("send_at" => $send_at));
		}

		// Generate curl request
		$session = curl_init(SENDGRID_API_SEND_URL);
		// Tell curl to use HTTP POST
		curl_setopt ($session, CURLOPT_POST, true);
		// Tell curl that this is the body of the POST
		curl_setopt ($session, CURLOPT_POSTFIELDS, $pbody);
		// Tell curl not to return headers, but do return the response
		curl_setopt($session, CURLOPT_HEADER, false);
		// Tell PHP not to use SSLv3 (instead opting for TLS)
		curl_setopt($session, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

		// obtain response
		$response = curl_exec($session);
		curl_close($session);

		// Post it and return it!
		return json_decode($response);
	}

	/**
	 * Send a UTF-encoded HTML email.
	 * @param string $from The email which is displayed as the from field.
	 * @param string $to The email to which this message should be sent.
	 * @param string $subject A string with the email's subject.
	 * @param string $body The email's body which should be in HTML.
	 * @param bool|string $bcc If set, a copy of the email will be sent (bcc) to the specified email address. By default, no copy is sent.
	 * @param bool|array $additional_headers Any additional email headers you may want to send defined as a key/value pair.
	 * @param bool|integer $send_at Unix timestamp of the delayed sending or false if no delay is needed
	 * @param bool|string $text_body If set, text-version will be set to this. If not set, text version will be a strip-tagged version.
	 * @deprecated Use send() instead with the TextBody additional header.
	 * @return boolean True if successful, false otherwise.
	 */
	public function send_html($from, $to, $subject, $body, $bcc = false, $additional_headers = false, $send_at = false, $text_body = false){
		// Create a plain text version (if not set by default)
			if(empty($text_body)) $text_body = strip_tags($this->zajlib->text->brtonl($body));
		// Create additional headers if not exists
			if($additional_headers == false) $additional_headers = [];
			$additional_headers['TextBody'] = $text_body;
		// Now send
			return $this->send($from, $to, $subject, $body, $bcc, $additional_headers, $send_at);
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
