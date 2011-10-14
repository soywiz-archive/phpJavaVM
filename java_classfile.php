<?php

// http://java.sun.com/docs/books/jvms/second_edition/html/ClassFile.doc.html

/*
struct Class_File_Format {
   u4 magic_number;   
 
   u2 minor_version;   
   u2 major_version;   
 
   u2 constant_pool_count;   
 
   cp_info constant_pool[constant_pool_count - 1];
 
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

class JavaClassFile {
	public function __construct() {
		$this->constantPool = new JavaConstantPool();
	}
	
	public $constantPool;
	
	public function readClassFile($f) {
		$magic              = fread4_be($f);
		$minor_version      = fread2_be($f);
		$major_version      = fread2_be($f);
		$contant_pool_count = fread2_be($f);

		static $major_version_map = array(
			51 => 'J2SE 7',
			50 => 'J2SE 6.0',
			49 => 'J2SE 5.0',
			48 => 'JDK 1.4',
			47 => 'JDK 1.3',
			46 => 'JDK 1.2',
			45 => 'JDK 1.1',
		);
		
		$major_version_string = $major_version_map[$major_version];
		
		for ($index = 1; $index < $contant_pool_count; $index++) {
			//printf("%08X\n", ftell($f));
			$JavaConstant = $this->readConstantPoolInfo($f);
			$this->constantPool->add($index, $JavaConstant);
			echo "{$index}: {$JavaConstant}\n";
		}
		
		$access_flags = fread2_be($f);
		$this_class = fread2_be($f);
		$super_class = fread2_be($f);
		
		$interfaces_count = fread2_be($f);
		$interfaces = array();
		for ($n = 0; $n < $interfaces_count; $n++) {
			$interfaces[] = fread2_be($f);
		}
		//print_r($interfaces);

		$fields_count = fread2_be($f);
		$fields = array();
		for ($n = 0; $n < $fields_count; $n++) {
			$fields[] = $this->readFieldInfo($f);
		}
		//print_r($fields);

		$methods_count = fread2_be($f);
		$methods = array();
		for ($n = 0; $n < $methods_count; $n++) {
			$method = $this->readMethodInfo($f);
			$methods[] = $method;
			$method_data = $method->attributes[0]->data;
			//print_r(unpack('H*', $method_data));
			//print_r($method);
			$javaDisassembler = new JavaDisassembler($this->constantPool, $method_data);
			$javaDisassembler->disasm();
		}
		//print_r($methods);
		
		$attributes_count = fread2_be($f);
		$attributes = array();
		for ($n = 0; $n < $attributes_count; $n++) {
			$attributes[] = $this->readAttributeInfo($f);
		}
	}

	// Class
	const ACC_CLASS_PUBLIC        = 0x0001; // Declared public; may be accessed from outside its package.
	const ACC_CLASS_FINAL         = 0x0010; // Declared final; no subclasses allowed.
	const ACC_CLASS_SUPER         = 0x0020; // Treat superclass methods specially when invoked by the invokespecial instruction.
	const ACC_CLASS_INTERFACE     = 0x0200; // Is an interface, not a class.
	const ACC_CLASS_ABSTRACT      = 0x0400; // Declared abstract; may not be instantiated.
	
	// Fields
	const ACC_FIELD_PUBLIC        = 0x0001; // Declared public; may be accessed from outside its package.
	const ACC_FIELD_PRIVATE       = 0x0002; // Declared private; usable only within the defining class.
	const ACC_FIELD_PROTECTED     = 0x0004; // Declared protected; may be accessed within subclasses.
	const ACC_FIELD_STATIC        = 0x0008; // Declared static.
	const ACC_FIELD_FINAL         = 0x0010; // Declared final; no further assignment after initialization.
	const ACC_FIELD_VOLATILE      = 0x0040; // Declared volatile; cannot be cached.
	const ACC_FIELD_TRANSIENT     = 0x0080; // Declared transient; not written or read by a persistent object manager.

	// Methods
	const ACC_METHOD_PUBLIC       = 0x0001; // Declared public; may be accessed from outside its package.
	const ACC_METHOD_PRIVATE      = 0x0002; // Declared private; accessible only within the defining class.
	const ACC_METHOD_PROTECTED    = 0x0004; // Declared protected; may be accessed within subclasses.
	const ACC_METHOD_STATIC       = 0x0008; // Declared static.
	const ACC_METHOD_FINAL        = 0x0010; // Declared final; may not be overridden.
	const ACC_METHOD_SYNCHRONIZED = 0x0020; // Declared synchronized; invocation is wrapped in a monitor lock.
	const ACC_METHOD_NATIVE       = 0x0100; // Declared native; implemented in a language other than Java.
	const ACC_METHOD_ABSTRACT     = 0x0400; // Declared abstract; no implementation is provided.
	const ACC_METHOD_STRICT       = 0x0800; // Declared strictfp; floating-point mode is FP-strict

	const CONSTANT_Utf8               = 1;
	const CONSTANT_Integer            = 3;
	const CONSTANT_Float              = 4;
	const CONSTANT_Long               = 5;
	const CONSTANT_Double             = 6;
	const CONSTANT_Class              = 7;
	const CONSTANT_String             = 8;
	const CONSTANT_Fieldref           = 9;
	const CONSTANT_Methodref          = 10;
	const CONSTANT_InterfaceMethodref = 11;
	const CONSTANT_NameAndType        = 12;
	
	protected function readConstantPoolInfo($f) {
		$type = fread1($f);

		switch ($type) {
			case self::CONSTANT_Utf8:
				$stringLength = fread2_be($f);
				return new JavaConstantString($this->constantPool, fread($f, $stringLength));

			case self::CONSTANT_Integer:
				break;
			case self::CONSTANT_Float:
				break;
			case self::CONSTANT_Long:
				break;
			case self::CONSTANT_Double:
				break;
			case self::CONSTANT_Class:
				$classStringIndex = fread2_be($f);
				return new JavaConstantClassReference($this->constantPool, $classStringIndex);

			case self::CONSTANT_String:
				$classStringIndex = fread2_be($f);
				return new JavaConstantStringReference($this->constantPool, $classStringIndex);

			case self::CONSTANT_Fieldref:
				$classReferenceIndex     = fread2_be($f);
				$nameTypeDescriptorIndex = fread2_be($f);
				return new JavaConstantFieldReference($this->constantPool, $classReferenceIndex, $nameTypeDescriptorIndex);

			case self::CONSTANT_Methodref:
				$classReferenceIndex     = fread2_be($f);
				$nameTypeDescriptorIndex = fread2_be($f);
				return new JavaConstantMethodReference($this->constantPool, $classReferenceIndex, $nameTypeDescriptorIndex);
				
			case self::CONSTANT_InterfaceMethodref:
				break;
			case self::CONSTANT_NameAndType:
				$identifierNameStringIndex = fread2_be($f);
				$typeDescriptorStringIndex = fread2_be($f);
				return new JavaConstantNameTypeDescriptor($this->constantPool, $identifierNameStringIndex, $typeDescriptorStringIndex);
				
			default:
				throw(new Exception("Unknown type of constant pool info {$type}"));
		}
		
		throw(new Exception("Unimplemented type of constant pool {$type}"));
	}

	protected function _readMemberInfo($f) {
		$access_flags     = fread2_be($f);
		$name_index       = fread2_be($f);
		$descriptor_index = fread2_be($f);
		$attributes_count = fread2_be($f);
		$attributes = array();
		for ($n = 0; $n < $attributes_count; $n++) {
			$attributes[] = $this->readAttributeInfo($f);
		}
		return new JavaMethod($this->constantPool, $access_flags, $name_index, $descriptor_index, $attributes);
	}
	
	protected function readMethodInfo($f) {
		return $this->_readMemberInfo($f);
	}

	protected function readFieldInfo($f) {
		return $this->_readMemberInfo($f);
	}
	
	protected function readAttributeInfo($f) {
		$attribute_name_index = fread2_be($f);
		$attribute_length = fread4_be($f);
		$attribute_data = fread($f, $attribute_length);
		return new JavaAttribute($this->constantPool, $attribute_name_index, $attribute_data);
	}
}

class JavaAttribute {
	public $constantPool;
	public $name_index;
	public $data;

	public function __construct(JavaConstantPool $constantPool, $name_index, $data) {
		$this->constantPool = $constantPool;
		$this->name_index = $name_index;
		$this->data = $data;
	}
}
