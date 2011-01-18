<?php

class Segment implements Countable {
	public $l, $r;
	
	function __construct($l, $r) {
		$this->l = $l;
		$this->r = $r;
	}

	function count() { return $this->r - $this->l; }
	function valid() { return $this->count() >= 0; }
	function grow(Segment $that) {
		$this->l = min($this->l, $that->l);
		$this->r = max($this->r, $that->r);
	}

	static function intersect(Segment $a, Segment $b, $strict = false) {
		return ($strict
			? ($a->l <  $b->r && $a->r >  $b->l)
			: ($a->l <= $b->r && $a->r >= $b->l)
		);
	}

	function opCmp(Segment $that) {
		$r = $this->l - $that->l;
		if ($r == 0) $r = $this->r - $that->r;
		return $r;
	}
	
	//function opEquals(Segment $that) { return ($this->l == $that->l) && ($this->r == $that->r); }
	
	function __toString() {
		return sprintf('(%08X, %08X)', $this->l, $this->r);
	}
}

class Segments implements Countable, ArrayAccess {
	public $segments = array();
	
	function refactor() {
		usort($this->segments, function(Segment $a, Segment $b) {
			return $a->opCmp($b);
		});
	}
	
	function count() { return count($this->segments); }

	function offsetGet($index) { return $this->segments[$index]; }
	function offsetSet($index, $v) { assert('0'); }
	function offsetUnset($index) { assert('0'); }
	function offsetExists($index) { assert('0'); }
	
	function add(Segment $s) {
		$find = false;
		foreach ($this->segments as $cs) {
			if (Segment::intersect($s, $cs)) {
				$cs->grow($s);
				$find = true;
				break;
			}
		}
		if (!$find) $this->segments[] = $s;
		$this->refactor();
		return $this;
	}
	
	function remove(Segment $s) {
		$ss = array();
		$addValid = function(Segment $s) use (&$ss) { if ($s->valid()) $ss[] = $s; };
		foreach ($this->segments as $cs) {
			if (Segment::intersect($s, $cs)) {
				$addValid(new Segment($cs->l, $s->l));
				$addValid(new Segment($s->r, $cs->r));
			} else {
				$addValid($cs);
			}
		}
		$this->segments = $ss;
		$this->refactor();
		return $this;
	}
	
	public $textList = array();
	
	function allocate($size) {
		foreach ($this->segments as $s) {
			if ($s->count() >= $size) {
				$this->remove(new Segment($s->l, $s->l + $size));
				return $s->l;
			}
		}
		throw(new Exception("Can't allocate {$size} bytes"));
	}
	
	function allocateDataOnce($text) {
		$ref = &$this->textList[$text];
		if (!isset($ref)) $ref = $this->allocate(strlen($text));
		return $ref;
	}

	function __toString() { $r = "Segments {\n"; foreach ($this->segments as $s) $r .= "  " . $s . "\n"; $r .= "}"; return $r; }

	static public function __unittest() {
		echo "Segments::__unittest()\n";
		$ss = new Segments;
		$ss->add(new Segment(0, 100));
		$ss->add(new Segment(50, 200));
		$ss->add(new Segment(-50, 0));
		$ss->remove(new Segment(0, 50));
		$ss->remove(new Segment(0, 75));
		$ss->add(new Segment(-1500, -100));
		$ss->remove(new Segment(-1000, 1000));
		assert('count($ss) == 1');
		assert('$ss[0] == new Segment(-1500, -1000)');
	}
}

print_r($argv)
Segments::__unittest();
