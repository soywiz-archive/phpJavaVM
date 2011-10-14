<?php

namespace java\util;

interface Iterator {
	/**
	 * @return boolean
	 */
	public function hasNext();

	/**
	 * @return Object
	 */
	public function next();

	/**
	 */
	public function remove();
}

class ArrayIterator implements Iterator {
	protected $array;
	protected $index;
	
	public function __construct($array) {
		$this->index = 0;
		$this->array = $array;
	}
	
	public function hasNext() {
		return ($this->index < count($this->array));
	}
	
	public function next() {
		$value = $this->array[$this->index];
		$this->index++;
		return $value;
	}
		
	public function remove() {
		throw(new \Exception("Not implemented!"));
	}
}

class Collection extends \java\lang\Object implements \java\lang\Iterable {
	public $array;
	
	public function __construct($array = array()) {
		$this->array = $array; 
	}
	
	public function iterator() {
		return new ArrayIterator($this->array);
	}
}

class Set extends Collection {

}

class HashMap extends \java\lang\Object {
	public $array = array();
	
	public function __java_constructor() {
		
	}
	
	public function put($key, $value) {
		$this->array[$key] = $value;
	}

	public function get($key) {
		return $this->array[$key];
	}
	
	public function keySet() {
		return new Set(array_keys($this->array));
	}

	public function values() {
		return new Collection(array_values($this->array));
	}
}