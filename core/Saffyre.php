<?php

/**
 * The main Saffyre class. This class is really just a namespace for core Saffyre methods. You cannot instantiate this class.
 *
 * @package Saffyre
 * @author Alex Brombal
 * @copyright 2011
 */
class Saffyre {

	private static $path;

	/**
	 * Adds a directory to php's include_path.
	 *
	 * @param string $path The absolute path of a directory to add to the include_path
	 * @param boolean $recursive Indicates whether all subfolders of $path should also be added to the include_path
	 */
	public static function includePath($path, $recursive = true)
	{
		$path = rtrim($path, '/');
		if(!is_dir($path)) return;
		set_include_path(get_include_path() . ":$path");
		if(!$recursive) return;
		$r = opendir($path);
		while($dir = readdir($r)) {
			if($dir == '.' || $dir == '..' || substr($dir, 0, 1) == '_' || substr($dir, 0, 1) == '.') continue;
			if(is_dir("$path/$dir"))
				self::includePath("$path/$dir", true);
		}
	}

	/**
	 * Return the request URI of this execution.
	 *
	 * @uses Q
	 * @param boolean $query True to add the query string to the return value, false to omit.
	 * @param array $exceptQuery An array of parameter names that should not be included in the query string.
	 * @return string The request URI of this execution.
	 */
	public static function request($query = false, $exceptQuery = array())
	{
		if($query) $q = Q::get()->buildQuery($exceptQuery);
		return '/' . ltrim(preg_replace('/'.preg_quote(URL_PATH, '/').'/', '', self::$path, 1), '/') .
					($query && !empty($q) ? "?$q" : '');
	}

	/**
	 * Return an item from the path of this execution, or an array containing the path.
	 *
	 * @param int $index The index to return, or null to return the entire array.
	 * @return string|array The path component requested by $index, or the entire path as an array.
	 * @uses Util
	 */
	public static function path($index = null)
	{
		$path = trim(URL_PATH, '/');
		$uri = preg_replace("(^/?$path|\?.*$)", '', self::$path);
		$path = self::cleanpath($uri);
		Util::array_clean($path);
		if($index === null) return $path;
		else {
			if(isset($path[$index])) return $path[$index];
			else return null;
		}
	}

	/**
	 * Cleans $path (an array of path components or a string path) by removing empty values and url-decoding the values.
	 *
	 * @param string|array $path The array of path components or string path to clean.
	 * @param boolean $string True to return a string path, false to return an array of components
	 * @return string|array The cleaned path, as a string or array
	 * @uses Util
	 */
	public static function cleanPath($path, $string = false)
	{
		if(!$path) return ($string ? '' : array());
		if(!is_array($path)) $path = explode('/', $path);
		$path = Util::array_clean($path);
		foreach($path as $key => $item)
			$path[$key] = urldecode($item);
		return ($string ? implode('/', $path) : array_values($path));
	}

	/**
	 * Redirects the current request using an HTTP 301 redirect, then terminates execution. Optionally sets extra headers.
	 *
	 * @param string $url The url to redirect to.
	 * @param array $headers Additional headers to set (each array value should be an entire header line).
	 */
	public static function redirect($url, $headers = array(), $temporary = false)
	{
		array_unshift($headers, 'HTTP/1.1 ' . ($temporary ? '302 Moved Temporarily' : '301 Moved Permanently'));
		if(preg_match('/^https?:\/\//', $url))
			header("Location: $url");
		else
			header('Location: ' . rtrim(URL_BASE, '/') . '/' . ltrim($url, '/'));
		if(is_array($headers)) foreach($headers as $header) header($header);
		exit;
	}

	/**
	 * Sends an HTTP error code header. This method DOES NOT terminate execution.
	 *
	 * @param int $number The error status code to send.
	 * @todo Add all error codes to this list.
	 */
	public static function httpError($number)
	{
		switch($number)
		{
			case 400:
				header("HTTP/1.1 400 Bad Request");
				return;
			case 404:
				header("HTTP/1.1 404 Not Found");
				return;
			case 403:
				header("HTTP/1.1 403 Forbidden");
				return;
			case 401:
				header("HTTP/1.1 401 Unauthorized");
				return;
		}
	}

	/**
	 * Indicates whether the request was an ajax call (based on the X-Requested-With header).
	 *
	 * @return boolean Whether this request was an ajax call.
	 */
    public static function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
    }

    /**
     * Indicates whether the request was secure.
     *
     * @return boolean
     */
    public static function isSSL()
    {
    	return !empty($_SERVER['HTTPS']);
    }

    public static function forceSSL()
    {
    	$url = URL_BASE . ltrim(Saffyre::request(), '/');
    	$ssl = preg_replace('/^http:/', 'https:', $url);
    	if($ssl != $url) Saffyre::redirect($ssl);
    }


    public static function isPost()
    {
    	return $_SERVER['REQUEST_METHOD'] == 'POST';
    }

    public static function isGet()
    {
    	return $_SERVER['REQUEST_METHOD'] == 'GET';
    }

    /**
     * A default exception handler for the Saffyre framework.
     *
     * @param Exception $e
     */
	public static function exceptionHandler(Exception $e) {
		echo "<br/><br/><b>{$e->getMessage()}</b><br/>".nl2br($e->getTraceAsString());
	}

	/**
	 * Requires HTTP Authentication. Execution dies after authentication fails.
	 *
	 * @param string $message Message displayed to the user
	 * @param array $creds An associative array of username => sha1(password) pairs.
	 * @param callback $failure A callback method that is called if authentication fails.
	 */
	public static function requireHTTPAuth($message, $creds, $failure = null)
	{
		//if(isset($_COOKIE['auth'])) return true;

		if(isset($_SERVER['PHP_AUTH_USER']))
		{
			if(isset($creds[$_SERVER['PHP_AUTH_USER']]) && sha1($_SERVER['PHP_AUTH_PW']) == $creds[$_SERVER['PHP_AUTH_USER']])
			{
				Q::cookie('auth', 1, 10000000);
				return true;
			}
			else
			{
				header("WWW-Authenticate: Basic realm=\"$message\"", null, 401);
				if($failure) call_user_func($failure);
				die();
			}
		}
		else
		{
			header("WWW-Authenticate: Basic realm=\"$message\"", null, 401);
			die('Forbidden');
		}
	}


	public static function forceTrailingSlash($slash = true)
	{
		$lastChar = substr(Saffyre::request(false), -1);
		if(!$_POST && strlen(Saffyre::request(false)) > 1 && ($slash ? $lastChar != '/' : $lastChar == '/'))
			Saffyre::redirect(rtrim(Saffyre::request(false), '/') . ($slash ? '/' : '') . ($_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : ''));
	}

	/**
	 * The main execution method for the Saffyre framework. The method should only be invoked ONCE and there is no guarantee that
	 * it will return execution (many methods and controllers will die or exit). This method starts an output buffer.
	 *
	 * @param string $path The path to execute, or the request URI if null
	 * @param boolean $return True to return the response, or false to flush it.
	 * @uses Controller
	 */
	public static function execute($path = null, $return = false)
	{
		ob_start();

		self::$path = preg_replace('/\?.*$/', '', $path ? self::cleanPath($path, true) : $_SERVER['REQUEST_URI']);

		$required = array("URL_BASE", "URL_PATH");
		$undefined = array();
		foreach($required as $const)
			if(!defined($const)) $undefined[] = $const;

		if($undefined)
			throw new Exception('The following constants are not defined: ' . join(', ', $undefined));

		if(!defined('ENCODING'))
			define('ENCODING', 'UTF-8');

		header('Content-type: text/html; charset='.ENCODING);
		header("X-Powered-By: Saffyre Framework 1.0", true);

		include_once dirname(__FILE__) . '/Controller.php';
		$controller = new Controller(self::$path);
		$controller->mainRequest = true;

		$response = $controller->executeGlobal();

		if($response instanceof Onion)
			echo $response;
		elseif($response !== false)
			$response = $controller->execute();

		if($response instanceof Onion)
			echo $response;

		if($return) ob_get_clean();
		else ob_flush();
	}

}