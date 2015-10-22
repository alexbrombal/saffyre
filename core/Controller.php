<?php

final class Controller
{
	public static $ext;
	public static $dir;

	private static $stack = array();

	public $isMainRequest;

	private $file = array();
	private $path = array();
	private $args = array();

	private $uri;
	private $method;
	private $get;
	private $post;
	private $cookie;
	private $headers;
	private $body;

	public $resultWasOutput;
	public $status;


	public function __construct($fromRequest, $path = null, $method = null, $get = array(), $post = array(), $cookies = array(), $headers = array(), $body = null)
	{
		if(!self::$dir || !is_dir(self::$dir))
			throw new Exception('Controller directory is not set or does not exist! (Use Controller::$dir = "")');

		self::$dir = rtrim(self::$dir, DIRECTORY_SEPARATOR) . '/';

		if (is_array($path))
			$path = '/' . Saffyre::cleanPath($path, true);

		$this->path = Saffyre::cleanPath($path);
		self::removeExtension($this->path);
		if(!$this->path) $this->path = array('_default');

		do {
			$file = implode(DIRECTORY_SEPARATOR, $this->path);
			if(is_file(self::$dir . "$file.php") && $this->file = $this->path) break;
			if(is_file(self::$dir . $file . DIRECTORY_SEPARATOR . '_default.php') && $this->file = array_merge($this->path, array('_default'))) break;
			array_unshift($this->args, $slug = array_pop($this->path));
		} while($slug);

		if(!$this->file) {
			throw new Exception('Invalid controller path. Maybe you don\'t have a _default.php file.');
		}


		$this->get = $fromRequest ? Q::fromRequest('get') : new Q();
		$this->post = $fromRequest ? Q::fromRequest('post') : new Q();
		$this->cookie = $fromRequest ? Q::fromRequest('cookie') : new Q();
		$this->headers = new Q();

		if ($fromRequest)
		{
			$this->uri = $_SERVER['REQUEST_URI'];
			$this->method = strtoupper($_SERVER['REQUEST_METHOD']);
			foreach ($_SERVER as $key => $value)
				if (strpos($key, 'HTTP_') === 0)
					$this->headers->{strtolower(str_replace(array('HTTP_', '_'), array('', '-'), $key))} = $value;
		}

		if ($path !== null) $this->uri = $path;
		if ($method !== null) $this->method = strtoupper($method);
		$this->get->__import($get);
		$this->post->__import($post);
		$this->cookie->__import($cookies);
		$this->headers->__import($headers);
		if ($body !== null) {
			if (is_object($body)) $body = json_encode($body);
			$this->body = $body;
		}
	}


	public function get($name = null)
	{
		if ($name === null) return clone $this->get;
		else return $this->get->{$name};
	}

	public function post($name = null)
	{
		if ($name === null) return clone $this->post;
		else return $this->post->{$name};
	}

	public function cookie($name = null)
	{
		if ($name === null) return clone $this->cookie;
		else return $this->cookie->{$name};
	}

	public function header($name)
	{
		return $this->header->{$name};
	}

	public function method() { return $this->method; }

	/**
	 * Return the request URI of this execution.
	 *
	 * @param boolean $query True to add the query string to the return value, false to omit.
	 * @param array $exceptQuery An array of parameter names that should not be included in the query string.
	 * @return string The request URI of this execution.
	 */
	public function request($includeQuery = false, $exceptQuery = array())
	{
		if ($exceptQuery && !is_array($exceptQuery))
			throw new Exception('$exceptQuery parameter must be null or array');
		if($includeQuery) $q = $this->get->buildQuery($exceptQuery);
		//$replacePathPrefixRegex = '/^\\/' . preg_quote(trim(URL_PATH, '/'), '/') . '/';
		//return '/' . trim(preg_replace($replacePathPrefixRegex, '', $this->uri), '/') . ($includeQuery && !empty($q) ? "?$q" : '');
		return '/' . trim($this->uri, '/') . ($includeQuery && !empty($q) ? "?$q" : '');
	}

	public function body() {
		if ($this->body === null)
			$this->body = file_get_contents('php://input');
		return $this->body;
	}

	/**
	 * Return an item from the path of this request, or an array containing the path.
	 *
	 * @param int $index The index to return, or null to return the entire array.
	 * @return string|array The path component requested by $index, or the entire path as an array.
	 * @uses Util
	 */
	public function path($index = null)
	{
		return $index === null ? $this->path : (isset($this->path[$index]) ? $this->path[$index] : '');
	}

	public function args($index = null)
	{
		if(is_numeric($index))
			return(isset($this->args[$index]) ? $this->args[$index] : '');
		else
			return $this->args;
	}

	public function isInternal()
	{
		return !$this->isMainRequest;
	}


	private function executeGlobal()
	{
		$file = array();
		$args = $this->path;
		while(true)
		{
			if(is_file(rtrim(self::$dir . implode(DIRECTORY_SEPARATOR, $file), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '_global.php'))
			{
				$controller = clone $this;
				$controller->file = array_merge($file, array('_global'));
				$result = $controller->doExecute();
				if ($controller->status !== null)
					$this->status = $controller->status;
				if($result === false)
					return false;
			}
			if(!$args) return;
			array_push($file, array_shift($args));
		}
	}

	public function execute($withGlobal = true)
	{
		if ($withGlobal)
			if ($this->executeGlobal() === false)
				return;
		return $this->doExecute();
	}

	private function doExecute()
	{
		array_push(self::$stack, $this);
		chdir(dirname(self::$dir . implode(DIRECTORY_SEPARATOR, $this->file) . '.php'));

		ob_start();
		$result = include self::$dir . implode(DIRECTORY_SEPARATOR, $this->file) . '.php';
		if ($result === 1) $result = null;
		$output = ob_get_flush();

		if ($result === null && $output) {
			$result = $output;
			$this->resultWasOutput = true;
		}

		array_pop(self::$stack);

		if (is_int($result) && $result >= 100 && $result < 600)
		{
			$this->status = $result;
			$result = null;
		}
		else if ($this->status === null)
			$this->status = 200;

		return $result;
	}


	/**
	 * This is a convenience method to set an error code and return a response body in one line.
	 * @example return $this->(400, '{ "message": "Bad request" }');
	 */
	public function error($code, $response = null)
	{
		$this->status = $code;
		return $response;
	}





	public static function run($fromRequest, $path = null, $method = null, $get = array(), $post = array(), $cookies = array(), $headers = array(), $body = null, &$status = null)
	{
		$c = new Controller($fromRequest, $path, $method, $get, $post, $cookies, $headers, $body);
		$result = $c->execute();
		$status = $c->status;
		return $result;
	}

	private static function removeExtension(&$args)
	{
		if(!self::$ext || !$args) return;
		end($args);
		$args[key($args)] = preg_replace('/\.('.self::$ext.')$/', '', $args[key($args)]);
	}

	public static function current()
	{
		return count(self::$stack) > 0 ? self::$stack[count(self::$stack)-1] : null;
	}

}