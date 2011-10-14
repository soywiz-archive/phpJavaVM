<?php

class JavaConstantPool {
	public $table = array();
	
	public function add($index, JavaConstant $JavaConstant) {
		$this->table[$index] = $JavaConstant;
	}
	
	public function get($index) {
		return $this->table[$index];
	}
}

class JavaConstant {
	/**
	 * @var JavaConstantPool
	 */
	public $constantPool;
	
	public function __construct(JavaConstantPool $constantPool) {
		$this->constantPool = $constantPool;
	}
	
	public function __toString() {
		$params = array();
		foreach ($this as $k => $v) {
			if ($k == 'constantPool') continue;
			$params[] = "{$k}:'{$v}'";
		}
		return get_called_class() . '(' . implode(',', $params) . ')';
	}
	
	public function getNormalizedString() {
		return get_called_class();
	}
	
	public function getValue() {
		return NULL;
	}
}

class JavaConstantStringReference extends JavaConstant {
	public $classStringIndex;

	public function __construct(JavaConstantPool $constantPool, $classStringIndex) {
		parent::__construct($constantPool);
		$this->classStringIndex = $classStringIndex;
	}

	public function getNormalizedString() {
		//return "STR_REF(" . $this->constantPool->get($this->classStringIndex)->getNormalizedString() . ")";
		return $this->constantPool->get($this->classStringIndex)->getNormalizedString();
	}
	
	public function getString() {
		return $this->constantPool->get($this->classStringIndex)->string;
	}
	
	public function getValue() {
		return $this->getString();
	}
}

class JavaConstantClassReference extends JavaConstantStringReference {
	public function getNormalizedString() {
		return 'CLASS(' . json_encode($this->getClassName()) . ')';
	}
	
	/**
	 * @return string
	 */
	public function getClassName() {
		return $this->constantPool->get($this->classStringIndex)->string;
	}
}

class JavaConstantString extends JavaConstant {
	public $string;

	public function __construct(JavaConstantPool $constantPool, $string) {
		parent::__construct($constantPool);
		$this->string = $string;
	}
	
	public function getNormalizedString() {
		return json_encode($this->string);
	}
}

class JavaConstantMemberReference extends JavaConstant {
	public $classReferenceIndex;
	public $nameTypeDescriptorIndex;

	public function __construct(JavaConstantPool $constantPool, $classReferenceIndex, $nameTypeDescriptorIndex) {
		parent::__construct($constantPool);
		$this->classReferenceIndex = $classReferenceIndex;
		$this->nameTypeDescriptorIndex = $nameTypeDescriptorIndex;
	}
	
	protected function _getNormalizedStringType() {
		return 'MEMBER_REF';
	}
	
	public function getNormalizedString() {
		return $this->_getNormalizedStringType() . "(" .
			$this->constantPool->get($this->classReferenceIndex)->getNormalizedString() . ", " .
			$this->constantPool->get($this->nameTypeDescriptorIndex)->getNormalizedString() . 
		")";
	}
	
	/**
	 * @return JavaConstantClassReference
	 */
	public function getClassReference() {
		return $this->constantPool->get($this->classReferenceIndex);
	}

	/**
	 * @return JavaConstantNameTypeDescriptor 
	 */
	public function getNameTypeDescriptor() {
		return $this->constantPool->get($this->nameTypeDescriptorIndex);
	}
}

class JavaConstantMethodReference extends JavaConstantMemberReference {
	protected function _getNormalizedStringType() {
		return 'METHOD_REF';
	}
}

class JavaConstantFieldReference extends JavaConstantMemberReference {
	protected function _getNormalizedStringType() {
		return 'FIELD_REF';
	}
}

class JavaConstantNameTypeDescriptor extends JavaConstant {
	public $identifierNameStringIndex;
	public $typeDescriptorStringIndex;

	public function __construct(JavaConstantPool $constantPool, $identifierNameStringIndex, $typeDescriptorStringIndex) {
		parent::__construct($constantPool);
		$this->identifierNameStringIndex = $identifierNameStringIndex;
		$this->typeDescriptorStringIndex = $typeDescriptorStringIndex;
	}
	
	public function getNormalizedString() {
		return "NAME_TYPE_REF(" .
			$this->constantPool->get($this->identifierNameStringIndex)->getNormalizedString() . ", " .
			$this->getTypeDescriptor() . 
		")";
	}
	
	/**
	 * @return string 
	 */
	public function getIdentifierNameString() {
		return $this->constantPool->get($this->identifierNameStringIndex)->string;
	}

	/**
	 * @return string 
	 */
	public function getTypeDescriptorString() {
		return $this->constantPool->get($this->typeDescriptorStringIndex)->string;
	}

	/**
	 * @return JavaType 
	 */
	public function getTypeDescriptor() {
		return JavaType::parse($this->getTypeDescriptorString());
	}
}
