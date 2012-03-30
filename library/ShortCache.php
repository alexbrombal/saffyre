<?

class ShortCache {

	private static $cache = array();

	public static function set($key, $value) {
		return self::$cache[$key] = $value;
	}

	public static function get($key) {
		return isset(self::$cache[$key]) ? self::$cache[$key] : null;
	}

	public static function clear($key) {
		unset(self::$cache[$key]);
	}

	public static function flush() {
		self::$cache = array();
	}

}