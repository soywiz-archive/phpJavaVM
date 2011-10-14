<?php

namespace java\io;

class PrintStream extends \java\lang\Object {
	public $f;

	public function __construct($f) {
		$this->f = $f;
	}

	public function println($object) {
		\fprintf($this->f, "%s\n", $object);
	}
}
