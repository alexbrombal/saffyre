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
	
	public static function encrypt($message)
	{
        if (!self::$key)
            throw new Exception('Generate and set Encryption::$key before calling Encryption::encrypt()!');
        return Crypto::encrypt($message, self::$key);
    }
	
	public static function decrypt($ciphertext)
	{
        if (!self::$key)
            throw new Exception('Generate and set Encryption::$key before calling Encryption::encrypt()!');
        return Crypto::decrypt($ciphertext, self::$key);
	}
}