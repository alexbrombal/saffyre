<?

class SoxMail
{
	private $to = array();
	private $cc = array();
	private $bcc = array();
	private $from;
	private $subject;
	private $headers = array();
	
	private $text;
	private $html;
	private $inlineImages = array();
	private $attachments = array();
	
	private $tls = false;
	private $tlsUsername;
	private $tlsPassword;
	
	
	public function text($text = null) { if($text === null) return $this->text; $this->text = $text; }
	public function html($html = null) { if($html === null) return $this->html; $this->html = $html; }

	public function to($string = null, $append = true) {
		if($string === null) return $this->to;
		if(!$append) $this->to = array();
		if(is_array($string)) { foreach($string as $key => $value) if(String::isEmail($key)) $this->to[$key] = $value; elseif($value) $this->to($value); }
		else $this->to += self::parseEmailString($string);
	}

	public function cc($string = null, $append = true) {
		if($string === null) return $this->cc;
		if(!$append) $this->cc = array();
		if(is_array($string)) { foreach($string as $key => $value) if(String::isEmail($key)) $this->cc[$key] = $value; elseif($value) $this->cc($value); }
		else $this->cc += self::parseEmailString($string);
	}

	public function bcc($string = null, $append = true) {
		if($string === null) return $this->bcc;
		if(!$append) $this->bcc = array();
		if(is_array($string)) { foreach($string as $key => $value) if(String::isEmail($key)) $this->bcc[$key] = $value; elseif($value) $this->bcc($value); }
		else $this->bcc += self::parseEmailString($string);
	}
	
	public function from($string = null)	{ if($string === null) return $this->from; $this->from = self::parseEmailString($string); }
	
	public function subject($subject = null) { 
		if($subject === null) return $this->subject;
		$this->subject = trim(preg_replace('/[\n\r]/', '', $subject)); 
	}
	
	public static function parseEmailString($string)
	{
		if(is_array($string)) return $string;
		$arr = explode(',', $string);
		$emails = array();
		foreach($arr as $a)
		{
			preg_match_all('/"?(.*?)"?\s*<?([A-Za-z0-9\.\+\-_]+@[A-Za-z0-9\.\+\-_]+)>?/', trim($a), $to);
			if(isset($to[2][0]) && isset($to[1][0])) $emails[$to[2][0]] = $to[1][0];
		}
		return $emails;
	}
	
	public static function emailArrayToString($array)
	{
		if(is_string($array)) return $array;
		$str = array();
		foreach($array as $email => $name)
			$str[] = $email && $name ? '"'.$name.'" <'.$email.'>' : $email;
		return implode(', ', $str);
	}
	
	public function addInlineImage($imageData, $contentType, $id)
	{
		$this->attachments[] = new SoxMIME(array(
				'Content-Type' => $contentType, 
				'Content-Disposition' => 'inline', 
				'Content-ID' => "<$id>",
				'Content-Transfer-Encoding' => 'base64'
		), $imageData);
		return $this;
	}
		
	public function addAttachmentData($contentType, $filename, $data)
	{
		$this->attachments[] = new SoxMIME(array('Content-Type' => $contentType, 'Content-Disposition' => 'attachment;filename="'.$filename.'"'), $data);
		return $this;
	}

	public function header($header, $value)
	{
		$this->headers[ucwords($header)] = $value;
		return $this;
	}
	
	public function getMIME()
	{
		$mime = new SoxMIME();
		
		$to = array();

		if($this->to) $mime->To = self::emailArrayToString($this->to);
		if($this->cc) $mime->Cc = self::emailArrayToString($this->cc);
		if($this->bcc) $mime->Bcc = self::emailArrayToString($this->bcc);
		if($this->from) $mime->From = self::emailArrayToString($this->from);
		
		$mime->Subject = $this->subject;
		
		if($this->headers)
			foreach($this->headers as $name => $value)
				$mime->$name = $value;
		
		if($this->text && $this->html && $this->attachments)
		{
			$mime->{'Content-Type'} = 'multipart/mixed';
			$mime->body = array(
				new SoxMIME(
					array('Content-Type' => 'multipart/alternative'),
					array(
						new SoxMIME(array('Content-Type' => 'text/plain'), $this->text),
						new SoxMIME(array('Content-Type' => 'text/html'), $this->html)
					)
				)
			);
			$mime->body = array_merge($mime->body, $this->attachments);
		}
		else if($this->text && $this->html)
		{
			$mime->{'Content-Type'} = 'multipart/alternative';
			$mime->body = array(
				new SoxMIME(array('Content-Type' => 'text/plain'), $this->text),
				new SoxMIME(array('Content-Type' => 'text/html'), $this->html)
			);
		}
		else if($this->html)
		{
			$mime->{'Content-Type'} = 'text/html';
			$mime->body = $this->html;
		}
		else if($this->text)
		{
			$mime->{'Content-Type'} = 'text/plain';
			$mime->body = $this->text;
		}
		
		return $mime;
	}
	
	public function tls($enabled, $username = null, $password = null)
	{
		$this->tls = (bool)$enabled;
		$this->tlsUsername = $username;
		$this->tlsPassword = $password;
		return $this;
	}
	
	public function send(Sox $conn = null, $serverFrom = null)
	{
		if(!$conn) $conn = new Sox('localhost', 25);

		$conn->connect();
		$this->read($conn);
		
		$conn->write('HELO '.($serverFrom ? $serverFrom : $conn->host));
		$this->read($conn);
		
		if($this->tls)
		{
			$conn->write('STARTTLS');
			$this->read($conn);
			
			if(!($conn->port() == 443 || substr($conn->host(), 0, 4) == 'ssl:'))
				@stream_socket_enable_crypto($conn->conn(), true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
			
			$conn->write('EHLO '.($serverFrom ? $serverFrom : $conn->host));
			$this->read($conn);
			
			$conn->write("AUTH PLAIN ".base64_encode("\0$this->tlsUsername\0$this->tlsPassword")."");
			$this->read($conn);
		}
		
		$regex = '/[^"<>]+@[A-Za-z0-9\.-]+/';
		
		reset($this->from);
		@list($from_email, $from_name) = each($this->from);
		if(!isset($from_email)) throw new Exception('Invalid deliverer');
		$conn->write("MAIL FROM: <$from_email>");
		$this->read($conn);
		

		foreach($this->to as $email => $name)
		{
			$conn->write("RCPT TO: <$email>");
			$conn->readline();
		}
		foreach($this->cc as $email => $name)
		{
			$conn->write("RCPT TO: <$email>");
			$conn->readline();
		}
		foreach($this->bcc as $email => $name)
		{
			$conn->write("RCPT TO: <$email>");
			$conn->readline();
		}


		$conn->write('DATA');
		$conn->readline();

		$body = $this->getMIME()->__toString();
		$body = str_replace("\n.", "\n..", $body);
		$conn->write($body);
		$conn->write('.');
		$conn->readline();

		$conn->write('QUIT');
		$conn->readline();

		$conn->close();
	}
	
	private function read(Sox $conn)
	{
		do {
			$line = $conn->readline();
		} while(substr($line, 3, 1) != ' ');
	}

}