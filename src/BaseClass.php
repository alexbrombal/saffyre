<?php

namespace Saffyre;

/**
 * An extended Standard Class
 *
 * Last Updated 10/04/2007
 * @author Alex Brombal
 * @copyright Alex Brombal 2007
 * @version 1.0.2
 */

class BaseClass extends \stdClass {

	public function __get($name) {
		return null;
	}

	public static function create($obj) {
		return new static($obj);
	}

	public function __construct($values = null, $onlyProperties = false) {
		$this->__import($values, $onlyProperties);
	}

	public function __call($name, $arguments) {}

    public static function __callStatic($name, $arguments) {}

	public function __isEmpty($except = "", $debug = false) {
		$except = explode(",", str_replace(" ", "", $except));
		$properties = 0;
		foreach($this as $key => $value)
			if(array_search($key, $except) === false && $value)
				$properties++;
			elseif($debug)
				print "Not empty: $key = $value";
		if(!$properties) return true;
	}

	public function __clear($except = array()) {
		$except = (array)$except;
		foreach($this as $key => $value)
			if(array_search($key, $except) === false) unset($this->$key);
	}

	public function __import($obj, $onlyProperties = false) {
		if (is_string($obj))
			$obj = json_decode($obj);
		if ($obj)
			foreach($obj as $key => $value)
				if (!$onlyProperties || property_exists($this, $key))
					$this->$key = (is_object($value) && method_exists($value, '__clone') ? clone $value : $value);
	}

	public function __compare(BaseClass $compare, $except = array(), $debug = false) {
		$except = (array)$except;
		foreach($compare as $key => $value)
			if(	!in_array($key, $except) &&
				(
					(is_array($this->$key) != is_array($value)) ||
				 	(!is_array($value) && $this->$key != $value))
				)
			{
				if($debug) print "Compare: $key => $value :: {$this->$key}<br/>\n";
				return false;
			}
		return true;
	}

	public function __empty($except = array()) {
		if(!is_array($except)) $except = explode(",", str_replace(" ", "", $except));
		foreach($this as $key => $value) {
			if(!in_array($key, $except)) $this->$key = null;
		}
	}

}