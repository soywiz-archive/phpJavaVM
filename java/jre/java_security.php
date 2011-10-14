<?php

namespace java\security;

class MessageDigest extends \java\lang\Object {
	static public function getInstance($type) {
		switch ($type) {
			case 'MD5': return new MessageDigestMd5();
			default: throw(new Exception("Unknown Digest"));
		}
	}
}

class MessageDigestMd5 extends MessageDigest {
	public function digest($bytes) {
		return \string_to_array(md5(implode('', array_map('chr', $bytes)), true));
	}
}