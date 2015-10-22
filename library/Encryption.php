<?php

require_once __DIR__ . '/defuse-encryption/autoload.php';

use \Defuse\Crypto\Crypto;
use \Defuse\Crypto\Exception as Ex;

/**
 * Usage:
 *
 * 1. Call Encryption::createKey() manually and write the result to Encryption::$key = '' (as a constant value, DO NOT
 *    generate it dynamically on each page load).
 * 2. Call Encryption::encrypt() and Encryption::decrypt() freely!
 */
class Encryption {
		
	public static $key;

    public static function createKey()
    {
        // WARNING: Do NOT encode $key with bin2hex() or base64_encode(),
        // they may leak the key to the attacker through side channels.
        return Crypto::createNewRandomKey();
    }
	
	public static function encrypt($message, $urlSafe = false)
	{
        if (!self::$key)
            throw new Exception('Generate and set Encryption::$key before calling Encryption::encrypt()!');
        $cipher = Crypto::encrypt($message, self::$key);
        if ($urlSafe)
            $cipher = str_replace(array('/', '+'), array('_', '-'), base64_encode($cipher));
        return $cipher;
    }
	
	public static function decrypt($ciphertext, $urlSafe = false)
	{
        if (!self::$key)
            throw new Exception('Generate and set Encryption::$key before calling Encryption::encrypt()!');
        if ($urlSafe)
            $ciphertext = base64_decode(str_replace(array('_', '-'), array('/', '+'), $ciphertext));
        try {
            return Crypto::decrypt($ciphertext, self::$key);
        } catch(Exception $e) {
            return "";
        }
	}
}