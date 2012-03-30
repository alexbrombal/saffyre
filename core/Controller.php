<?php

final class Controller {

	public static $ext;
	public static $dir;

	public $mainRequest;

	private $file = array();
	private $path = array();
	private $args = array();

	private static $stack = array();
	
	public function __construct($path = null, $args = null)
	{
		if(!self::$dir || !is_dir(self::$dir))
			throw new Exception('Controller directory is not set or does not exist! (Use Controller::$dir = "")');
		if($path === false) return;

		self::$dir = rtrim(self::$dir, DIRECTORY_SEPARATOR) . '/';

		$path = Saffyre::cleanPath($path);
		self::removeExtension($path);
		$this->path = $path;
		if(!$path) $path = array('default');

		do {
			$file = implode(DIRECTORY_SEPARATOR, $path);
			if(is_file(self::$dir . "$file.php") && $this->file = $path) break;
			if(is_file(self::$dir . $file . DIRECTORY_SEPARATOR . 'default.php') && $this->file = array_merge($path, array('default'))) break;
			array_unshift($this->args, $slug = array_pop($path));
		} while($slug);

		if(!$this->file) {
			throw new Exception('Invalid controller path. Maybe you don\'t have a default.php file.');
		}

		if($args !== null) $this->args = Saffyre::cleanPath($args);
	}

	public static function create($path = null, $args = null)
	{
		return new Controller($path, $args);
	}

	private static function removeExtension(&$args)
	{
		if(!self::$ext || !$args) return;
		end($args);
		$args[key($args)] = preg_replace('/\.('.self::$ext.')$/', '', $args[key($args)]);
	}
	
	public function isInternal()
	{
		return !$this->mainRequest;
	}

	public static function current()
	{
		return self::$stack[count(self::$stack)-1];
	}
	
	public function executeGlobal()
	{
		$file = array();
		$args = $this->path;
		while(true)
		{
			if(is_file(rtrim(self::$dir . implode(DIRECTORY_SEPARATOR, $file), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'global.php'))
			{
				$controller = new Controller(false);
				$controller->file = array_merge($file, array('global'));
				$controller->path = $this->path;
				$controller->args = $args;
				$controller->mainRequest = true;
				if($controller->execute() === false) return false;
			}
			else return;
			if(!$args) return;
			$file = array_merge($file, array(array_shift($args)));
		}
	}

	public static function process($path, $args = null)
	{
		$c = new Controller($path, $args);
		return $c->execute();
	}

	public function execute()
	{
		array_push(self::$stack, $this);
		chdir(dirname(self::$dir . implode(DIRECTORY_SEPARATOR, $this->file) . '.php'));
		return include self::$dir . implode(DIRECTORY_SEPARATOR, $this->file) . '.php';
		array_pop(self::$stack);
	}

	public function args($index = null)
	{
		if($index === null) return $this->args;
		if(is_numeric($index))
			return(isset($this->args[$index]) ? $this->args[$index] : '');
		else
			return $this->args;
	}

}