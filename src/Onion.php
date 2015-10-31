<?php

namespace Saffyre;

class Onion {

	public static $dir;

	private static $__files;

	private $__file;
	private $__section;
	private $__vars = array();
	private $__parent;

	public function __construct($file, $vars = null)
	{
		if(!self::$dir) throw new Exception('Onion directory has not been set!');
		if(!is_dir(self::$dir)) throw new Exception('Onion directory ('.self::$dir.') does not exist!');

		self::$dir = rtrim(self::$dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;		// Be sure there is a / at the end of directory

		$template = explode('#', $file, 2);

		// If filename begins with /, use only the filename, otherwise prepend the directory
		$this->__file = (substr($template[0], 0, 1) == DIRECTORY_SEPARATOR ? '' : self::$dir) . $template[0];
		$this->__section = isset($template[1]) ? $template[1] : '';

		if(!is_file($this->__file)) throw new Exception('Onion file ('.$this->__file.') does not exist!');

		if(is_array($vars))
			foreach($vars as $key => $value) $this->$key = $value;
	}

	public static function parse($file, $vars = null, $context = null)
	{
		$o = new Onion($file, $vars);
		if($context) $context->{"$file"} = $o;
		return $o->__toString();
	}

	public function file() {
		return $this->__file;
	}

	public function section() {
		return $this->__section;
	}

	public function __get($name) {
		if(isset($this->__vars[$name])) return $this->__vars[$name];
		elseif($this->__parent) return $this->__parent->$name;
		else return null;
	}

	public function __set($name, $value) {
		if($value instanceof self) $value->__parent = $this;
		$this->__vars[$name] = $value;
	}

	public function __isset($name)
	{
		return isset($this->__vars[$name]);
	}

	public function __toString()
	{
		$file = $this->__file;
		if(!is_file($file)) return '';									// If file doesn't exist, quit

		$section = $this->__section;

		if(!isset(self::$__files[$file]))
		{
			$contents = file_get_contents($file);
			$fileparts = preg_split('/\n?({{{)\s*(.*?)\s*}}}\s*\n?/', $contents, null, PREG_SPLIT_OFFSET_CAPTURE | PREG_SPLIT_DELIM_CAPTURE);
																				// Split data into sections
			if(trim($fileparts[0][0]) == '') unset($fileparts[0]);						// Get rid of first empty value if necessary

			while(list($k, $part) = each($fileparts)) {								// Stores all sections in name => value pairs for use later
				if($part[0] != '{{{') {
					self::$__files[$file][''] = array($part[0], 0);
				} else {
					$s = current($fileparts);
					$f = next($fileparts);
					self::$__files[$file][$s[0]] = array($f[0], count(explode(PHP_EOL, substr($contents, 0, $part[1]))));
					next($fileparts);
				}
			}
		}

		if(isset(self::$__files[$file][$section]))
		{
			set_error_handler(array($this, 'error'), ini_get('error_reporting'));
			ob_get_level();
			ob_start(array($this, 'fatal'));
			try {
				eval('?>'.self::$__files[$file][$section][0]);
			} catch(Exception $e) {
				Onion::exceptionHandler($e);
			}
			restore_error_handler();
			return ob_get_clean();
		}
		else
			return '';
	}

	public function fatal($text)
	{
		$strpos = strrpos($text, '<br />'.PHP_EOL.'<b>Fatal error');
		if($strpos) {
			$text = substr($text, 0, strrpos($text, '<br />'.PHP_EOL.'<b>Fatal error'));
			$err = error_get_last();
			return $text . self::error($err['type'], $err['message'], $err['file'], $err['line'], array(), true);
		}
		return $text;
	}

	public function error($errno, $errstr, $errfile, $errline, $errcontext, $return = false)
	{
		$errs = array(
			E_ERROR => 'Error',
			E_COMPILE_ERROR => 'Error',
			E_WARNING => 'Warning',
			E_NOTICE => 'Notice',
			E_STRICT => 'Strict Warning',
			E_RECOVERABLE_ERROR => 'Error'
		);
		$file = $this->__file;
		$section = $this->__section;
		if(strpos($errfile, 'Onion.php(') !== false)
			$str = "<br/><br/><b>Onion {$errs[$errno]}</b>: $errstr in <b>$file" . ($section ? "#$section" : '') . '</b> on line <b>' . ($errline + self::$__files[$file][$section][1] + 1) . "</b>\n";
		else
			$str = "<br/><br/><b>{$errs[$errno]}</b>: $errstr in <b>$errfile</b> on line <b>$errline</b>\n";
		if($return) return $str;
		else echo $str;
	}

	public static function exceptionHandler(Exception $e) {
		echo "<br/><br/><b>{$e->getMessage()}</b><br/>".nl2br($e->getTraceAsString());
	}

}
