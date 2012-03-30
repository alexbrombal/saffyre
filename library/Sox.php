<?

class Sox {

	protected $conn;
	public $host;
	public $port;
	public $timeout = 10;
	public $errno;
	public $errstr;
	protected $newline = "\r\n";

	protected $debug;

	public function __construct($host, $port, $timeout = 10, $connect = false) {
		$this->host = $host;
		$this->port = $port;
		$this->timeout = $timeout;
		if($connect) $this->connect();
	}

	public function connect()
	{
		$this->conn = fsockopen($this->host, $this->port, $this->errno, $this->errstr, $this->timeout);
	}

	public function conn()
	{
		return $this->conn;
	}
	
	public function port()
	{
		return $this->port;
	}
	
	public function host()
	{
		return $this->host;
	}
	
	public function write($string, $newline = true)
	{
		if(is_object($string) && method_exists($string, '__toString')) $string = call_user_func(array($string, '__toString'));
		fwrite($this->conn, $written = $string . ($newline ? $this->newline : ''));
		$this->debugStr("C: " . $written);
	}

	public function readline($trim = true)
	{
		$str = fgets($this->conn, 4096);
		$this->debugStr("S: " . $str);
		if($trim) $str = rtrim($str, "\r\n");
		return $str;
	}

	public function read($bytes)
	{
		$str = fread($this->conn, $bytes);
		$this->debugStr("S: {$str}[no newline]");
		return $str;
	}

	public function debug($on = true) {
		$this->debug = $on;
	}

	protected function debugStr($string)
	{
		if($this->debug)
		{
			$str = str_replace(
				array("\r\n", "\r", "\n"),
				array('<font color="#CCC">[\r\n]</font><br/>', '<font color="#CCC">[\r]</font><br/>', '<font color="#CCC">[\n]</font><br/>'),
				htmlspecialchars($string)
			);
			echo $str . (substr($str, -5)=='<br/>' ? '' : '<br/>') . "\n";
		}
	}

	public function close() {
		if(is_resource($this->conn)) fclose($this->conn);
	}

	public function newline($n) {
		$this->newline = $n;
	}

	public function __destruct() {
		$this->close();
	}

}