<?php

/**
 * Base class for all models. Every new model type must extend from this class.
 *
 * @author Alex Brombal
 * @copyright 7/20/09
 */
abstract class Model implements dbInsertable {

	protected $id;
	public $__error;

	const TABLE = '';
	public static $table = '';

	public static $db_default;
	private static $db_old;

	private static $loaded = array();

	public static $origValues = array();
	private static $dbInsertables = array();

	private static $nextId = -1;

	final public static function getTable($model)
	{
		if($model instanceof Model) $model = get_class($model);
		elseif(!is_subclass_of($model, "Model")) return null;
		return ($table = eval("return $model::\$table;")) ? $table : (($table = constant("$model::TABLE")) ? $table : $model);
	}

	public function getId()
	{
		if(!$this->id)
			$this->id = self::$nextId--;
		return (int)$this->id;
	}

	public function hasId()
	{
		return (int)$this->id > 0;
	}

	public static function getIdOf($model)
	{
		if($model instanceof Model) return $model->getId();
		elseif(is_int($model) || (is_string($model) && (int)(string)$model)) return (int)$model;
		return null;
	}

	public function __set($name, $value)
	{
		if(!property_exists($this, $name))
			throw new Exception("'$name' is not a valid property of ".get_class($this));

		if($value === null || $value instanceof dbInsertable) {
			$this->$name = $value;
			self::$dbInsertables[get_class($this)][$this->getId()][$name] = $value;
		} else
			throw new Exception("'$value' is not a valid value for field '$name' of class ".get_class($this));
	}

	public function __get($name)
	{
		if(property_exists($this, $name))
			return $this->$name();
	}

	public function __call($name, $args = null)
	{
		if(!property_exists($this, $name))
			throw new Exception("'$name' is not a valid property of ".get_class($this));

		if(!self::isFieldCallable($name))
			throw new Exception("'$name' is not a callable field of ".get_class($this));

		if(!isset(self::$dbInsertables[get_class($this)][$this->getId()]))
			self::$dbInsertables[get_class($this)][$this->getId()] = array();

		$dbInsertables = &self::$dbInsertables[get_class($this)][$this->getId()];

		if($this->$name instanceof dbInsertable) return $dbInsertables[$name] = $this->$name;
		if(!$this->$name) return $dbInsertables[$name] = null;

		if(isset($dbInsertables[$name])) return $dbInsertables[$name];

		$class = String::upTo('_', $name);
		return $dbInsertables[$name] = call_user_func_array(array($class, '__fromDbValue'), array($this->$name, $class));
	}

	public static function isFieldCallable($name) {
		$class = String::upTo('_', $name);
		return class_exists($class) && in_array('dbInsertable', class_implements($class, true));
	}

	public function isChanged()
	{
		$except = func_get_args();
		$origValues = $this->getOrigValues();
		foreach($this as $key => $value)
		{
			if($key == 'id') continue;
			if(in_array($key, $except)) continue;

			/* @var $value dbInsertable */
			if($value instanceof dbInsertable)
				$value = $value->__dbValue();

			elseif(is_object($value) && method_exists($value, "__toString"))
				$value = call_user_func(array($value, '__toString'));

			elseif(is_array($value) || is_object($value))
				$value = serialize($value);

			$value = (string)$value;

			if($value != $origValues->$key) return true;
		}
		return false;
	}


	public function __dbValue()
	{
		return $this->getId();
	}

	public static function __fromDbValue($value, $type = null)
	{
		return $value && $type ? Model::getById($value, $type) : null;
	}

	final function orig($name)
	{
		return isset(self::$origValues[get_class($this)][$this->getId()][$name]) ? self::$origValues[get_class($this)][$this->getId()][$name] : null;
	}

	final public function getOrigValues() {
		if(!isset(self::$origValues[get_class($this)][$this->getId()])) return new BaseClass();
		return BaseClass::create(self::$origValues[get_class($this)][$this->getId()]);
	}


	/**
	 * Callbacks used throughout Model
	 *
	 * public function verify()			// Called by client to verify object
	 * public function __save()			// Called before save() to verify object
	 * 									// If false is returned, save is cancelled
	 *
	 * public function __create()		// Called before save(), if no id, after verified
	 * public function __update()		// Called before save(), if already has id, after verified
	 * public function __created()		// Called after save(), if id assigned
	 * public function __updated()		// Called after save(), already had id
	 * public function __saved()		// Called after save(), whether it had an id or not (called after __created and __updated)
	 *
	 * public function __build($values)	// Called on getById(), before assigning values
	 * 									// Original DB values passed as object to $values
	 * 									// If another Model class is returned, it will be used instead
	 *
	 * public function __built()		// Called on getById(), after assigning values
	 *
	 * public function __destroy()		// Called on destroy(), if has id
	 */


	public function save($force = false)
	{
		$this->__error = null;
		$verify = true;
		if(method_exists($this, '__save')) $verify = call_user_func(array($this, '__save'));
		if(!$force && ($this->__errors || $verify === false)) return false;

		$class = get_class($this);

		if(!$table = Model::getTable($this))
			throw new Exception("Could not save $class, table does not exist");

		if((int)$this->id <= 0) {
			if(method_exists($this, '__create')) call_user_func(array($this, '__create'));
			$created = true;
		}
		elseif(method_exists($this, '__update')) call_user_func(array($this, '__update'));

		$db = self::getDB($class);

		$sql = array();
		$desc = $db->describe($table);

		$origValues = $this->getOrigValues();

		foreach($this as $key => $value)
		{
			if($key == 'id' || !isset($desc->$key)) continue;

			if($value instanceof dbInsertable)
				$value = $value->__dbValue();

			elseif(is_object($value) && method_exists($value, "__toString"))
				$value = call_user_func(array($this, '__toString'));

			elseif(is_array($value) || is_object($value))
				$value = serialize($value);

			if($value === null && $desc->$key->Null == 'NO') $value = '';
			if($value !== null) {
				if(in_array($desc->$key->Type, array('int', 'tinyint'))) $value = (int)$value;
				else $value = (string)$value;
			}

			if($value == $origValues->$key) continue;
			$origValues->$key = $value;

			if(is_int($value))
				$sql[] = $db->prepare("`$key` = %d", $value);
			elseif($value === null)
				$sql[] = "`$key` = NULL";
			elseif(!$value)
				$sql[] = $db->prepare("`$key` = ''");
			else
				$sql[] = $db->prepare("`$key` = '%s'", $value);
		}

		if($force || $sql)
		{
			$sql = join(', ', $sql);
			if($sql) $sql = "SET $sql";
			if($sql && $this->id > 0) {
				$db->execute("UPDATE `$table` $sql WHERE id = $this->id");
			} else {
				$this->id = $db->execute("INSERT IGNORE INTO `$table` $sql");
			}
			if(isset($created) && method_exists($this, '__created')) call_user_func(array($this, '__created'));
			elseif(method_exists($this, '__updated')) call_user_func(array($this, '__updated'));
			self::$origValues[get_class($this)][$this->getId()] = $origValues;
			if(method_exists($this, '__saved')) call_user_func(array($this, '__update'), $force || $sql);
		}

		if($this->id < 0)
			return false;

		return self::$loaded[get_class($this)][$this->id] = $this;
	}

	/**
	 * Destroys this model (removes it and all references to it)
	 */
	public function destroy()
	{
		if($this->getId() > 0) {
			if(method_exists($this, '__destroy')) call_user_func(array($this, '__destroy'));
			if($table = Model::getTable($this))
				self::getDB($this)->execute("DELETE FROM `$table` WHERE id = %d", $this->getId());
		}
		Model::unload($this);
	}

	public static function unload($models)
	{
		$models = is_array($models) ? $models : array($models);
		foreach($models as $model) {
			if(!$model instanceof Model) continue;
			unset(self::$loaded[get_class($model)][$model->getId()]);
			$model->id = null;
			foreach($model as $key => $value)
				$model->$key = null;
		}
	}

	public function getBaseModel()
	{
		$class = get_class($this);
		do {
			if(get_parent_class($class) == 'Model') return $class;
		} while($class = get_parent_class($class));
	}

	public function duplicate()
	{
		$class = get_class($this);
		if(!$table = Model::getTable($this))
			throw new Exception("Could not save $class, table does not exist");

		$id = DB::execute("INSERT INTO `$table` SELECT * FROM `$table` WHERE id = {$this->getId()}");
		if($id) return Model::getFromSQL("SELECT * FROM `$table` WHERE id = {$id}");
	}


	private static function getDB($class) {
		if(!is_subclass_of($class, 'Model')) return;
		if($class instanceof Model) $class = get_class($class);
		return DB::connect(eval("return $class::\$db_default;"));
	}



	/******** Static creation functions ********/

	/**
	 * Returns a model by its model id
	 *
	 * @return Model
	 */
	public static function getById($id, $class)
	{
		if(!$class || !is_subclass_of($class, 'Model')) throw new Exception("Model class name must be provided ('$class' was given).");
		if($id instanceof $class) return $id;
		if(!is_numeric($id)) return null;

		if(isset(self::$loaded[$class][$id]) && self::$loaded[$class][$id]->getId() > 0)
			return self::$loaded[$class][$id];

		if(!$table = Model::getTable($class)) return;

		$db = self::getDB($class);
		$sql = $db->prepare("SELECT * FROM `%s` WHERE id = %d LIMIT 1", $table, $id);
		if($results = $db->execute($sql))
			return Model::getFromSQL($results, $class, false);
	}

	public static function idExists($id, $class)
	{
		if(!$class || !is_subclass_of($class, 'Model')) throw new Exception("Model class name must be provided ('$class' was given).");
		if($id instanceof $class) return $id;
		if(!is_numeric($id)) return null;

		if(isset(self::$loaded[$class][$id]) && self::$loaded[$class][$id]->getId() > 0)
			return self::$loaded[$class][$id];

		if(!$table = Model::getTable($class)) return;

		$db = self::getDB($class);
		return (bool)$db->execute("SELECT COUNT(id) FROM `%s` WHERE id = %d", $table, $id);
	}


	/**
	 * Returns a model or array of models from a DB result or array of DB results
	 *
	 * @return Model
	 */
	final public static function getFromSQL($results, $class, $forceArray = null, $table = null)
	{
		if(!is_subclass_of($class, "Model")) return;

		$db = self::getDB($class);

		if($results instanceof SQLQuery) $results = $results->getSQL();
		if(is_string($results)) $results = $db->executeF($results);

		if($table) $table .= '.';

		if(!($results && $results->numRows())) {
			if($forceArray) return array();
			else return null;
		}

		$models = array();

		foreach($results as $row)
		{
			/* @var $row DBResult */
			if(isset($row->{$table.'id'}) && isset(self::$loaded[$class][$row->{$table.'id'}]) && self::$loaded[$class][$row->{$table.'id'}]->getId() > 0) {
				$model = self::$loaded[$class][$row->{$table.'id'}];
			}
			else
			{
				$model = unserialize('O:'.strlen($class).':"'.$class.'":0:{}');

				if(method_exists($model, '__build'))
				{
					$newClass = call_user_func(array($model, '__build'), $row->getObject());
					if($newClass instanceof Model)
						$model = $newClass;
				}

				if(isset($row->{$table.'id'})) $model->id = $row->{$table.'id'};

				foreach($model as $key => $value) {
					if(substr($key, 0, 2) == '__') continue;
					try {
						$model->$key = self::$origValues[get_class($model)][$model->getId()][$key] = $row->{$table.$key};
					} catch(Exception $e) {}
				}

				if(method_exists($model, '__built')) call_user_func(array($model, '__built'));

				self::$loaded[$class][$model->id] = $model;
			}
			$models[$row->{$table.'id'}] = $model;
		}

		if(!$forceArray && count($models) == 1)
			return reset($models);

		return $models;
	}


	public static function match($model1, $model2)
	{
		if($model1 instanceof Model && $model2 instanceof Model) return $model1 === $model2;
		if($model1 instanceof Model && is_int((int)$model2)) return $model1->getId() == $model2;
		if(is_int((int)$model1) && $model2 instanceof Model) return $model1 == $model2->getId();
		if(is_int((int)$model1) && is_int((int)$model2)) return $model1 == $model2;
	}

}