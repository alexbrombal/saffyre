<?php

namespace Saffyre;

class Util {

	/**
	 * $true? --no--> $false? --no--> $neither? --null--> $false
	 *   |               |                |
	 *  yes             yes              set
	 *   |               |                |
	 * $true          $false          $neither
	 */
	public static function get($true, $false, $neither = null) {
		if($true) return $true;
		elseif($false && $neither === null) return $false;
		else return $neither;
	}

	/**
	 * Returns the first argument that evaluates to true.
	 * There can be any number of arguments.
	 */
	public static function first($arg1, $arg2) {
		$args = func_get_args();
		foreach($args as $arg)
			if($arg) return $arg;
		return $arg;
	}

	public static function rand($arg1, $arg2) {
		$args = func_get_args();
		return $args[array_rand($args)];
	}

	/**
	 * Returns the value of static variable $class::$property
	 */
	public static function get_class_var($class, $property) {
		if(!class_exists($class, true)) return;
		if(class_exists('ReflectionProperty'))
		{
			$x = new ReflectionProperty($class, $property);
			return $x->getValue();
		}
		else
			return eval("return $class::$$property;");
	}

	public static function binaryUpdate($old, $new, $mask) {
		return ($new & $mask) | ($old & ~$mask);
	}

	/**
	 * Return $value if it is one of the items in $ifNot, otherwise return $else
	 */
	public static function useIf($value, $if, $else = null, $strict = false) {
		$if = Util::toArray($if);
		foreach($if as $i) {
			if($strict && $value === $i) return $value;
			elseif(!$strict && $value == $i) return $value;
		}
		return $else;
	}

	/**
	 * If $value is one of the items in $ifNot, return $else
	 */
	public static function useIfNot($value, $ifNot, $else = null, $strict = false) {
		$ifNot = Util::toArray($ifNot);
		foreach($ifNot as $i) {
			if($strict && $value === $i) return $else;
			elseif(!$strict && $value == $i) return $else;
		}
		return $value;
	}



	public static function pick($obj, $properties)
	{
		if(!($obj instanceof stdClass || is_array($obj) || $obj instanceof BaseClass)) throw new Exception('Invalid object type!');
		if(!is_array($properties))
		{
			$properties = func_get_args();
			array_shift($properties);
		}
		if(is_array($obj))
		{
			$new = array();
			foreach($properties as $key)
				$new[$key] = $obj[$key];
		}
		else
		{
			$type = get_class($obj);
			$new = new $type;
			foreach($properties as $key)
				if(isset($obj->$key)) $new->$key = $obj->$key;
		}
		return $new;
	}

	public static function omit($obj, $properties)
	{
		if(!$obj instanceof stdClass && !is_array($obj)) throw new Exception('Invalid object type!');
		if(!is_array($properties))
		{
			$properties = func_get_args();
			array_shift($properties);
		}
		if(is_array($obj))
		{
			$new = array();
			foreach($obj as $key => $value)
				if(!in_array($key, $properties))
					$new[$key] = $value;
		}
		else
		{
			$class = get_class($obj);
			$new = new $class;
			foreach($obj as $key => $value)
				if(!in_array($key, $properties))
					$new->$key = $value;
		}
		return $new;
	}

	public static function extend(&$obj1, $obj2)
	{
		if(!((is_array($obj1) || is_object($obj1)) && (is_array($obj2) || is_object($obj2)))) return $obj1;
		foreach($obj2 as $key => $value)
			$obj1->$key = $obj2->$key;
		return $obj1;
	}

	public static function getClassProperties($class)
	{
		return array_values(
			array_map(
				function($p) { return $p->name; },
				array_filter(
					(new ReflectionClass($class))->getProperties(ReflectionProperty::IS_PUBLIC),
					function($p) use ($class) {
						return $p->class == $class && !$p->isStatic();
					}
				)
			)
		);
	}

}
