<?php

class JSON {
	protected $t, $c;
	protected $debug;
	protected $useArrays = false;

	static public function decode($s, $array = false, $debug = false) {
		//return json_decode($s, true, 512, JSON_BIGINT_AS_STRING);
		//return json_decode($s, $array);
	
		// Version >= 5.4.x will suppoort JSON_BIGINT_AS_STRING parameter.
		if (version_compare(PHP_VERSION, '5.3.99', '>=')) {
			return json_decode($s, $array, 512, JSON_BIGINT_AS_STRING);
		}
		// Not supported JSON_BIGINT_AS_STRING so we will use our class.
		else {
			try {
				$d = new JSON($s);
				$d->useArrays = $array;
				$d->setDebug($debug);
				return $d->parseObject(512);
			} catch (Exception $e) {
				return NULL;
			}
		}
	}
	
	static public function encode($object) {
		return json_encode($object);
	}
	
	public function __construct($s) {
		$this->setInput($s);
	}
	
	public function setInput($s) {
		$vv = array_map(function($a) { return isset($a[1]) ? $a[1] : $a; }, token_get_all('<?php ' . $s));
		$this->t = array_values(array_splice($vv, 1));
		$this->c = 0;
		if ($this->debug) print_r($this->t);
	}
	
	public function setDebug($debug) {
		$this->debug = $debug;
	}
	
	protected function getCurrentToken() {
		if ($this->c >= count($this->t)) throw(new Exception("EOF"));
		return $this->t[$this->c];
	}
	
	protected function nextToken() {
		$this->c++;
	}
	
	protected function parseString() {
		$ct = $this->getCurrentToken();
		if ($ct[0] != '"') throw(new Exception("JSON: Expecting String but found '" . $ct . "'"));
		$this->nextToken();
		//return stripcslashes(substr($ct, 1, -1));
		return json_decode($ct);
	}

	protected function expectValues($values = array()) {
		$ct = $this->getCurrentToken();
		foreach ($values as $value) if ($ct == $value) {
			$this->nextToken();
			return $ct;
		}
		throw(new Exception("JSON: Expecting [" . implode(', ', array_map(function($a) { return "'{$a}'"; }, $values)) . "]"));
	}
	
	protected function parseNumber() {
		$ct = $this->getCurrentToken();
		if (!is_numeric($ct)) throw(new Exception("JSON: Expecting a number but found '" . $ct . "'"));
		$this->nextToken();
		if ((string)(int)$ct == (string)$ct) {
			return (int)$ct;
		}
		return $ct;
	}
	
	protected function parseObject($depth = 512) {
		$ret = NULL;
		if ($depth == -1) throw(new Exception("JSON: Depth too long"));
		$ct = $this->getCurrentToken();
		if ($this->debug) echo "--$ct\n";
		switch ($ct[0]) {
			// Object
			case '{':
				$ret = array();
				$this->nextToken();
				if ($this->getCurrentToken() != '}') {
					do {
						$key = $this->parseString();
						if ($this->debug) echo "---key:$key\n";

						$this->expectValues(array(':'));

						$value = $this->parseObject($depth - 1);
						if ($this->debug) echo "---value:$value\n";

						$ct = $this->expectValues(array(',', '}'));
						
						$ret[$key] = $value;
					} while ($ct == ',');
				} else {
					$this->nextToken();
				}
				if (!$this->useArrays) $ret = (object)$ret;
			break;
			// Array
			case '[':
				$ret = array();
				$this->nextToken();
				if ($this->getCurrentToken() != ']') {
					do {
						$value = $this->parseObject();
						if ($this->debug) echo "---value:$value\n";
						$ct = $this->expectValues(array(',', ']'));
						$ret[] = $value;
					} while ($ct == ',');
				} else {
					$this->nextToken();
				}
				//while ()
			break;
			// String
			case '"':
				$ret = $this->parseString();
			break;
			default:
				if (is_numeric($ct[0])) {
					$ret = $this->parseNumber();
				} else {
					switch ($ct) {
						case "null": $this->nextToken(); return NULL;
						//case "undefined": $this->nextToken(); return NULL;
						case "true": $this->nextToken(); return true;
						case "false": $this->nextToken(); return false;
						default: throw(new Exception("JSON: Invalid object"));
					}
				}
			break;
		}
		return $ret;
	}
	
	static public function unittest() {
		$test = function($expr) {
			$a = JSON::decode($expr);
			$b = json_decode($expr, true);
			var_dump($a === $b);
			if ($a != $b) {
				var_dump($a);
				var_dump($b);
			}
			$a2 = JSON::encode($a);
			$b2 = JSON::encode($b);
			var_dump($a2 === $b2);
			//print_r($a);
			//print_r($b);
		};
		
		$test2 = function($expr, $b) {
			$a = JSON::decode($expr);
			var_dump($a === $b);
			if ($a != $b) {
				var_dump($a);
				var_dump($b);
			}
			//var_export($a);
		};
	
		// Empty object.
		$test('{}');

		// Simple object
		$test('{"a":"b"}');

		// Object with comma separated items.
		$test('{"k1":"v1","k2":"v2"}');

		// Simple object with numbers
		$test('{"k1":1,"k2":2}');

		// Simple array with numbers
		$test('[1,2,3,4]');
		
		$test('"\\u10000"');

		// Array with langbuage's constants.
		$test('[3,false,true,null]');

		// Complex array with subobjects.
		$test('[{},{"a":{"b":1}},3,false,{"test":true}]');

		// Complex array.
		$test2(
			'{"order_id":131608840232655,"buyer":100001738131058,"app":118178464915404,"receiver":100001738131058,"amount":2,"update_time":1292862324,"time_placed":1292862322,"data":"","items":[{"item_id":"0","title":"[Test Mode] Unicorn","description":"[Test Mode] Own your own mythical beast!","image_url":"http:\/\/www.facebook.com\/images\/gifts\/21.png","product_url":"http:\/\/www.facebook.com\/images\/gifts\/21.png","price":2,"data":"{\"product_id\":1}"}],"status":"placed"}',
			array (
				'order_id' => '131608840232655',
				'buyer' => '100001738131058',
				'app' => '118178464915404',
				'receiver' => '100001738131058',
				'amount' => 2,
				'update_time' => 1292862324,
				'time_placed' => 1292862322,
				'data' => '',
				'items' => array (
					0 => array (
						'item_id' => '0',
						'title' => '[Test Mode] Unicorn',
						'description' => '[Test Mode] Own your own mythical beast!',
						'image_url' => 'http://www.facebook.com/images/gifts/21.png',
						'product_url' => 'http://www.facebook.com/images/gifts/21.png',
						'price' => 2,
						'data' => '{"product_id":1}',
					),
				),
				'status' => 'placed',
			)
		);
	}
	
	static public function _unittest() {
		if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
			JSON::unittest();
		}
	}
}

function json_decode_fixed($s, $debug = false) {
	return JSON::decode($s, $debug);
}

JSON::_unittest();