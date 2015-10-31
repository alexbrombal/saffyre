<?php

namespace Saffyre;

/**
 * A collection of string utility functions.
 * 
 * @copyright 2009 Alex Brombal
 */
class String 
{
	// This is a namespace class only; you can't construct it.
	private function __construct() {}

	public static function urlEncode($string, $clone) {
		if(is_string($string)) return urlencode($string);
		if(is_object($string) || is_array($string)) {
			foreach($string as &$value) {
				$value = String::urlEncode($value);
			}
		}
		return $string;
	}

	public static function htmlEntities($string, $quote_style = null) 
	{
		if(is_string($string)) return htmlentities($string, $quote_style ? $quote_style : ENT_COMPAT, 'UTF-8');
		if(is_object($string) || is_array($string)) {
			foreach($string as &$value) {
				$value = String::htmlEntities($value, $quote_style);
			}
		}
		return $string;
	}

	public static function trim($string, $mask = null) {
		if(is_string($string)) $string = ($mask !== null ? trim($string, $mask) : trim($string));
		if(is_object($string) || is_array($string)) {
			foreach($string as &$value) {
				if($mask !== null) $value = ($mask !== null ? trim($value, $mask) : trim($value));
			}
		}
		return $string;
	}

	public static function jsObfuscate($string, $bytesOnly = false)
	{
		$string = mb_convert_encoding($string, "UCS-2", mb_detect_encoding($string, array('UTF-8')));
		
		$str = array();
		
		for($i = 0; $i < mb_strlen($string, "UCS-2"); $i++)
		{
			$s2 = mb_substr($string, $i, 1, "UCS-2");
			$val = unpack("n", $s2);
			$val = $val[1];

			if ($val > 0xFFFF) {
				$val -= 0x10000;
				$str[] = 0xD800 + ($val >> 10);
				$str[] = 0xDC00 + ($val & 0x3FF);
			}
			else $str[] = $val;
		}

        $bytes = implode(',', $str);
		return $bytesOnly ? $bytes : "<script type='text/javascript'>document.write(String.fromCharCode($bytes));</script>";
	}

	public static function trimLines($string) {
		return preg_replace('/\n[ \t]{2,}/', "\n", preg_replace('/^[ \t]{2,}/', "", $string));
	}

	public static function nl2br($string) {
		if(is_string($string)) $string = nl2br($string);
		if(is_object($string) || is_array($string)) {
			foreach($string as &$value) {
				$value = nl2br($value);
			}
		}
		return $string;
	}

	const REGEX_EMAIL_ANY = '/(?:[a-z0-9!#$%&*+-?^_`{|}~]+|"[^"]+")@(?:[-a-z0-9]+\.)+[a-z]{2,}/i';
	const REGEX_EMAIL_ALL = '/^(?:[a-z0-9!#$%&*+-?^_`{|}~]+|"[^"]+")@(?:[-a-z0-9]+\.)+[a-z]{2,}$/i';
				
	/**
	 * Returns true if $email is a valid email address.
	 */
	public static function isEmail($email) {
		return preg_match(self::REGEX_EMAIL_ALL, $email);
	}
	
	public static function isUSPhone($phone)
	{
		return strlen(self::formatUSPhone($phone, false, true)) >= 10;
	}

	const REGEX_URL = '/^((http|https):\/\/)?[a-z0-9]+([\-\.][a-z0-9]+)*\.[a-z]{2,}(\/.*)?$/i';
	/**
	 * Returns true if $url is a valid url
	 */
	public static function isURL($url) {
		return preg_match(self::REGEX_URL, $url);
	}

	/**
	 * Returns true if $ip is a valid ip address.
	 */
	public static function isIPAddress($ip) {
		return preg_match('/\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b/', $ip);
	}

	/**
	 * Returns true if $sha1 is a valid sha1 value (40 char hexadecimal)
	 */
	public static function isSHA1($sha1) {
		return preg_match("/[a-fA-F0-9]{40}/", $sha1);
	}

	/**
	 * Formats a phone number to (xxx) xxx-xxxx if $format = true, otherwise xxxxxxxxxx
	 * If $phone is not a valid phone number, returns stripped version.
	 */
	public static function formatUSPhone($phone, $format = true, $forceLength = true) {
		$stripped = preg_replace('/\D/', '', $phone);

		if(!$format)
			return $forceLength && strlen($stripped) < 10 ? '' : $stripped;

		$formatted = preg_replace('/(\d{3})(\d{3})(\d{4})/', "($1) $2-$3", substr($stripped, 0, 10));
		if (strlen($stripped) > 10)
			$formatted .= ' ext. ' . substr($stripped, 10);
		return ($formatted ? $formatted : $stripped);
	}

	public static function formatMoney($money) {
		return sprintf('$%0.2f', $money);
	}

	public static function formatHours($hours) {
		$minutes = round(($hours - floor($hours)) * 60);
		$hours = floor($hours);
		return "$hours:" . sprintf('%02d', $minutes);
	}

	public static function cleanFloat($float) {
		return preg_replace('/[^\.\d]/', '', $float);
	}

	/**
	 * Removes all characters except a-z, A-Z, 0-9, hyphens and underscores from $slug,
	 * lowercases all letters and replaces spaces with hyphens.
	 */
	public static function urlNormalize($str) {
		$str = str_replace(" ", "-", $str);
		$str = str_replace("&", "and", $str);
		$str = preg_replace("/[^a-zA-Z0-9_-]/", "", $str);
		$str = preg_replace("/-{2,}/", "-", $str);
		$str = trim($str, "-");
		$str = strtolower($str);
		return $str;
	}

	/**
	 * Returns a "key" of alpha-numeric characters with $length number of characters.
	 * Zero and one are always used instead of letters O and I.
	 */
	public static function alphanumKey($length, $chars = null, $upper = null) {
		if($chars === null) $chars = "ACDEFGHJKLMNPQRTUVWXYZ234679";
		if(!is_array($chars)) $chars = str_split($chars);
		if(!$chars) return '';
		$key = '';
		while(strlen($key) < $length)
			$key .= $chars[array_rand($chars)];
		if($upper === true) $key = strtoupper($key);
		if($upper === false) $key = strtolower($key);
		return $key;
	}

	public static function lipsum($length)
	{
		$words = explode(' ', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed ac ligula nec justo porta auctor vitae eu magna. Quisque in nulla vitae mi pulvinar congue quis ac leo. Sed fermentum, purus quis venenatis consectetur, enim felis cursus magna, non feugiat mauris dui non est. In in sem ipsum. Etiam suscipit blandit velit eu consequat. Integer semper, dolor nec scelerisque lacinia, diam metus dapibus ante, ac posuere nisl quam id mauris. Vestibulum eu interdum felis. Sed dolor risus, tincidunt id consectetur at, congue sed elit. Aenean rhoncus sollicitudin est, id viverra felis posuere et. Donec id ligula imperdiet erat lobortis scelerisque. Sed turpis nunc, iaculis in ullamcorper quis, dictum quis sem.');
		shuffle($words);
		$lipsum = strtolower(implode(' ', $words));
		$sentences = explode('.', $lipsum);
		foreach($sentences as &$sentence) ucfirst($sentence);
		$trim = trim(substr(preg_replace('/ {2,}/', '', implode(' ', $sentences)), 0, $length), ' ,');
		return ucfirst($trim).'.';
	}
	
	public static function upTo($needle, $haystack) {
		if(strpos($haystack, $needle) === false) return $haystack;
		return substr($haystack, 0, strpos($haystack, $needle));
	}

	public static function jsEscape($string, $quote = "'") {
		if(is_string($string)) return str_replace(array($quote, "\n"), array("\\$quote", '\n'), $string);
		if(is_object($string) || is_array($string)) {
			foreach($string as &$value) {
				$value = self::jsEscape($value);
			}
		}
		return $string;
	}

	/**
	 * Returns the line number of the character at $offset
	 */
	public static function getLine($str, $offset) {
		return count(explode(PHP_EOL, substr($str, 0, $offset)));
	}

	public static function truncate($string, $length, $mask) {
		if($length >= strlen($string)) return $string;
		return substr($string, 0, $length - strlen($mask)) . $mask;
	}

	public static function truncateMiddle($string, $length, $mask) {
		if($length >= strlen($string)) return $string;
		$chars = $length - strlen($mask);
		return substr($string, 0, ceil($chars/2)) . $mask . substr($string, -floor($chars/2)); 
	}

	public static function compressHTML($html)
	{
		return preg_replace('/>\s+</', '><', $html);
	}

}