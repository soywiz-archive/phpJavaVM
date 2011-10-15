<?php

require_once(__DIR__ . '/BigInteger.php');

class PhpLong {
	public $lo, $hi;
	
	public function __construct($lo, $hi = 0) {
		$this->lo = (int)$lo;
		$this->hi = $hi;
	}

	/**
	 * @return PhpLong
	 */
	public function add(PhpLong $that) {
		//bcadd($left_operand, $right_operand)
		
		$carry = 0;
		
		if ($this->lo & 0x80000000 || $that->lo & 0x80000000) {
			if (!(($this->lo + $that->lo) & 0x80000000)) {
				$carry++;
			}
			//$carry;
		}
		
		return new PhpLong(
			$this->lo + $that->lo,
			$this->hi + $that->hi + $carry
		);
	}
	
	/**
	 * @return PhpLong
	 */
	public function mul(PhpLong $that) {
		return new PhpLong(
			$this->lo * $that->lo,
			0
		);
	}

	/**
	 * @return PhpLong
	 */
	public function div(PhpLong $that) {
		return new PhpLong(
			$this->lo / $that->lo,
			0
		);
	}
	
	/**
	 * @return PhpLong
	 */
	public function rem(PhpLong $that) {
		return new PhpLong(
			$this->lo % $that->lo,
			0
		);
	}
	
	/**
	 * @return PhpLong
	 */
	public function _xor(PhpLong $that) {
		return new PhpLong($this->lo ^ $that->lo, $this->hi ^ $that->hi);
	}

	/**
	 * @return PhpLong
	 */
	public function _and(PhpLong $that) {
		return new PhpLong($this->lo & $that->lo, $this->hi & $that->hi);
	}

	/**
	 * @return PhpLong
	 */
	public function _or(PhpLong $that) {
		return new PhpLong($this->lo | $that->lo, $this->hi | $that->hi);
	}

	/**
	 * @return PhpLong
	 */
	public function _not() {
		return new PhpLong(~$this->lo, ~$this->hi);
	}
	
	public function neg() {
		return $this->_not()->add(new PhpLong(0xFFFFFFFF, 0xFFFFFFFF));
	}
	
	public function __toString() {
		if ($this->hi & 0x80000000) {
			// Negative!
			
			$v = $this->neg();
			printf("%032b|%032b\n", $this->hi, $this->lo);
			printf("%032b|%032b\n", $v->hi, $v->lo);
			
			return $this->neg()->__toString();
			//throw(new Exception("Not implemented negative yet!"));	
		} else {
			return bcadd(bcmul(sprintf('%u', $this->hi), "4294967296"), sprintf('%u', $this->lo));
		}
	}
}

function value_get_byte($value) {
	$v = ((int)$value) & 0xFF;
	if ($v & 0x80) $v = -((~$v) & 0xFF) - 1;
	return $v;
}

function value_get_short($value) {
	$v = ((int)$value) & 0xFFFF;
	if ($v & 0x8000) $v = -((~$v) & 0xFFFF) - 1;
	return $v;
}

function value_get_char($value) {
	return value_get_short($value);
}

function value_get_int($value) {
	$v = ((int)$value) & 0xFFFFFFFF;
	if ($v & 0x80000000) $v = -((~$v) & 0xFFFFFFFF) - 1;
	return $v;
}

function value_get_long($value) {
	return new PhpLong($value);
}

function string_to_array($str) {
	$array = array();
	for ($n = 0; $n < strlen($str); $n++) {
		$array[] = ord($str[$n]);
	}
	return $array;
}

function array_to_string($bytes) {
	return implode('', array_map('chr', $bytes));
}

// Unsigned freads
function fread1($f) {
	return ord(fread($f, 1));
}

function fread2_be($f) {
	$v = unpack('n', fread($f, 2));
	return $v[1];
}

function fread4_be($f) {
	$v = unpack('N', fread($f, 4));
	return $v[1];
}

function fread8_be_s($f) {
	$h = fread4_be($f);
	$l = fread4_be($f);
	return new PhpLong($l, $h);
}

// Signed freads
function fread1_s($f) {
	return value_get_byte(fread1($f));
}

function fread2_be_s($f) {
	return value_get_short(fread2_be($f));
}

function fread4_be_s($f) {
	return value_get_int(fread4_be($f));
}


function string_to_stream($str) {
	$f = fopen('php://memory', 'r+b');
	fwrite($f, $str);
	fseek($f, 0);
	return $f;
}

function fread_stream($f, $count) {
	return string_to_stream(fread($f, $count));
}

function fread_until($f, $stopChar) {
	$r = '';
	while (!feof($f)) {
		$c = fread($f, 1);
		if ($c == $stopChar) break;
		$r .= $c;
	}
	
	return $r;
}
