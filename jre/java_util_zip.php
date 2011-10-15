<?php

namespace java\util\zip;

class CRC32 extends \java\lang\Object {
	protected $hash;

	public function __java_constructor() {
		$this->hash = hash_init('crc32b');
	}
	
	public function update($bytes) {
		hash_update($this->hash, \array_to_string($bytes));
	}
	
	public function getValue() {
		return hexdec(hash_final($this->hash));
	}
}