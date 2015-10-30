<?

class MongoModel extends BaseClass
{
    public static $mongoConnection;
    public static $mongoDbName;

    private static $mongoClient;
    private static $mongoDb;

    public static function db()
    {
        if (!self::$mongoClient)
        {
            self::$mongoClient = new MongoClient(self::$mongoConnection);
            self::$mongoDb = self::$mongoClient->{self::$mongoDbName};
        }

        return self::$mongoDb;
    }



    public static $collection;

    /**
     * @var MongoId
     */
    public $_id;

    public function id() { return $this->_id->{'$id'}; }

    public static function collection($name = null)
    {
        return MongoModel::db()->{$name ?: static::$collection};
    }

    public static function findOne(array $query = array(), array $fields = array())
    {
        return ($result = static::collection()->findOne($query, $fields)) ? new static($result, true) : null;
    }

    public static function findAndModify(array $query, array $update = NULL, array $fields = NULL, array $options = NULL)
    {
        return ($result = static::collection()->findAndModify($query, $update, $fields, $options)) ? new static($result, true) : null;
    }

    public static function find(array $query = array(), array $fields = array(), array $sort = array())
    {
        $result = static::collection()->find($query, $fields);
        if($sort) $result->sort($sort);
        $results = [];
        foreach ($result as $r)
            $results[] = new static($r, true);
        return $results;
    }

    public static function count(array $query = array())
    {
        return static::collection()->count($query);
    }

    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array(array(static::collection(), $name), $arguments);
    }


    public function save($collection = null)
    {
        $obj = get_object_vars($this);
        if ($obj['_id'] === null) unset($obj['_id']);
        $collection = $this->collection($collection);
        $result = $collection->save($obj);
        $this->_id = $obj['_id'];
        return $result;
    }
}
