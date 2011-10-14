<?php

namespace java\lang;

class Object {
}

class String extends \java\lang\Object {
	static public function format($format, $arguments) {
		return call_user_func_array('sprintf', array_merge(array($format), (array)$arguments));
	}
}

class System extends \java\lang\Object {
	static public $out;
}

class StringBuilder extends \java\lang\Object {
	public $str = '';
	
	public function __java_constructor($str) {
		$this->str = $str;
	}
	
	public function append($object) {
		$this->str .= (string)$object;
		return $this;
	}
	
	public function toString() {
		return $this->str;
	}
}

class Number extends \java\lang\Object {
	public $value;
	
	public function __java_constructor($value) {
		$this->value = $value;
	}

	public function shortValue() {
		return \value_get_short($this->value);
	}
	
	public function byteValue() {
		return \value_get_byte($this->value);
	}
}

class Integer extends Number {
	
}

class Byte extends Number {
	static public function valueOf($v) {
		return \value_get_byte($v);
	}
}