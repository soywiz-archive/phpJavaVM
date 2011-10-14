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
		$method = $class->getMethod($methodName);
		$this->interpret($method->code);
	}
	
	protected function stackPush($value) {
		$this->stack[] = $value;
		return $value;
	}
	
	protected function stackPop() {
		return array_pop($this->stack);
	}
	
	protected function stackPopArray($count) {
		if ($count == 0) return array();
		return array_splice($this->stack, -$count);
	}
	
	public function getPhpClassNameFromJavaClassName($javaClassName) {
		$phpClassName = str_replace('/', '\\', $javaClassName);
		return $phpClassName;
	}
	
	protected function newObject(JavaConstantClassReference $classRef) {
		$className = $classRef->getClassName();
		$phpClassName = $this->getPhpClassNameFromJavaClassName($className);
		return new $phpClassName();
	}
	
	protected function getStaticFieldRef(JavaConstantFieldReference $fieldRef) {
		$className = $fieldRef->getClassReference()->getClassName();
		$fieldName = $fieldRef->getNameTypeDescriptor()->getIdentifierNameString();
		$phpClassName = $this->getPhpClassNameFromJavaClassName($className);
		return $phpClassName::$$fieldName;
	}
	
	protected function callMethodStack(JavaConstantMethodReference $methodRef) {
		$nameTypeDescriptor = $methodRef->getNameTypeDescriptor();
		$methodName = $nameTypeDescriptor->getIdentifierNameString();
		/* @var $type JavaTypeMethod */
		$methodType = $nameTypeDescriptor->getTypeDescriptor();
			
		$paramsCount = count($methodType->params);
		$params = $this->stackPopArray($paramsCount);
		$object = $this->stackPop();
		
		if ($methodName == '<init>') {
			$methodName = '__java_constructor';
		}
		
		$func = array($object, $methodName);
		if (!is_callable($func)) {
			//print_r($func);
			throw(new Exception("Can't call '" . implode('::', $func) . "'"));
		}
		
		$returnValue = call_user_func_array($func, $params);
		
		if (!($methodType->return instanceof JavaTypeIntegralVoid)) {
			$this->stackPush($returnValue);
		}
		//array_slice($this->stack, -$paramsCount);
		//echo "paramsCount: $paramsCount\n";
	}
	
	protected function interpret(JavaCode $code) {
		$locals = array();
		
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
				case JavaOpcodes::OP_BIPUSH:
					$param0 = fread1($f);
					
					$this->stackPush($param0);
				break;
				case JavaOpcodes::OP_LDC:
					$param0 = fread1($f);
					/* @var $constant JavaConstant */
					$constant = $code->constantPool->get($param0);
					
					$this->stackPush($constant->getValue());
				break;
				case JavaOpcodes::OP_ISTORE_0:
				case JavaOpcodes::OP_ISTORE_1:
				case JavaOpcodes::OP_ISTORE_2:
				case JavaOpcodes::OP_ISTORE_3:
					$locals[$op - JavaOpcodes::OP_ISTORE_0] = $this->stackPop();
				break;
				case JavaOpcodes::OP_ILOAD_0:
				case JavaOpcodes::OP_ILOAD_1:
				case JavaOpcodes::OP_ILOAD_2:
				case JavaOpcodes::OP_ILOAD_3:
					$this->stackPush($locals[$op - JavaOpcodes::OP_ILOAD_0]);
				break;
				case JavaOpcodes::OP_INVOKESPECIAL:
				case JavaOpcodes::OP_INVOKEVIRTUAL:
					$param0 = fread2_be($f);
					/* @var $methodRef JavaConstantMethodReference */
					$methodRef = $code->constantPool->get($param0);

					$this->callMethodStack($methodRef);
				break;
				case JavaOpcodes::OP_NEW:
					$param0 = fread2_be($f);
					/* @var $classRef JavaConstantClassReference */
					$classRef = $code->constantPool->get($param0);
					$this->stackPush($this->newObject($classRef));
				break;
				case JavaOpcodes::OP_DUP:
					$v = $this->stackPop();
					$this->stackPush($v);
					$this->stackPush($v);
				break;
				case JavaOpcodes::OP_RETURN:
					return;
				break;
				default: throw(new Exception(sprintf("Don't know how to interpret opcode(0x%02X) : %s", $op, JavaOpcodes::getOpcodeName($op))));
			}
		}
	}
}
