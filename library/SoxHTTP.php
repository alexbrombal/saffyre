<?

class SoxHTTP extends Sox {

	public $url;
	public $method;
	private $request;
	public $status;
	public $statusCode;
	public $statusMsg;
	public $response;

	public function __construct($url, $postData = null, $timeout = 10)
	{
		if(is_string($url)) $url = new SoxURL($url);
		$this->url = $url;
		$this->newline = "\r\n";
		$this->method = $postData === null ? 'GET' : 'POST';
		$this->request = new SoxMIME();
		$this->request->Host = $url->host;
		$this->request->{'User-Agent'} = 'Saffyre Sox Client 1.0';
		$this->request->{'Content-Length'} = true;
		$this->request->body = $postData;
		parent::__construct($url->host, $url->port ? $url->port : 80, $timeout, false);
	}

	public function send()
	{
		if($this->method == 'POST')
		{
			if(is_array($this->body))
			{
				if(!$this->request->{'Content-Type'})
					$this->request->{'Content-Type'} = 'multipart/form-data';

				$body = $this->body;
				foreach($body as $name => $value)
				{
					if($value instanceof SoxMIME) continue;
					$body[$name] = new SoxMIME(array(
						'Content-Type' => 'text/plain',
						'Content-Disposition' => "form-data; name=\"$name\""
					), $value);
				}
				$this->body = $body;
			}
			else
			{
				if(!$this->request->{'Content-Type'})
					$this->request->{'Content-Type'} = 'application/x-www-form-urlencoded';
			}
		}

		$this->connect();

		$path = $this->url->path . ($this->url->query ? '?'.$this->url->query : '');
		$this->write("$this->method ".($path ? $path : '/')." HTTP/1.1");

		$this->write($this->request, false);


		$this->status = $this->readline();
		preg_match('/HTTP\/... +(\d\d\d) +(.*)/', $this->status, $matches);
		$this->statusCode = $matches[1];
		$this->statusMsg = $matches[2];

		$headers = '';
		while(($h = $this->readline()) && $headers .= "$h\r\n");
		$headers .= "\r\n";

		$this->response = SoxMIME::fromString($headers);

		if($length = $this->response->{'Content-Length'})
		{
			$this->request->body = $this->read($length);
		}
		elseif($this->response->{'Transfer-Encoding'} == 'chunked')
		{
			while($length = $this->readline())
			{
				$this->response->body .= $this->read(hexdec($length));
				$this->readline();
			}
		}
		return $this->response->body;
	}

	public static function request($url, $postData = null, &$response = null, $timeout = 10, $debug = false)
	{
		$response = new SoxHTTP($url, $postData, $timeout);
		$response->debug($debug);
		return $response->send();
	}

	public function __get($name)
	{
		return $this->request->$name;
	}

	public function __set($name, $value)
	{
		$this->request->$name = $value;
	}

}