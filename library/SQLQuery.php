<?

/**
 * A structured, object-oriented approach to building SQL queries. This is NOT database 
 * abstraction in any way; it just simplifies the process of creating SQL queries dynamically.
 * 
 * @example
 * $sql = SQLQuery::create()
 *         ->select('*')
 *         ->from('MyTable T1')
 *         ->where('x = 1 AND y = 2')
 *         ->limit(10, 10); 
 *
 * @copyright 2009 Alex Brombal
 *
 */
class SQLQuery {

	private $select = '';
	private $join = array();
	private $where = array();
	private $groupBy = '';
	private $orderBy = array();
	private $having = '';
	private $limit = '';

	public static function create() {
		return new self;
	}

	public function select($select) {
		$this->select = $select;
		return $this;
	}

	public function innerJoin($innerJoin) {
		$this->join[] = "INNER JOIN $innerJoin";
		$this->join = array_unique($this->join);
		return $this;
	}

	public function leftJoin($leftJoin) {
		$this->join[] = "LEFT JOIN $leftJoin";
		$this->join = array_unique($this->join);
		return $this;
	}

	public function where($where) {
		$this->where[] = $where;
		return $this;
	}

	public function groupBy($groupBy) {
		$this->groupBy = $groupBy;
		return $this;
	}

	public function orderBy($orderBy) {
		$this->orderBy[] = $orderBy;
		return $this;
	}

	public function having($having) {
		$this->having = $having;
		return $this;
	}

	public function limit($start, $limit = 0) {
		$this->limit = (int)$start . ($limit ? ", ".(int)$limit : '');
		return $this;
	}

	public function getSQL() {
		$sql = "SELECT $this->select ";
		if($this->join) $sql .= implode(' ', $this->join) . ' ';
		if($this->where) {
			foreach($this->where as $key => $where) {
				if(!$where) {
					unset($this->where[$key]);
					continue;
				}
				if(is_array($where)) $this->where[$key] = '(' . implode(') OR (', $where) . ')';
			}
			if($this->where) $sql .= "WHERE (" . implode(') AND (', $this->where) . ") ";
		}
		if($this->groupBy) $sql .= "GROUP BY $this->groupBy ";
		if($this->having) $sql .= "HAVING $this->having ";
		if($this->orderBy) $sql .= "ORDER BY " . implode(', ', $this->orderBy) . ' ';
		if($this->limit) $sql .= "LIMIT $this->limit";
		return $sql;
	}

	public function __toString() {
		return $this->getSQL();
	}

}