<?php

namespace Saffyre;

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
	 * Cleans $path (an array of path components or a string path) by removing empty values and url-decoding the values.
	 *
	 * @param string|array $path The array of path components or string path to clean.
	 * @param boolean $string True to return a string path, false to return an array of components
	 * @return string|array The cleaned path, as a string or array
	 * @uses Saffyre\Arrays
	 */
	public static function cleanPath($path, $string = false)
	{
		if(!$path) return ($string ? '' : array());
		if(!is_array($path)) $path = explode('/', $path);
		$path = Arrays::array_clean($path);
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
	public static function responseStatus($code)
	{
		switch($code)
		{
			case 400:
				header("HTTP/1.1 400 Bad Request");
				break;
			case 404:
				header("HTTP/1.1 404 Not Found");
				break;
			case 403:
				header("HTTP/1.1 403 Forbidden");
				break;
			case 401:
				header("HTTP/1.1 401 Unauthorized");
				break;
			default:
				header("HTTP/1.1 " . $code);
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
        if (!Saffyre::isSSL())
        {
            $url = URL_BASE . ltrim(Saffyre::request(), '/');
            $ssl = preg_replace('/^http:/', 'https:', $url);
            Saffyre::redirect($ssl);
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

		if (!$path)
			$path = strtok($_SERVER['REQUEST_URI'], '?');

		self::$path = self::cleanPath($path);

		$required = array("URL_BASE", "URL_PATH");
		$undefined = array();
		foreach($required as $const)
			if(!defined($const)) $undefined[] = $const;

		if($undefined)
			throw new Exception('The following constants are not defined: ' . join(', ', $undefined));

		if(!defined('ENCODING'))
			define('ENCODING', 'UTF-8');

		header('Content-type: text/html; charset='.ENCODING);

		//include_once dirname(__FILE__) . '/Controller.php';
		$controller = new Controller(true, self::$path);
		$controller->isMainRequest = true;
		$response = $controller->execute(true);

		if (!$controller->resultWasOutput)
		{
			if (array_filter(headers_list(), function($c) { return strpos(strtolower($c), 'content-type: application/json') === 0; }))
			{
				if ($response instanceof JsonSeriazable)
					echo json_encode($response);
                else if (method_exists($response, '__toJson'))
					echo $response->__toJson();
                else
					echo json_encode($response);
			}
			else
				echo $response;
		}

		if ($controller->status != null)
			Saffyre::responseStatus($controller->status);

		if($return) return ob_get_clean();
		else ob_flush();
	}
}
