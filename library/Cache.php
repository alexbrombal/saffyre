<?php

/**
 * Caches arbitrary data in the filesystem.
 * 
 * @example
 * ...
 * Cache::$dir = '/path/to/cache/';
 * ...
 * 
 * $data = Cache::get('my-cache-data');
 * if(!$data)
 * {
 *     $data = some_complex_function();
 *     Cache::save('my-cache-data', $data, 3600);
 * }
 * ...
 * 
 * @copyright 2009 Alex Brombal
 */
class Cache
{
	public static $dir;

	public static function save($label, $data, $expires = null)
	{
		$label = urlencode($label);
		$dir = rtrim(self::$dir, "/");
		if (is_object($data))
		{
			if (method_exists($data, "__toString")) $data = call_user_func(array($data, '__toString'));
			else $data = serialize($data);
		}
		if ((bool)file_put_contents("$dir/$label", (string)gzcompress($data)))
		{
			if ($expires) $expires = time() + (int)$expires;
			else $expires = time() + (60 * 60 * 24 * 365 * 10);
			touch("$dir/$label", time(), $expires);
			return $data;
		}
		else throw new Exception("Could not cache file.", 0);
	}

	public static function get($label)
	{
		$label = urlencode($label);
		$dir = rtrim(self::$dir, "/");
		if (file_exists("$dir/$label"))
		{
			$file = gzuncompress(file_get_contents("$dir/$label"));
			if (fileatime("$dir/$label") > time()) return $file;
		}
	}

	public static function output($label, $ContentType = null)
	{
		if ($content = self::get($label))
		{
			if ($ContentType) header("Content-type: $ContentType");
			print $content;
			return true;
		}
	}

	public static function clear($label)
	{
		$label = urlencode($label);
		$dir = rtrim(self::$dir, "/");
		if (file_exists("$dir/$label"))
		{
			unlink("$dir/$label");
		}
	}

}