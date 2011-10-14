<?php

namespace java\lang;

class Object {
	public function toString() {
		return $this->__toString();
	}
}

class Exception extends \Exception {

}

class String extends \java\lang\Object {
	public $str;
	public $encoding;
	
	public function __construct($str = NULL, $encoding = 'UTF-8') {
		$this->str = $str;
		$this->encoding = $encoding;
	}
	
	public function getBytes() {
		$bytes = array();
		$len = $this->length();
		for ($n = 0; $n < $len; $n++) {
			$bytes[] = ord(mb_substr($this->str, $n, 1, $this->encoding));
		}
		return $bytes;
	}
	
	public function length() {
		return \mb_strlen($this->str, $this->encoding);
	}
	
	static public function format($format, $arguments) {
		return call_user_func_array('sprintf', array_merge(array($format), (array)$arguments));
	}
}

class System extends \java\lang\Object {
	static public $out;
}

class StringBuilder extends \java\lang\Object {
	public $str = '';
	
	public function __java_constructor($str = NULL) {
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
	
	public function __construct($value = NULL) {
		$this->value = $value;
	}
	
	public function __java_constructor($value) {
		$this->value = $value;
	}
	
	public function intValue() {
		return Integer::valueOf($this->value);
	}

	public function shortValue() {
		return \value_get_short($this->value);
	}
	
	public function byteValue() {
		return Byte::valueOf($this->value);
	}
}

class Integer extends Number {
	static public function valueOf($v) {
		return \value_get_int($v);
	}
	
	static public function toHexString($v) {
		return dechex($v);
	}
}

class Byte extends Number {
	static public function valueOf($v) {
		return \value_get_byte($v);
	}
}

interface Iterable {
	/**
	 * @return Iterator
	 */
	public function iterator();
}
