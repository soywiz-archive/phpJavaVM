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

class JavaTypeIntegralInt extends JavaTypeIntegral {
	public function __construct() {
		parent::__construct('int');
	}
}

class JavaType {
	static public function parse($f) {
		if (is_string($f)) $f = string_to_stream($f);
	
		$t = fread($f, 1);
		switch ($t) {
			// Function
			case '(':
				$params = array();
				while (($p = static::parse($f)) !== NULL) {
					$params[] = $p;
				}
				$return = static::parse($f);
				return new JavaTypeMethod($params, $return);
			break;
			case ')': return NULL;
			case 'V': return new JavaTypeIntegralVoid();
			case 'I': return new JavaTypeIntegralInt();
			case 'L': return new JavaTypeClass(fread_until($f, ';'));
			default: throw(new Exception("Unknown java type '{$t}'"));
		}
	}
	
	public function __toString() {
		return 'JavaType';
	}
}
