<?php

namespace Saffyre;

/**
 * A utility wrapper class for accessing $_GET, $_POST, and $_COOKIE data.
 *
 * // Get individual values:
 * $getName = Q::get('name');
 * $postString = Q::post('string');
 * $cookieSession = Q::cookie('session');
 *
 * // As an object of name value pairs:
 * $g = Q::get();
 * echo "Your name is {$g->name}";
 * String::htmlEntities($g); // Now all the values are safe for printing in html!
 *
 * // Set a cookie:
 * Q::cookie('name', 'value', 3600, '/', 'website.com', false);
 *
 * @author Alex
 *
 */
class Q
{
	public static $cookie_domain;

	private static $_GET;
	private static $_POST;
	private static $_COOKIE;

    public $get;
    public $post;
    public $cookie;

	/**
	 * Rebuilds a query string based on this object.
	 * You may leave out keys by specifying them in the $except array.
	 *
	 * @param array $except
	 * @return string
	 */
	public function buildQueryString($except = array())
	{
		$obj = clone $this->get;
		if($except) {
			if(is_string($except)) $except = explode(",", str_replace(" ", "", $except));
			foreach($except as $key) unset($obj->$key);
		}
		return http_build_query($obj);
	}


	public function __construct()
	{
        $this->get = new BaseClass($_GET);
        $this->post = new BaseClass($_POST);
        $this->cookie = new BaseClass($_COOKIE);
        $this->headers = new BaseClass(getallheaders());
	}

	public static function get($name = null)
	{
		if($name === null) return Q::fromRequest('get');
		if(isset(self::$_GET[$name])) return self::$_GET[$name] ?: "";
		return self::$_GET[$name] = isset($_GET[$name]) ? self::clean($_GET[$name]) : null;
	}

	public static function post($name = null)
	{
		if($name === null) return Q::fromRequest('post');
		if(isset(self::$_POST[$name])) return self::$_POST[$name];
		return self::$_POST[$name] = isset($_POST[$name]) ? self::clean($_POST[$name]) : null;
	}

	public static function request($name = null)
	{
		if($name === null) return Q::fromRequest('post', 'get');
		if(isset(self::$_POST[$name])) return self::$_POST[$name];
		if(isset(self::$_GET[$name])) return self::$_GET[$name];
		self::$_POST[$name] = isset($_POST[$name]) ? self::clean($_POST[$name]) : null;
		self::$_GET[$name] = isset($_GET[$name]) ? self::clean($_GET[$name]) : null;
		return self::$_POST[$name] ? self::$_POST[$name] : self::$_GET[$name];
	}

	public static function cookie($name, $value = null, $expires = null, $path = null, $domain = null, $secure = null)
	{
		if(func_num_args() == 1)
		{
			if(isset(self::$_COOKIE[$name])) return self::$_COOKIE[$name];
			return self::$_COOKIE[$name] = isset($_COOKIE[$name]) ? self::clean($_COOKIE[$name]) : null;
		}

		if(is_array($value)) {
			$values = self::getArrayValues($name, $value);
			foreach($values as $key => $v) {
				self::setCookie($key, $v, $expires, $path, $domain, $secure);
			}
		} else {
			if($value === null) {
				if(isset($_COOKIE[$name]) && is_array($_COOKIE[$name])) {
					foreach(self::getArrayValues($name, $_COOKIE[$name]) as $cname => $cvalue)
						self::setCookie($cname, null, 0, $path, $domain, $secure);
				} else
					self::setCookie($name, null, 0, $path, $domain, $secure);
			}
			else
				self::setCookie($name, $value, $expires, $path, $domain, $secure);
		}
	}

	private static function setCookie($name, $value, $expires, $path, $domain, $secure)
	{
		$domain = ($domain ? $domain : (self::$cookie_domain !== null ? self::$cookie_domain : '.' . $_SERVER['HTTP_HOST']));
		setcookie($name, $value, $expires === null ? null : time() + $expires, $path ? $path : '/', $domain, $secure);
	}

	private static function getArrayValues($name, $array)
	{
		if(!is_array($array)) return array($name => $array);
		$return = array();
		foreach($array as $key => $value) {
			if(is_array($value) && $value)
				$return += self::getArrayValues($name."[$key]", $value);
			else
				$return[$name."[$key]"] = $value;
		}
		return $return;
	}
}