<?php

class JavaTypeClass extends JavaType {
	public $className;
	
	public function __construct($className) {
		$this->className = $className;
	}
	
	public function __toString() {
		return 'JavaTypeClass(' . $this->className . ')';
	}
}

class JavaTypeMethod extends JavaType {
	public $params;
	public $return;
	
	public function __construct($params, $return) {
		$this->params = $params;
		$this->return = $return;
	}

	public function __toString() {
		return 'JavaTypeMethod(' . implode(', ', $this->params) . ' : ' . $this->return . ')';
	}
}

class JavaTypeArray extends JavaType {
	public $type;

	public function __construct($type) {
		$this->type = $type;
	}

	public function __toString() {
		return 'JavaTypeArray(' . $this->type . ')';
	}
}

class JavaTypeIntegral extends JavaType {
	public $integralType;

	public function __construct($integralType) {
		$this->integralType = $integralType;
	}
	
	public function __toString() {
		return 'JavaTypeIntegral(' . $this->integralType . ')';
	}
}

class JavaTypeIntegralVoid extends JavaTypeIntegral {
	public function __construct() {
		parent::__construct('void');
	}
}

class JavaTypeIntegralBool extends JavaTypeIntegral {
	public function __construct() {
		parent::__construct('bool');
	}
}

class JavaTypeIntegralByte extends JavaTypeIntegral {
	public function __construct() {
		parent::__construct('byte');
	}
}

class JavaTypeIntegralChar extends JavaTypeIntegral {
	public function __construct() {
		parent::__construct('char');
	}
}

class JavaTypeIntegralShort extends JavaTypeIntegral {
	public function __construct() {
		parent::__construct('short');
	}
}

class JavaTypeIntegralInt extends JavaTypeIntegral {
	public function __construct() {
		parent::__construct('int');
	}
}

class JavaTypeIntegralLong extends JavaTypeIntegral {
	public function __construct() {
		parent::__construct('long');
	}
}


class JavaType {
	static public function parse($f, $ori_str = NULL) {
		if (is_string($f)) $f = string_to_stream($ori_str = $f);
	
		$t = fread($f, 1);
		switch ($t) {
			// Function
			case '(':
				$params = array();
				while (($p = static::parse($f, $ori_str)) !== NULL) {
					$params[] = $p;
				}
				$return = static::parse($f, $ori_str);
				return new JavaTypeMethod($params, $return);
			break;
			// Array
			case '[':
				$arrayType = static::parse($f, $ori_str);
				return new JavaTypeArray($arrayType);
			break;
			case ')': return NULL;
			case 'V': return new JavaTypeIntegralVoid();
			case 'Z': return new JavaTypeIntegralBool();
			case 'B': return new JavaTypeIntegralByte();
			case 'C': return new JavaTypeIntegralChar();
			case 'S': return new JavaTypeIntegralShort();
			case 'I': return new JavaTypeIntegralInt();
			case 'J': return new JavaTypeIntegralLong();
			case 'L': return new JavaTypeClass(fread_until($f, ';'));
			default: throw(new Exception("Unknown java type '{$t}' on '{$ori_str}'"));
		}
	}
	
	public function __toString() {
		return 'JavaType';
	}
}
