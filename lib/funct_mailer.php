<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2023 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

/** mailer - function to send mails to users
 *  @arg $from        - single contact (see below)
 *  @arg $to          - single or multiple contacts (see below)
 *  @arg $cc          - none, single or multiple contacts (see below)
 *  @arg $bcc         - none, single or multiple contacts (see below)
 *  @arg $replyto     - none, single or multiple contacts (see below)
 *                      note that this value is used when hitting reply (overriding the default of using from)
 *  @arg $subject     - the email subject
 *  @arg $body        - the email body, in HTML format.  If content_text is not set, the function will attempt to extract
 *                      from the HTML format.
 *  @arg $body_text   - the email body in TEXT format.  If set, it will override the stripping tags method
 *  @arg $attachments - the emails attachments as an array
 *  @arg $headers     - an array of name value pairs representing custom headers.
 *  @arg $html        - if set to true, html is the default, otherwise text format will be used
 *
 *  For contact parameters, they can accept arrays containing zero or more values in the forms of:
 *      'email@email.com,email2@email.com,email3@email.com'
 *      array('email1@email.com' => 'My email', 'email2@email.com' => 'Your email', 'email3@email.com' => 'Whose email')
 *      array(array('email' => 'email1@email.com', 'name' => 'My email'), array('email' => 'email2@email.com',
 *          'name' => 'Your email'), array('email' => 'email3@email.com', 'name' => 'Whose email'))
 *
 *  The $from field will only use the first contact specified.  If no contact is provided for $replyto
 *  then $from is used for that too. If $from is empty, it will default to cacti@<server> or if no server name can
 *  be found, it will use cacti@cacti.net
 *
 *  The $attachments parameter may either be a single string, or a list of attachments
 *  either as strings or an array.  The array can have the following keys:
 *
 *  filename    : name of the file to attach (display name for graphs)
 *  display     : displayed name of the attachment
 *  mime_type   : MIME type to be set against the attachment.  If blank or missing mailer will attempt to auto detect
 *  attachment  : String containing attachment for image-based attachments (<GRAPH> or <GRAPH:#> activates graph mode
 *                and requires $body parameter is HTML containing one of those values)
 *  inline      : Whether to attach 'inline' (default for graph mode) or as 'attachment' (default for all others)
 *  encoding    : Encoding type, normally base64
 */
function v1_2_0_mailer($from, $to, $cc, $bcc, $replyto, $subject, $body, $body_text = '', $attachments = '', $headers = '', $html = true) {
	global $config;

	include_once($config['include_path'] . '/phpmailer/PHPMailerAutoload.php');

	// Set the to information
	if (empty($to)) {
		return __('Mailer Error: No <b>TO</b> address set!!<br>If using the <i>Test Mail</i> link, please set the <b>Alert e-mail</b> setting.', 'reportit');
	}

	// Create the PHPMailer instance
	$mail = new PHPMailer;

	// Set a reasonable timeout of 5 seconds
	$timeout = read_config_option('settings_smtp_timeout');
	if (empty($timeout) || $timeout < 0 || $timeout > 300) {
		$mail->Timeout = 5;
	} else {
		$mail->Timeout = $timeout;
	}

	$how = read_config_option('settings_how');
	if ($how < 0 || $how > 2) {
		$how = 0;
	}

	if ($how == 0) {
		$mail->isMail();
	} elseif ($how == 1) {
		$mail->Sendmail = read_config_option('settings_sendmail_path');
		$mail->isSendmail();
	} elseif ($how == 2) {
		$mail->isSMTP();
		$mail->Host     = read_config_option('settings_smtp_host');
		$mail->Port     = read_config_option('settings_smtp_port');

		if (read_config_option('settings_smtp_username') != '') {
			$mail->SMTPAuth = true;
			$mail->Username = read_config_option('settings_smtp_username');

			if (read_config_option('settings_smtp_password') != '') {
				$mail->Password = read_config_option('settings_smtp_password');
			}
		} else {
			$mail->SMTPAuth = false;
		}

		$secure  = read_config_option('settings_smtp_secure');
		if (!empty($secure) && $secure != 'none') {
			$mail->SMTPSecure = true;
			if (substr_count($mail->Host, ':') == 0) {
				$mail->Host = $secure . '://' . $mail->Host;
			}
		} else {
			$mail->SMTPAutoTLS = false;
			$mail->SMTPSecure = false;
		}
	}

	/* perform data substitution */
	if (strpos($subject, '|date_time|') !== false) {
		$date = read_config_option('date');
		if (!empty($date)) {
			$time = strtotime($date);
		} else {
			$time = time();
		}

		$subject = str_replace('|date_time|', date(CACTI_DATE_TIME_FORMAT, $time), $subject);
	}

	/*
	 * Set the from details using the variable passed in
	 * - if name is blank, use setting's name
	 * - if email is blank, use setting's email, otherwise default to
	 *   cacti@<server> or cacti@cacti.net if no known server name
	 */
	$from = v1_2_0_parse_email_details($from, 1);

	// from name was empty, use value in settings
	if (empty($from['name'])) {
		$from['name'] = read_config_option('settings_from_name');
	}

	// from email was empty, use email in settings
	if (empty($from['email'])) {
		$from['email'] = read_config_option('settings_from_email');
	}

	if (empty($from['email'])) {
		if (isset($_SERVER['HOSTNAME'])) {
			$from['email'] = 'Cacti@' . $_SERVER['HOSTNAME'];
		} else {
			$from['email'] = 'Cacti@cacti.net';
		}

		if (empty($from['name'])) {
			$from['name'] = 'Cacti';
		}
	}

	$fromText  = v1_2_0_add_email_details(array($from), $result, array($mail, 'setFrom'));

	if ($result == false) {
		cacti_log('ERROR: ' . $mail->ErrorInfo, false, 'MAILER');
		return $mail->ErrorInfo;
	}

	// Convert $to variable to proper array structure
	$to        = v1_2_0_parse_email_details($to);
	$toText    = v1_2_0_add_email_details($to, $result, array($mail,'addAddress'));

	if ($result == false) {
		cacti_log('ERROR: ' . $mail->ErrorInfo, false, 'MAILER');
		return $mail->ErrorInfo;
	}

	$cc        = v1_2_0_parse_email_details($cc);
	$ccText    = v1_2_0_add_email_details($cc, $result, array($mail,'addCC'));

	if ($result == false) {
		cacti_log('ERROR: ' . $mail->ErrorInfo, false, 'MAILER');
		return $mail->ErrorInfo;
	}

	$bcc       = v1_2_0_parse_email_details($bcc);
	$bccText   = v1_2_0_add_email_details($bcc, $result, array($mail,'addBCC'));

	if ($result == false) {
		cacti_log('ERROR: ' . $mail->ErrorInfo, false, 'MAILER');
		return $mail->ErrorInfo;
	}

	$replyto   = v1_2_0_parse_email_details($replyto);
	$replyText = v1_2_0_add_email_details($replyto, $result, array($mail,'addReplyTo'));

	if ($result == false) {
		cacti_log('ERROR: ' . $mail->ErrorInfo, false, 'MAILER');
		return $mail->ErrorInfo;
	}

	$body = str_replace('<SUBJECT>', $subject,   $body);
	$body = str_replace('<TO>',      $toText,    $body);
	$body = str_replace('<CC>',      $ccText,    $body);
	$body = str_replace('<FROM>',    $fromText,  $body);
	$body = str_replace('<REPLYTO>', $replyText, $body);

	// Set the subject
	$mail->Subject = $subject;

	// Support i18n
	$mail->CharSet = 'UTF-8';
	$mail->Encoding = 'base64';

	// Set the wordwrap limits
	$wordwrap = read_config_option('settings_wordwrap');
	if ($wordwrap == '') {
		$wordwrap = 76;
	} elseif ($wordwrap > 9999) {
		$wordwrap = 9999;
	} elseif ($wordwrap < 0) {
		$wordwrap = 76;
	}

	$mail->WordWrap = $wordwrap;
	$mail->setWordWrap();

	$i = 0;

	// Handle Graph Attachments
	if (!empty($attachments) && !is_array($attachments)) {
		$attachments = array('attachment' => $attachments);
	}

	if (is_array($attachments) && sizeof($attachments)) {
		$graph_mode = (substr_count($body, '<GRAPH>') > 0);
		$graph_ids = (substr_count($body, '<GRAPH:') > 0);

		$default_opts = array(
			// MIME type to be set against the attachment
			'mime_type'  => '',
			// Display name of the attachment
			'filename'    => '',
			// String containing attachment for image-based attachments
			'attachment' => '',
			// Whether to attach inline or as attachment
			'inline'     => ($graph_mode || $graph_ids) ? 'inline' : 'attachment',
			// Encoding type, normally base64
			'encoding'   => 'base64',
		);

		foreach($attachments as $attachment) {
			if (!is_array($attachment)) {
				$attachment = array('attachment' => $attachment);
			}

			foreach ($default_opts as $opt_name => $opt_default) {
				if (!array_key_exists($opt_name, $attachment)) {
					$attachment[$opt_name] = $opt_default;
				}
			}

			if (!empty($attachment['attachment'])) {
				/* get content id and create attachment */
				$cid = getmypid() . '_' . $i . '@' . 'localhost';

				if (empty($attachment['filename'])) {
					$attachment['filename'] = basename($attachment['attachment']);
				}

				/* attempt to attach */
				if (!($graph_mode || $graph_ids)) {
					$result = $mail->addAttachment($attachment['attachment'], $attachment['filename'], $attachment['encoding'], $attachment['mime_type'], $attachment['inline']);
				} else {
					$result = $mail->addStringEmbeddedImage($attachment['attachment'], $cid, $attachment['filename'], 'base64', $attachment['mime_type'], $attachment['inline']);
				}

				if ($result == false) {
					cacti_log('ERROR: ' . $mail->ErrorInfo, false, 'MAILER');
					return $mail->ErrorInfo;
				}

				$i++;
				if ($graph_mode) {
					$body = str_replace('<GRAPH>', "<br><br><img src='cid:$cid'>", $body);
				} else if ($graph_ids) {
					/* handle the body text */
					switch ($attachment['inline']) {
						case 'inline':
							$body = str_replace('<GRAPH:' . $attachment['local_graph_id'] . ':' . $attachment['timespan'] . '>', "<img src='cid:$cid' >", $body);
							break;
						case 'attachment':
							$body = str_replace('<GRAPH:' . $attachment['local_graph_id'] . ':' . $attachment['timespan'] . '>', '', $body);
							break;
					}
				}
			}
		}
	}

	/* process custom headers */
	if (is_array($headers) && sizeof($headers)) {
		foreach($headers as $name => $value) {
			$mail->addCustomHeader($name, $value);
		}
	}

	// Set both html and non-html bodies
	$brs = array('<br>', '<br />', '</br>');
	if ($html) {
		$body  = $body . '<br>';
	}

	if (empty($body_text)) {
		$body_text = strip_tags(str_ireplace($brs, "\n", $body));
	}

	$mail->isHTML($html);
	$mail->Body    = ($html?$body:$body_text);
	if ($html && !empty($body_text)) {
		$mail->AltBody = $body_text;
	}

	$result  = $mail->send();
	$error   = $result ? '' : $mail->ErrorInfo;

	$message = sprintf("%s: Mail %s from '%s', to '%s', cc '%s', Subject '%s'%s",
		$result ? 'INFO' : 'WARNING',
		$result ? 'successfully sent' : 'failed',
		$fromText, $toText, $ccText, $subject,
		$result ? '' : ", Message: $error");

	cacti_log($message, false, 'MAILER');
	return $error;
}

function v1_2_0_add_email_details($emails, &$result, callable $addFunc) {
	$arrText = array();
	foreach ($emails as $e) {
		if (!empty($e['email'])) {
			//if (is_callable($addFunc)) {
			if (!empty($addFunc)) {
				$result = $addFunc($e['email'], $e['name']);
			}
			$arrText[] = v1_2_0_create_emailtext($e);
		}
	}
	$text = implode(',', $arrText);
	//print "add_email_sw_details(): $text\n";
	return $text;
}

function v1_2_0_parse_email_details($emails, $max_records = 0, $details = array()) {
	if (!is_array($emails)) {
		$emails = array($emails);
	}

	if (!is_array($details)) {
		$details = array($details);
	}

	$update = array();
	//print "parse_email_details(): max is $max_records\n";
	//var_dump($emails);
	foreach ($emails as $key => $input) {
		//print "parse_email_details(): input is " . clean_up_lines(var_export($input, true)) . "\n";
		if (!empty($input)) {
			if (!is_array($input)) {
				$emails = explode(',', $input);
				foreach($emails as $email) {
					//print "parse_email_details(): checking '" . trim($email) . "' ... \n";
					$e = trim($email);
					$d = v1_2_0_split_emaildetail($e);
					$details[] = $d;
				}
			} else {
				$has_name  = array_key_exists('name', $input);
				$has_email = array_key_exists('email', $input);
				if ($has_name || $has_email) {
					$name  = $has_name  ? $input['name']  : '';
					$email = $has_email ? $input['email'] : '';
				} else {
					$name  = array_key_exists(1, $input) ? $input[1] : '';
					$email = array_key_exists(0, $input) ? $input[0] : '';
				}
				$details[] = array('name' => trim($name), 'email' => trim($email));
			}
		}
	}

	if ($max_records == 1) {
		$results = count($details) ? $details[0] : array();
	} elseif ($max_records != 0 && $max_records < count($details)) {
		$results = array();
		foreach ($details as $d) {
			$results[] = $d;
			$max_records--;
			if ($max_records == 0) {
				break;
			}
		}
	} else {
		$results = $details;
	}

	return $results;
}

function v1_2_0_split_emaildetail($input) {
	if (!is_array($input)) {
		$sPattern = '/(?<address><[\w\.]+@([\w\d-]+\.)+[\w]{2,4}>)$/';
		$aMatch = preg_split($sPattern, trim($input), -1, PREG_SPLIT_DELIM_CAPTURE);
		//print "\n------[REGEX]------\n";
		//print_r($aMatch);
		//print "\n------[REGEX]------\n";
		if (isset($aMatch[2])) {
			$name = $aMatch[0];
			$email = trim($aMatch[1],'<> ');
		} else {
			$name = '';
			$email = trim($aMatch[0],'<> ');
		}
	} else {
		$name = $input[1];
		$mail = $input[0];
	}

	return array('name' => trim($name), 'email' => trim($email));
}

function v1_2_0_create_emailtext($e) {
	if (empty($e['email'])) {
		$text = '';
	} else {
		if (empty($e['name'])) {
			$text = $e['email'];
		} else {
			$text = $e['name'] . ' <' . $e['email'] . '>';
		}
	}

	return $text;
}
