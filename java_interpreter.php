<?php

require_once(__DIR__ . '/jre/java_lang.php');
require_once(__DIR__ . '/jre/java_io.php');

\java\lang\System::$out = new \java\io\PrintStream(fopen('php://output', 'wb'));

class JavaInterpreter {
	public $classes = array();
	public $stack = array();

	public function addClass(JavaClass $javaClass) {
		$this->classes[$javaClass->getName()] = $javaClass;
	}
	
	public function callStatic($className, $methodName) {
		/* @var $class JavaClass */
		$class = $this->classes[$className];
		$method = $class->methods[$methodName];
		$this->interpret($method->code);
	}
	
	protected function stackPush($value) {
		$this->stack[] = $value;
		return $value;
	}
	
	protected function getStaticFieldRef(JavaConstantFieldReference $fieldRef) {
		$className = $fieldRef->getClassReference()->getClassName();
		$fieldName = $fieldRef->getNameTypeDescriptor()->getIdentifierNameString();
		$phpClassName = str_replace('/', '\\', $className);
		return $phpClassName::$$fieldName;
	}
	
	protected function callMethodStack(JavaConstantMethodReference $methodRef) {
		$nameTypeDescriptor = $methodRef->getNameTypeDescriptor();
		$methodName = $nameTypeDescriptor->getIdentifierNameString();
		/* @var $type JavaTypeMethod */
		$methodType = $nameTypeDescriptor->getTypeDescriptor();
			
		$paramsCount = count($methodType->params);
		$params = array_splice($this->stack, -$paramsCount);
		$object = array_pop($this->stack);
		
		call_user_func_array(array($object, $methodName), $params);
		//array_slice($this->stack, -$paramsCount);
		//echo "paramsCount: $paramsCount\n";
	}
	
	protected function interpret(JavaCode $code) {
		$f = string_to_stream($code->code); fseek($f, 0);
		//$javaDisassembler = new JavaDisassembler($code); $javaDisassembler->disasm(); 
		while (!feof($f)) {
			$op = fread1($f);
			switch ($op) {
				case JavaOpcodes::OP_GETSTATIC:
					$param0 = fread2_be($f);
					/* @var $fieldRef JavaConstantFieldReference */
					$fieldRef = $code->constantPool->get($param0);
					
					$ref = $this->getStaticFieldRef($fieldRef);
					$this->stackPush($ref);
				break;
				case JavaOpcodes::OP_LDC:
					$param0 = fread1($f);
					/* @var $constant JavaConstant */
					$constant = $code->constantPool->get($param0);
					
					$this->stackPush($constant->getValue());
				break;
				case JavaOpcodes::OP_INVOKEVIRTUAL:
					$param0 = fread2_be($f);
					/* @var $methodRef JavaConstantMethodReference */
					$methodRef = $code->constantPool->get($param0);

					$this->callMethodStack($methodRef);
				break;
				case JavaOpcodes::OP_RETURN:
					return;
				break;
				default: throw(new Exception(sprintf("Don't know how to interpret opcode(0x%02X) : %s", $op, JavaOpcodes::getOpcodeName($op))));
			}
		}
	}
}
