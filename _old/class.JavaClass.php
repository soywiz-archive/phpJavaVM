<?php

class JavaClass_Constant {
	public $javaClass;
	public $file_offset;

	static public function create($javaClass) {
		$class = get_called_class();
		$object = new $class;
		$object->javaClass = $javaClass;
		return $object;
	}

	public function __call($name, $params) {
		$this->$name = $params[0];
		return $this;
	}

	public function __toString() {
		static $ignore = array('javaClass', 'file_offset');
		$z = array();
		foreach ($this as $k => $v) {
			if (in_array($k, $ignore)) continue;
			//if ($k == 'file_offset') continue;
			$z[] = sprintf("'%s' => '%s'", $k, $v);
		}
		return sprintf('%s(%s)', get_called_class(), implode(', ', $z));
	}
	
	public function str() {
		return get_called_class();
	}
	
	public function getFromPool($index) {
		$r = &$this->javaClass->constant_pool[$index];
		if (!isset($r)) throw(new Exception("Invalid index {$index}"));
		return $r;
	}
	
	public function name() {
		return $this->getFromPool($this->name_index)->str();
	}
}

class JavaClass_Constant_Class extends JavaClass_Constant {
	public $name_index;
	
	public function str() {
		return $this->getFromPool($this->name_index)->str();
	}
}

class JavaClass_Constant_String extends JavaClass_Constant {
	public $name_index;
	
	public function str() {
		return get_called_class();
	}
}

class JavaClass_Constant_HIGHLOW extends JavaClass_Constant {
	public $high;
	public $low;
}

class JavaClass_Constant_Long extends JavaClass_Constant_HIGHLOW { }
class JavaClass_Constant_Double extends JavaClass_Constant_HIGHLOW { }


class JavaClass_Constant_Integer extends JavaClass_Constant {
	public $value;
}


class JavaClass_Constant_Utf8 extends JavaClass_Constant {
	public $str;
	
	function str() {
		return $this->str;
	}
}

class JavaClass_Constant_COMMONref extends JavaClass_Constant {
	public $class_index;
	public $name_and_type_index;
}

class JavaClass_Constant_Methodref extends JavaClass_Constant_COMMONref { }
class JavaClass_Constant_Fieldref extends JavaClass_Constant_COMMONref { }
class JavaClass_Constant_InterfaceMethodref extends JavaClass_Constant_COMMONref { }

class JavaClass_Constant_NameAndType extends JavaClass_Constant {
	public $name_index;
	public $descriptor_index;
}

class JavaClass_COMMON_Info extends JavaClass_Constant {
	const ACC_PUBLIC       = 0x0001; // Declared public; may be accessed from outside its package.
	const ACC_PRIVATE      = 0x0002; // Declared private; usable only within the defining class.
	const ACC_PROTECTED    = 0x0004; // Declared protected; may be accessed within subclasses.
	const ACC_STATIC       = 0x0008; // Declared static.
	const ACC_FINAL        = 0x0010; // Declared final; no further assignment after initialization.
	const ACC_VOLATILE     = 0x0040; // Declared volatile; cannot be cached.
	const ACC_TRANSIENT    = 0x0080; // Declared transient; not written or read by a persistent object manager.

	public $access_flags;
	public $name_index;
	public $descriptor_index;
	public $attributes_count;
	public $attributes;
	
	public function __toString() {
		$class = new ReflectionClass(__CLASS__);
		//print_r($class->getConstants());
		$access_flags_array = array();
		foreach ($class->getConstants() as $name => $value) {
			if (substr($name, 0, 4) != 'ACC_') continue;
			if ($this->access_flags & $value) $access_flags_array[] = substr($name, 4);
		}
		$attrs = implode(' ', $access_flags_array);
		return sprintf(
			'%s(name=%s, attrs(%04X)=%s, file_offset=%08X)',
			get_called_class(),
			$this->getFromPool($this->name_index)->str(),
			$this->access_flags,
			$attrs,
			$this->file_offset
		);
	}
}

class JavaClass_FieldInfo extends JavaClass_COMMON_Info { }
class JavaClass_MethodInfo extends JavaClass_COMMON_Info { }

class JavaClass_Attributes extends JavaClass_Constant {
	public $count;
	public $values;
}

class JavaClass_Attribute extends JavaClass_Constant {
	public $name_index;
	public $length;
	public $data;
}

/**
 * http://java.sun.com/docs/books/jvms/second_edition/html/ClassFile.doc.html
 */
class JavaClass {
	public $f;
	public $constant_pool;
	public $interface;
	public $fields;
	public $methods;

	const CONSTANT_Utf8 = 1;
	const CONSTANT_Integer = 3;
	const CONSTANT_Float = 4;
	const CONSTANT_Long = 5;
	const CONSTANT_Double = 6;
	const CONSTANT_Class = 7;
	const CONSTANT_String = 8;
	const CONSTANT_Fieldref = 9;
	const CONSTANT_Methodref = 10;
	const CONSTANT_InterfaceMethodref = 11;
	const CONSTANT_NameAndType = 12;

	/*
	const ACC_PUBLIC       = 0x0001; // Declared public; may be accessed from outside its package.
	const ACC_PRIVATE      = 0x0002; // Declared private; usable only within the defining class.
	const ACC_PROTECTED    = 0x0004; // Declared protected; may be accessed within subclasses.
	const ACC_STATIC       = 0x0008; // Declared static.
	const ACC_FINAL        = 0x0010; // Declared final; no further assignment after initialization.
	
	const ACC_SYNCHRONIZED = 0x0020; // Declared synchronized; invocation is wrapped in a monitor lock.
	const ACC_SUPER        = 0x0020; // Treat superclass methods specially when invoked by the invokespecial instruction.
	
	const ACC_VOLATILE     = 0x0040; // Declared volatile; cannot be cached.
	const ACC_TRANSIENT    = 0x0080; // Declared transient; not written or read by a persistent object manager.
	const ACC_NATIVE       = 0x0100; // Declared native; implemented in a language other than Java.
	const ACC_INTERFACE    = 0x0200; // Is an interface, not a class.
	const ACC_ABSTRACT     = 0x0400; // Declared abstract; may not be instantiated.
	const ACC_STRICT       = 0x0800; // Declared strictfp; floating-point mode is FP-strict
	*/
	
	public function __construct($name) {
		$this->f = fopen($name, 'rb');
		$this->parseHeader();
	}
	
	protected function r($l, $param = NULL) {
		if (feof($this->f)) {
			throw(new Exception("EOF"));
		}
		switch ($l) {
			case 4: $d = unpack('N', fread($this->f, 4)); return $d[1];
			case 2: $d = unpack('n', fread($this->f, 2)); return $d[1];
			case 1: return ord(fread($this->f, 1));
			case 'align': while (ftell($this->f) % $param) fread($this->f, 1); break;
			case 'cp_info':
				//$this->r('align', 2);
				$tag = $this->r(1);
				//echo "TAG: $tag\n";
				switch ($tag) {
					case 0:
						fprintf(STDERR, "********************** Bad length\n");
						fseek($this->f, -1, SEEK_CUR);
						return NULL;
					break;
					/*
					case 0:
						$this->r(2);
						$this->r(2);
						$this->r(2);
						$this->r(2);
						$this->r(2);
						$this->r(2);
						return NULL;
					break;
					*/
					case JavaClass::CONSTANT_Integer:
						$r = JavaClass_Constant_Integer::create($this);
						$r->value($this->r(4));
						return $r;
					break;
					case JavaClass::CONSTANT_Double:
						$r = JavaClass_Constant_Double::create($this);
						$r->high($this->r(4));
						$r->low($this->r(4));
						return $r;
					break;
					case JavaClass::CONSTANT_Class:
						$r = JavaClass_Constant_Class::create($this);
						$r->name_index = $this->r(2);
						return $r;
					break;
					case JavaClass::CONSTANT_Utf8:
						$r = JavaClass_Constant_Utf8::create($this);
						$length = $this->r(2);
						//echo $length;
						$r->str = ($length > 0) ? fread($this->f, $length) : '';
						return $r;
					break;
					case JavaClass::CONSTANT_String:
						$name_index = $this->r(2);
						return JavaClass_Constant_String::create($this)
							->name_index($name_index)
						;
					break;
					case JavaClass::CONSTANT_Long:
						$r = JavaClass_Constant_Long::create($this);
						$r->high($this->r(4));
						$r->low($this->r(4));
						return $r;
					break;
					case JavaClass::CONSTANT_Fieldref:
					case JavaClass::CONSTANT_Methodref:
					case JavaClass::CONSTANT_InterfaceMethodref:
						$class_index = $this->r(2);
						$name_and_type_index = $this->r(2);
						
						switch ($tag) {
							case JavaClass::CONSTANT_Fieldref: $class = 'JavaClass_Constant_Fieldref'; break;
							case JavaClass::CONSTANT_Methodref: $class = 'JavaClass_Constant_Methodref'; break;
							case JavaClass::CONSTANT_InterfaceMethodref: $class = 'JavaClass_Constant_InterfaceMethodref'; break;
							default: assert(0);
						}
						
						return $class::create($this)
							->class_index($class_index)
							->name_and_type_index($name_and_type_index)
						;
					break;
					case JavaClass::CONSTANT_NameAndType:
						$name_index = $this->r(2);
						$descriptor_index = $this->r(2);
						return JavaClass_Constant_NameAndType::create($this)
							->name_index($name_index)
							->descriptor_index($descriptor_index)
						;
					break;
					default:
						throw(new Exception("Unknown tag id {$tag}"));
					break;
				}
			break;
			case 'field_info':
				$r = JavaClass_FieldInfo::create($this);
				$r->file_offset      = ftell($this->f);
				$r->access_flags     = $this->r(2);
				$r->name_index       = $this->r(2);
				$r->descriptor_index = $this->r(2);
				$r->attributes       = $this->r('attributes');
				return $r;
			break;
			case 'method_info':
				$r = JavaClass_MethodInfo::create($this);
				$r->file_offset      = ftell($this->f);
				$r->access_flags     = $this->r(2);
				$r->name_index       = $this->r(2);
				$r->descriptor_index = $this->r(2);
				$r->attributes       = $this->r('attributes');
				return $r;
			break;
			case 'attributes':
				$r = JavaClass_Attributes::create($this);
				$r->file_offset      = ftell($this->f);
				$r->count = $this->r(2);
				$r->values = array();
				for ($n = 0; $n < $r->count; $n++) {
					$r->values[] = $this->r('attribute_info');
				}
				return $r;
			break;
			case 'attribute_info':
				$r = JavaClass_Attribute::create($this);
				$r->file_offset      = ftell($this->f);
				$r->name_index = $this->r(2);
				$r->length = $this->r(4);
				$r->data = fread($this->f, $r->length);
				return $r;
			break;
			default: throw(new Exception("Trying to read '{$l}'"));
		}
	}
	
	protected function parseHeader() {
		/*
		ClassFile {
			u4 magic;
			u2 minor_version;
			u2 major_version;
			u2 constant_pool_count;
			cp_info constant_pool[constant_pool_count-1];
			u2 access_flags;
			u2 this_class;
			u2 super_class;
			u2 interfaces_count;
			u2 interfaces[interfaces_count];
			u2 fields_count;
			field_info fields[fields_count];
			u2 methods_count;
			method_info methods[methods_count];
			u2 attributes_count;
			attribute_info attributes[attributes_count];
		}
		*/
		$magic = $this->r(4);
		//printf("%08X, %08X\n", $magic, 0xBEBAFECA);
		//print_R(unpack('H*', $magic));
		//$z = unpack('H*', $magic);
		
		if (dechex($magic) != 'cafebabe') throw(new Exception("Not a java class file"));
		$minor_version = $this->r(2);
		$major_version = $this->r(2);
		//echo "version: $minor_version, $major_version\n";
		
		$constant_pool_count = $this->r(2);
		//echo "constant_pool_count: $constant_pool_count\n";
		for ($n = 1; $n < $constant_pool_count; $n++) {
			$cp = $this->r('cp_info');
			//printf("%04d: %s\n", $n, $cp);
			if ($cp === NULL) break;
			$this->constant_pool[$n] = $cp;
		}
		
		//$this->r('align', 2);
		
		$access_flags = $this->r(2);
		//printf("access_flags: %04X\n", $access_flags);
		
		$this_class = $this->r(2);
		//printf("this_class: %04d\n", $this_class);
		
		$super_class = $this->r(2);
		//printf("super_class: %04d\n", $super_class);
		
		$interfaces_count = $this->r(2);
		//printf("interfaces_count: %d\n", $interfaces_count);
		for ($n = 0; $n < $interfaces_count; $n++) {
			$this->interfaces[$n] = $this->r(2);
		}

		$fields_count = $this->r(2);
		//printf("fields_count: %d\n", $fields_count);
		for ($n = 0; $n < $fields_count; $n++) {
			$field = $this->r('field_info');
			$this->fields[$field->name()] = $field;
		}
		
		$methods_count = $this->r(2);
		//printf("methods_count: %d\n", $methods_count);
		for ($n = 0; $n < $methods_count; $n++) {
			$method = $this->r('method_info');
			$this->methods[$method->str()] = $method;
		}
		
		$attributes = $this->r('attributes');
		//echo $attributes;
		//echo "\n";
	}
	
	function getFieldByName($name) {
		return $this->fields[$name];
	}
}

/*
	require_once(__DIR__ . '/class.JavaClass.php');
	$jc = new JavaClass("className.class");
	echo $jc->fields['instance'];
*/