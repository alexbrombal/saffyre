<?

class Email
{
	// Send emails from this address by default
	public static $default_from = '';
	public static $default_replyTo = '';
	public static $default_helo = '';

	// Send a debug copy (with extra information) to this address, regardless of debug mode
	public static $debug_all = false;

	// Turn debugging on for all emails by specifying an array of email addresses.
	// When debugging is on, only the addresses specified in this array will be able to receive original emails.
	public static $debug_to = false;

	public static $smtp_host;
	public static $smtp_port;
	public static $smtp_user;
	public static $smtp_password;

	public static function create()
	{
		$s = new SoxMail();
		$s->from(self::$default_from);
		$s->header('Reply-To', self::$default_replyTo);
		return $s;
	}


	public static function send(SoxMail $mail, $debug = false)
	{
		// $to, $cc, $bcc & $all contain the address that will actually be sent to (filtered by debug settings).
		// $origTo, $origCC, $origBCC, & $origAll contain the original addressees before filtering.

		$to = $origTo = $mail->to();
		$cc = $origCC = $mail->cc();
		$bcc = $origBCC = $mail->bcc();
		$origAll = $to + $cc + $bcc;

		if(self::$debug_to) {
			foreach($to as $email => $name)
				if(!in_array($email, self::$debug_to)) unset($to[$email]);
			foreach($cc as $email => $name)
				if(!in_array($email, self::$debug_to)) unset($cc[$email]);
			foreach($bcc as $email => $name)
				if(!in_array($email, self::$debug_to)) unset($bcc[$email]);
		}

		$all = $to + $cc + $bcc;
		$origSubject = $mail->subject();

		$mail->tls(true, self::$smtp_user, self::$smtp_password);

		if($all)
		{
			$mail->to($to, false);
			$mail->cc($cc, false);
			$mail->bcc($bcc, false);
			$c = new Sox(self::$smtp_host, self::$smtp_port);
			$c->debug($debug);
			$mail->send($c, self::$default_helo);
		}

		if(self::$debug_all)
		{
			$origTo = SoxMail::emailArrayToString($origTo);
			$origCC = SoxMail::emailArrayToString($origCC);
			$origBCC = SoxMail::emailArrayToString($origBCC);
			$origAll = SoxMail::emailArrayToString($origAll);
			$mail->to(self::$debug_all, false);
			$mail->cc('', false);
			$mail->bcc('', false);
			$mail->subject("Email sent to: $origTo");

			$header = "To: $origTo\n".
						"CC: $origCC\n".
						"BCC: $origBCC\n".
						"Delivered To: ".($all ? SoxMail::emailArrayToString($all)."  (Yes, it was really sent to them)" : '(nobody)')."\n".
						"Subject: $origSubject\n\n\n";
			$mail->text($header . $mail->text());
			if($mail->html()) $mail->html(nl2br(htmlentities($header)) . $mail->html());

			$c = new Sox(self::$smtp_host, self::$smtp_port);
			$c->debug($debug);

			$mail->send($c, self::$default_helo);
		}
	}
}