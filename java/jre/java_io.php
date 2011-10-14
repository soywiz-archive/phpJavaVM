<?php

namespace java\io;

class PrintStream extends \java\lang\Object {
	public $f;

	public function __construct($f) {
		$this->f = $f;
	}

	public function _print($object = '') {
		if (is_bool($object)) $object = $object ? 'true' : 'false';
		\fprintf($this->f, "%s", $object);
	}
	
	public function println($object = '') {
		$this->_print($object);
		$this->_print("\n");
	}
}
