<?

class HTMLOptions {

	public $values = array();
	public $selected;
	public $useKeys = true;

	public function __construct($values, $selected = null, $keyProp = null, $valueProp = null) {
		$arrayKey = 0;
		foreach((array)$values as $key => $value) {
			if($key === $arrayKey++) $this->useKeys = false;
			if(is_object($value)) {
				if(substr($keyProp, -2) == '()') {
					$keyFunc = substr($keyProp, 0, -2);
					$key = (string)$value->$keyFunc();
				} else {
					$key = (string)$value->$keyProp;
				}
				if(substr($valueProp, -2) == '()') {
					$valueFunc = substr($valueProp, 0, -2);
					$value = (string)$value->$valueFunc();
				} else {
					$value = (string)$value->$valueProp;
				}
			}
			$this->values[] = array('key' => $key, 'value' => $value);
		}
		$this->selected = $selected;
	}

	public function __toString() {
		$str = '';
		foreach($this->values as $values) {
			$value = htmlentities($values['value']);
			$key = $this->useKeys
						? htmlentities($values['key'])
						: (
							$this->useKeys === false
								? $value
								: (is_int($values['key']) ? $value : htmlentities($values['key']))
						);
			$str .= '<option value="' . $key . '"' . ((string)$key == (string)$this->selected || (string)$value == (string)$this->selected ? ' selected="selected"' : '') . ">$value</option>";
		}
		return $str;
	}

}