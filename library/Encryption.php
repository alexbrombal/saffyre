<?php

class Encryption {
		
	public static $salt;
	
	public static function encrypt($text)
	{
		if(!$text) return;
		srand(microtime(true) * 1000000);
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_CFB, '');
		$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		$ks = mcrypt_enc_get_key_size($td);
		$key = self::getKey($ks);
	
		mcrypt_generic_init($td, $key, $iv);
		$ciphertext = mcrypt_generic($td, $text);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		
		return $ciphertext . $iv;
	}
	
	public static function decrypt($cipher)
	{
		if(!$cipher) return;
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_CFB, '');
		$ks = mcrypt_enc_get_key_size($td);
		$key = self::getKey($ks);
		
		mcrypt_generic_init($td, $key, substr($cipher, -32));
		$plaintext = mdecrypt_generic($td, substr($cipher, 0, strlen($cipher)-32));
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		
		return $plaintext;
	}
	
	private static function getKey($length) {
		return substr(sha1(self::$salt), 0, $length);
	}
	
}