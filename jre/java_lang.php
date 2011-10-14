<?php

namespace java\lang;

class Object {
}

class String extends \java\lang\Object {
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