<?php

class UserAgent {

	private static $instance;

	// 32768 16384 8192 4096 2048 1024 512 256  128 64 32 16 8 4 2 1
	const OTHEROS = 1;
	const WIN = 2;
	const MAC = 4;
	const LINUX = 8;
	const IPHONE = 16;

	const OTHER = 256;
	const IE = 512;
	const FIREFOX = 1024;
	const SAFARI = 2048;
	const CHROME = 4096;
	const OPERA = 8192;

	const FIREFOX_WIN = 1025;
	const FIREFOX_MAC = 1026;

	const SAFARI_WIN = 2049;
	const SAFARI_MAC = 2050;
	const SAFARI_IPHONE = 2056;

	private function __construct() {}

	public static function get() {
		return self::$instance ? self::$instance : (self::$instance = new self);
	}

	public function browser($string = false)
	{
		$browser = 0;
		if(stripos($_SERVER['HTTP_USER_AGENT'], "MSIE") !== false) $browser |= self::IE;
		elseif(stripos($_SERVER['HTTP_USER_AGENT'], "Firefox") !== false) $browser |= self::FIREFOX;
		elseif(stripos($_SERVER['HTTP_USER_AGENT'], "Safari")) $browser |= self::SAFARI;
		elseif(stripos($_SERVER['HTTP_USER_AGENT'], "Chrome")) $browser |= self::CHROME;
		elseif(stripos($_SERVER['HTTP_USER_AGENT'], "Opera")) $browser |= self::OPERA;
		else $agent |= self::OTHER;

		if(strpos($_SERVER['HTTP_USER_AGENT'], "Windows") !== false) $browser |= self::WIN;
		elseif(strpos($_SERVER['HTTP_USER_AGENT'], "Macintosh") !== false) $browser |= self::MAC;
		elseif(strpos($_SERVER['HTTP_USER_AGENT'], "iPhone") !== false) $browser |= self::IPHONE;

		if($string) {
			if($browser & self::IE) $string = "MSIE";
			elseif($browser & self::FIREFOX) $string = "Firefox";
			elseif($browser & self::SAFARI) $string = "Safari";
			elseif($browser & self::CHROME) $string = "Chrome";
			elseif($browser & self::OPERA) $string = "Opera";
			else $string = "Other";

			if($browser & self::WIN) $string .= ", Windows";
			if($browser & self::MAC) $string .= ", OS X";
			if($browser & self::IPHONE) $string .= ", iPhone";
			if($browser & self::OTHER) $string .= ", Other OS";
			$browser = $string;
		}
		return $browser;
	}

	public function ip() {
		return $_SERVER['REMOTE_ADDR'];
	}

	public function port() {
		return $_SERVER['REMOTE_PORT'];
	}

	public static function isAjax() {
		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') : false;
	}

}