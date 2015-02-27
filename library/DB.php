<?php

class DB {

	public static $conns = array();
	public static $debug;

	const ERR_NOCONN = 'No default database connection! Try setting DB::$default = \'...\'';

	public static $default;

	private function __construct() { }

	/**
	 * @return DBConn
	 */
	public static function connect($string = null)
	{
		return DBConn::create($string);
	}

	/**
	 * @return DBConn
	 */
	public static function getDefault()
	{
		return empty(self::$conns['default']) ? null : self::$conns['default'];
	}

	/**
	 * @return DBResult
	 */
	public static function execute($sql, $args = null)
	{
		if(empty(self::$conns['default'])) DB::connect();
		if(empty(self::$conns['default'])) throw new Exception(self::ERR_NOCONN);
		$args = func_get_args();
		return call_user_func_array(array(self::$conns['default'], 'execute'), $args);
	}

	/**
	 * @return DBResult
	 */
	public static function executeF($sql, $args = null)
	{
		if(empty(self::$conns['default'])) DB::connect();
		if(empty(self::$conns['default'])) throw new Exception(self::ERR_NOCONN);
		$args = func_get_args();
		return call_user_func_array(array(self::$conns['default'], 'executeF'), $args);
	}

	public static function describe($table)
	{
		if(empty(self::$conns['default'])) DB::connect();
		if(empty(self::$conns['default'])) throw new Exception(self::ERR_NOCONN);
		return self::$conns['default']->describe($table);
	}

	public static function prepare($sql, $args = null)
	{
		if(empty(self::$conns['default'])) DB::connect();
		if(empty(self::$conns['default'])) throw new Exception(self::ERR_NOCONN);
		$args = func_get_args();
		return call_user_func_array(array(self::$conns['default'], 'prepare'), $args);
	}

	public static function setDebug($on = true) {
		self::$debug = (boolean)$on;
	}

}

class DBConn {

	private $user;
	private $password;
	private $host;
	private $database;
	
	private $conn;
	private $connString;
	private $tables = array();

	protected function __construct($string)
	{
		$this->connString = $string;

		$this->user = substr($string, 0, strpos($string, ':'));
		$atPos = strrpos($string, '@');
		$slashPos = strrpos($string, '/');
		$this->password = substr($string, $pwStart = strlen($this->user)+1, $atPos - $pwStart);
		$this->host = substr($string, $atPos + 1, $slashPos - $atPos - 1);
		$this->database = substr($string, $slashPos + 1);

		$this->conn = @mysql_connect($this->host, $this->user, $this->password);
		if(!$this->conn) throw new Exception('Could not connect to database: ' . mysql_error() . " ($string)");

		mysql_query("SET NAMES 'utf8'", $this->conn);
		mysql_query("SET CHARACTER SET 'utf8'", $this->conn);

		if(!@mysql_select_db($this->database)) throw new Exception('Error while selecting database: ' . mysql_error());
	}

	public static function create($string = null)
	{
		if(!$string) $string = DB::$default;
		if(isset(DB::$conns[$string])) return DB::$conns[$string];
		$db = new DBConn($string);
		if(empty(DB::$conns['default'])) DB::$conns['default'] = $db;
		return DB::$conns[$string] = $db;
	}

	public function getUser() { return $this->user; }
	public function getPassword() { return $this->password; }
	public function getHost() { return $this->host; }
	public function getDatabase() { return $this->database; }
	
	/*
	 * @return DBResult
	 */
	public function executeF($sql)
	{
		$args = func_get_args();
		if(count($args) > 1) {
			if(!is_array($args[1]))
				array_shift($args);
			else
				$args = $args[1];
		    if(count($args)) $sql = DB::prepare($sql, $args);
		}

		$table = $this->query($sql);



		switch(true)
		{
			case stripos($sql, "SELECT ") === 0:
			case stripos($sql, "DESCRIBE ") === 0:

				return new DBResult($table, mysql_info($this->conn));

			break;

			case stripos($sql, "UPDATE ") === 0:
			case stripos($sql, "DELETE ") === 0:
			case stripos($sql, "TRUNCATE ") === 0:

				return (int)mysql_affected_rows($this->conn);

			break;

			case stripos($sql, "INSERT ") === 0:

				if($id = mysql_insert_id($this->conn)) return $id;
				else return true;

			break;

		}

		return null;
	}

	public function execute($sql)
	{
		$args = func_get_args();
		$res = call_user_func_array(array($this, 'executeF'), $args);

		if(!$res instanceof DBResult) return $res;

		$numrows = $res->numRows();
		if($numrows == 1 && $res->numFields() == 1)
		{
			$field = mysql_fetch_array($res->resource, MYSQL_NUM);
			return $field[0];
		}
		elseif($numrows == 0)
			return null;

		return $res;
	}

	/**
	 * Executes $sql query and returns the result resource
	 */
	private function query($sql)
	{
		if(DB::$debug) print "<b>QUERY: $sql</b><br />\n";
		$result = @mysql_query($sql);
		if((DB::$debug || ini_get('display_errors')) && $error = mysql_error($this->conn))
			throw new Exception('Database query error: "'.$sql.'" ' . $error);
		return $result;
	}


	/**
	 * Returns a stdClass containing info about the $table
	 */
	public function describe($table)
	{
		if(isset($this->tables[$table])) return $this->tables[$table];

		if($result = $this->query(DB::prepare("DESCRIBE `%s`", $table)))
		{
			$this->tables[$table] = new stdClass();
			while($row = mysql_fetch_object($result)) {
				$this->tables[$table]->{$row->Field} = $row;
				preg_match_all('/([^\(]+)\((\d+)\)/', $row->Type, $matches);
				if(isset($matches[1][0]) && isset($matches[2][0])) {
					$this->tables[$table]->{$row->Field}->Type = $matches[1][0];
					$this->tables[$table]->{$row->Field}->Length = $matches[2][0];
				}
			}
			return $this->tables[$table];
		} else
			return $this->tables[$table] = false;
	}

	public function prepare($sql, $args = null)
	{
		if(!is_array($args)) {
			$args = func_get_args();
			array_shift($args);
		}

		foreach($args as &$arg) $arg = mysql_real_escape_string($arg, $this->conn);
		array_unshift($args, $sql);

		$result = call_user_func_array("sprintf", $args);
		if($result) return $result;
		else echo $sql;
	}

	public function connString()
	{
		return $this->connString;
	}

	public function disconnect()
	{
		mysql_close($this->conn);
	}

	public function __destruct()
	{
		$this->disconnect();
	}

}

class DBResult extends DBConn implements Iterator {

	protected $resource;
	private $table = array();
	private $rows = array();
	private $finished = false;

	protected function __construct($resource, $info)
	{
		$this->resource = $resource;
	}

	public function __call($field, $args)
	{
		$i = 0;
		$this->fetch();
		if(!$current = current($this->rows)) return new stdClass();
		$field = array_search($field, array_keys((array)$current));
		if($field !== false)
			return mysql_fetch_field($this->resource, $field);
		return new stdClass();
	}

	public function __get($field)
	{
		$this->fetch();
		$current = current($this->rows);
		if(property_exists($current, $field))
			return $current->$field;
		else
			throw new Exception("Invalid field '$field' called on database result");
	}

	public function __isset($field)
	{
		$this->fetch();
		$current = current($this->rows);
		return isset($current->$field);
	}
	
	public function current() {
		return $this;
	}

	public function next() {
		next($this->rows);
		$this->fetch();
	}

	public function key() {
		return key($this->rows);
	}

	public function valid() {
		return (bool)current($this->rows);
	}

	public function numRows() {
		return $this->finished ? count($this->rows) : mysql_num_rows($this->resource);
	}

	public function numFields() {
		return $this->rows ? count(array_keys($this->rows)) : mysql_num_fields($this->resource);
	}

	public function rewind() {
		reset($this->rows);
		if(!$this->rows) $this->fetch();
	}

	private function fetch() {
		if($this->finished || current($this->rows)) return;
		if($row = mysql_fetch_row($this->resource))
		{
			$this->rows[] = $obj = new stdClass;
			if(!$this->table)
			{
				foreach($row as $key => $value)
					$this->table[$key] = mysql_fetch_field($this->resource, $key);
			}
			foreach($row as $key => $value)
			{
				$obj->{$this->table[$key]->name} = $value;
				$obj->{"{$this->table[$key]->table}.{$this->table[$key]->name}"} = $value;
			}
		}
		else
			$this->finished = true;
	}


	public function getObject() {
		return (object)current($this->rows);
	}


	public function __destruct() {
		if(is_resource($this->resource)) mysql_free_result($this->resource);
	}

}