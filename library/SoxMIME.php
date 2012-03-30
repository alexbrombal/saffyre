<?

class SoxMIME {

	public $headers = array();
	public $body;
	public $boundary;

	public function __construct($headers = array(), $body = '')
	{
		$this->headers = self::parseHeaders($headers);
		$this->body = $body;
	}


	public static function parseHeaders($str)
	{
		if(is_array($str)) return $str;

		$endpos = strpos($str, "\r\n\r\n");
		if($endpos !== false)
			$str = substr($str, 0, $endpos);

		if(!$str) return array();

		$headers = preg_split('/\r\n(?!\s)/', $str);
		foreach($headers as $key => $header)
		{
			$parts = explode(":", $header, 2);
			$name = $parts[0];
			$value = trim(preg_replace('/\s*\r\n\s+/', "\n", $parts[1]));
			if(isset($headers[$name])) {
				if(!is_array($headers[$name])) {
					$headers[$name] = array($headers[$name]);
				}
				$headers[$name][] = $value;
			} else
				$headers[$name] = $value;
			unset($headers[$key]);
		}

		return $headers;
	}

	public static function fromString($str)
	{
		if(!$str) return;

		$parts = explode("\r\n\r\n", $str, 2);

		if(!$parts || count($parts) != 2)
			throw new Exception('Invalid MIME body');

		$mime = new SoxMIME();
		$mime->headers = self::parseHeaders($parts[0]);
		$mime->body = $parts[1];

		return $mime;
	}

	public function bodyString()
	{
		if(is_string($this->body))
		{
			$body = $this->body;
			if($this->{'Content-Transfer-Encoding'} == 'base64')
				$body = wordwrap(base64_encode($body), 76, "\r\n", true);
			return $body;
		}

		if(is_array($this->body))
		{
			if(!$this->boundary) $this->boundary = md5(microtime().rand());
			$str = "This is a message with multiple parts in MIME format.\r\n";
			foreach($this->body as $part)
			{
				if(!$part instanceof SoxMIME) $part = new SoxMIME(null, $part);
				$str .= "--$this->boundary\r\n";
				$str .= $part->__toString()."\r\n";
			}
			$str .= "--$this->boundary--\r\n";
		}
		return $str;
	}

	public function __toString()
	{
		if(is_array($this->body))
		{
			if(!$this->{'Content-Type'})
				$this->{'Content-Type'} = 'multipart/mixed';
			if(!$this->boundary) $this->boundary = md5(microtime().rand());
			$this->{'Content-Type'} .= "; boundary=\"$this->boundary\"";
		}

		$bodyString = $this->bodyString();

		if($this->{'Content-Length'} === true)
			$this->{'Content-Length'} = strlen($bodyString);

		$str = '';
		foreach($this->headers as $name => $value)
			if($value !== false && $value !== null) $str .= "$name: $value\r\n";
		$str .= "\r\n";
		$str .= $bodyString;
		return $str;
	}


	public function __get($name)
	{
		return isset($this->headers[ucwords($name)]) ? $this->headers[ucwords($name)] : null;
	}

	public function __set($name, $value)
	{
		$this->headers[ucwords($name)] = $value;
	}

}
