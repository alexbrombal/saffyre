<?

class SoxURL {

	public $original;
	public $scheme;
	public $user;
	public $pass;
	public $host;
	public $port;
	public $path;
	public $query;
	public $fragment;

	public function __construct($url)
	{
		$this->original = $url;

		$matches = array();
		preg_match('/^([^:]+):\/\//', $url, $matches);
		if(isset($matches[1])) $this->scheme = $matches[1];

		if($this->scheme)
			$url = substr($url, strlen($this->scheme)+3);

		preg_match('/^([^:@\/]+)(:|@)/', $url, $matches);
		if(isset($matches[1])) $this->user = $matches[1];

		if($this->user)
			$url = substr($url, strlen($this->user)+1);

		preg_match('/(^|@)([A-Za-z0-9\.\-]+)(:(\d+))?(\/|$)/', $url, $matches, PREG_OFFSET_CAPTURE);
		$this->host = $matches[2][0];
		$this->port = $matches[4][0];

		if($matches[2][1])
			$this->pass = substr($url, 0, $matches[2][1] - 1);
		$url = substr($url, $matches[5][1]);

		$qpos = strpos($url, '?');
		$fpos = strpos($url, '#');

		$pathend = $qpos && $fpos ? min($qpos, $fpos) : $qpos + $fpos; // the smallest non-zero

		$this->path = $pathend ? substr($url, 0, $pathend) : $url;

		if($fpos)
		{
			$this->fragment = substr($url, $fpos+1);
			$url = substr($url, 0, $fpos);
		}

		if($qpos) $this->query = substr($url, $qpos+1);
	}

}
