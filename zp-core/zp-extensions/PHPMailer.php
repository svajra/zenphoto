<?php
/**
 * Mailing using PHPMailer classes
 *
 * Configure the plugin options as necessary.
 *
 * @package plugins
 */
$plugin_is_filter = 8|CLASS_PLUGIN;
$plugin_description = gettext("Zenphoto outgoing mail handler based on the <em>PHPMailer</em> class mailing facility.");
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.4.0';
$plugin_URL = '*';
$plugin_disable = (version_compare(PHP_VERSION, '5.0.0') != 1) ? gettext('PHP version 5 or greater is required.') : false;
if ($plugin_disable) {
	setOption('zp_plugin_PHPMailer',0);
} else {
	$option_interface = 'zp_PHPMailer';
	zp_register_filter('sendmail', 'zenphoto_PHPMailer');
}

/**
 * Option handler class
 *
 */
class zp_PHPMailer {


	/**
	 * class instantiation function
	 *
	 * @return zp_PHPMailer
	 */
	function zp_PHPMailer() {
		setOptionDefault('PHPMailer_mail_protocol','sendmail');
		setOptionDefault('PHPMailer_server','');
		setOptionDefault('PHPMailer_pop_port','110');
		setOptionDefault('PHPMailer_smtp_port','25');
		setOptionDefault('PHPMailer_user','');
		setOptionDefault('PHPMailer_password','');
		setOptionDefault('PHPMailer_secure',0);
	}

	/**
	 * Reports the supported options
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		return array(	gettext('Mail protocol') => array('key' => 'PHPMailer_mail_protocol', 'type' => OPTION_TYPE_RADIO,
										'buttons' => array('POP3'=>'pop3', 'SMTP'=>'smtp', 'SendMail'=>'sendmail'),
										'desc' => gettext('Select the mail protocol you wish to be used.')),
									gettext('Outgoing mail server') => array('key' => 'PHPMailer_server', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => gettext('Outgoing mail server.')),
									gettext('Secure mail') => array('key' => 'PHPMailer_secure', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext('Set to use <em>SSL</em> protocol.')),
									gettext('Mail user') => array('key' => 'PHPMailer_user', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => gettext('<em>User ID</em> for mail server.')),
									gettext('Mail password') => array('key' => 'PHPMailer_password', 'type' => OPTION_TYPE_CUSTOM,
										'desc' => gettext('<em>Password</em> for mail server.')),
									gettext('POP port') => array('key' => 'PHPMailer_pop_port', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => gettext('POP port number.')),
									gettext('SMTP port') => array('key' => 'PHPMailer_smtp_port', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => gettext('SMTP port number.'))
									);
	}

	/**
	 * Custom opton handler--creates the clear ratings button
	 *
	 * @param string $option
	 * @param string $currentValue
	 */
	function handleOption($option, $currentValue) {
		if($option=="PHPMailer_password") {
			?>
			<input type="password" size="40" name="<?php echo $option; ?>" style="width: 338px" value="<?php echo html_encode($currentValue); ?>">
			<?php
		}
	}

}

function zenphoto_PHPMailer($msg, $email_list, $subject, $message, $from_mail, $from_name, $cc_addresses) {
	require_once(dirname(__FILE__).'/PHPMailer/class.phpmailer.php');
	switch (getOption('PHPMailer_mail_protocol')) {
		case 'pop3':
			require_once(dirname(__FILE__).'/PHPMailer/class.pop3.php');
			$pop = new POP3();
			$authorized = $pop->Authorise(getOption('PHPMailer_server'), getOption('PHPMailer_pop_port'), 30, getOption('PHPMailer_user'), getOption('PHPMailer_password'), 0);
			$mail = new PHPMailer();
			$mail->IsSMTP();
			$mail->Port = getOption('PHPMailer_smtp_port');
			$mail->Host  = getOption('PHPMailer_server');
			break;
		case 'smtp':
			$mail = new PHPMailer();
			$mail->SMTPAuth = true; // enable SMTP authentication
			$mail->IsSMTP();
			$mail->Username = getOption('PHPMailer_user');
			$mail->Password = getOption('PHPMailer_password');
			$mail->Host  = getOption('PHPMailer_server');
			$mail->Port = getOption('PHPMailer_smtp_port');
			break;
		case 'sendmail':
			$mail = new PHPMailer();
			$mail->IsSendmail();
			break;
	}
	if (getOption('PHPMailer_secure')) {
		$mail->SMTPSecure = "ssl";
	}
	$mail->CharSet = 'UTF-8';
	$mail->From = $from_mail;
	$mail->FromName = $from_name;
	$mail->Subject = $subject;
	$mail->Body = $message;
	$mail->AltBody = '';
	$mail->IsHTML(false);

	foreach ($email_list as $to_name=>$to_mail) {
		if (is_numeric($to_name)) {
			$mail->AddAddress($to_mail);
		} else {
			$mail->AddAddress($to_mail, $to_name);
		}
	}
	if (count($cc_addresses) > 0) {
		foreach ($cc_addresses as $cc_name=>$cc_mail) {
			$mail->AddCC($cc_mail);
		}
	}
	if (!$mail->Send()) {
		if (!empty($msg)) $msg .= '<br />';
		$msg .= sprintf(gettext('<code>PHPMailer</code> failed to send <em>%1$s</em>. ErrorInfo:%2$s'), $subject, $mail->ErrorInfo);
	}
	return $msg;
}

?>