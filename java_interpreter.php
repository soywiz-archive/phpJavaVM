<?php

spl_autoload_register(function($className) {
	if (substr($className, 0, 5) == 'java\\') {
		//str_replace('\\', '_');
		$pos = strrpos($className, '\\');
		$package = substr($className, 0, $pos);
		$class = substr($className, $pos + 1);
		require_once(__DIR__ . '/jre/' . basename(str_replace('\\', '_', $package)) . '.php');
	}
});

\java\lang\System::$out = new \java\io\PrintStream(fopen('php://output', 'wb'));

function IUSHR($l, $r) {
	//printf("%032b, %d\n", $l, $r);
	$v = ($l >> $r) & ((1 << (32 - $r)) - 1);
	//printf("%032b, %d\n", $v, $r);
	//exit;
	return $v;
}

class JavaInterpreter {
	public $classes = array();
	public $stack = array();
	public $autoDisasm = false;
	public $autoTrace  = false;

	public function addClass(JavaClass $javaClass) {
		$this->classes[$javaClass->getName()] = $javaClass;
	}
	
	public function callStatic($className, $methodName, $params = array()) {
		/* @var $class JavaClass */
		$class = $this->classes[$className];
		$method = $class->getMethod($methodName);
		return $this->interpret($method->code, $params);
	}
	
	protected function stackPush($value) {
		$this->stack[] = $value;
		return $value;
	}
	
	protected function stackPop() {
		return array_pop($this->stack);
	}
	
	protected function stackDump() {
		printf("$$ STACK[%s]\n", @json_encode($this->stack));
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
	
	protected function _callMethod($invokeStatic, $func, $params) {
		// Static call.
		if (is_string($func[0])) {
			if (isset($this->classes[$func[0]])) {
				return $this->callStatic($func[0], $func[1], $params);
			}
		}

		if (!$invokeStatic) {
			if (is_int($func[0])) {
				$func[0] = new \java\lang\Integer($func[0]);
			} else if (is_string($func[0])) {
				$func[0] = new \java\lang\String($func[0]);
			}
		}
		
		if (!is_callable($func)) {
			// print is a reserved keyword on PHP.
			if ($func[1] == 'print') {
				$func[1] = '_print';
			}
		}
		
		if (!is_callable($func)) {
			//print_r($func);
			if (!is_string($func[0])) {
				$func[0] = 'INSTANCEOF(' . get_class($func[0]) . ')';
			}
			$func_name = implode('::', $func);
			
			throw(new Exception("Can't call '" . $func_name . "'"));
		}
		
		$returnValue = call_user_func_array($func, $params);
		return $returnValue;
	}
	
	protected function callMethodStack(JavaConstantMethodReference $methodRef, $invokeStatic) {
		$nameTypeDescriptor = $methodRef->getNameTypeDescriptor();
		$methodName = $nameTypeDescriptor->getIdentifierNameString();
		/* @var $type JavaTypeMethod */
		$methodType = $nameTypeDescriptor->getTypeDescriptor();
			
		$paramsCount = count($methodType->params);
		$params = $this->stackPopArray($paramsCount);
		
		/* @var $paramType JavaType */
		foreach ($methodType->params as $k => $paramType) {
			if ($paramType instanceof JavaTypeIntegralChar) {
				$params[$k] = chr($params[$k]);
			} else if ($paramType instanceof JavaTypeIntegralBool) {
				$params[$k] = (bool)($params[$k]);
			}
		}
		
		if (!$invokeStatic) {
			$object = $this->stackPop();
		}
		
		if ($methodName == '<init>') {
			$methodName = '__java_constructor';
		}
		
		if (!$invokeStatic) {
			$func = array($object, $methodName);
		} else {
			$func = array($this->getPhpClassNameFromJavaClassName($methodRef->getClassReference()->getClassName()), $methodName);
		}

		$returnValue = $this->_callMethod($invokeStatic, $func, $params);
		
		if (!($methodType->return instanceof JavaTypeIntegralVoid)) {
			$this->stackPush($returnValue);
		}
		//array_slice($this->stack, -$paramsCount);
		//echo "paramsCount: $paramsCount\n";
	}
	
	protected function interpret(JavaCode $code, $params = array()) {
		if ($this->autoDisasm) {
			$code->disasm();
			//$javaClass->getMethod($methodName)->disasm();
		}
		
		$locals = array();
		foreach ($params as $k => $param) $locals[$k] = $param;
		
		$trace = $this->autoTrace;
		
		$f = string_to_stream($code->code); fseek($f, 0);
		//$javaDisassembler = new JavaDisassembler($code); $javaDisassembler->disasm(); 
		while (!feof($f)) {
			$instruction_offset = ftell($f);
			$op = fread1($f);
			if ($trace) {
				printf("-------------------------------------------------------\n");
				printf("::[%08X] %s(0x%02X)\n", $instruction_offset, JavaOpcodes::getOpcodeName($op), $op);
				$this->stackDump();
			}
			switch ($op) {
				case JavaOpcodes::OP_GETSTATIC:
					$param0 = fread2_be($f);
					/* @var $fieldRef JavaConstantFieldReference */
					$fieldRef = $code->constantPool->get($param0);
					
					$ref = $this->getStaticFieldRef($fieldRef);
					$this->stackPush($ref);
				break;
				case JavaOpcodes::OP_BIPUSH:
					$param0 = fread1_S($f);
					
					$this->stackPush($param0);
				break;
				case JavaOpcodes::OP_SIPUSH:
					$param0 = fread2_be_s($f);
					
					$this->stackPush($param0);
				break;
				case JavaOpcodes::OP_ICONST_M1:
				case JavaOpcodes::OP_ICONST_0:
				case JavaOpcodes::OP_ICONST_1:
				case JavaOpcodes::OP_ICONST_2:
				case JavaOpcodes::OP_ICONST_3:
				case JavaOpcodes::OP_ICONST_4:
				case JavaOpcodes::OP_ICONST_5:
					$this->stackPush($op - JavaOpcodes::OP_ICONST_0);
				break;
				case JavaOpcodes::OP_LDC_W:
					$param0 = fread2_be($f);
					/* @var $constant JavaConstant */
					$constant = $code->constantPool->get($param0);
					
					$this->stackPush($constant->getValue());
				break;
				case JavaOpcodes::OP_LDC:
					$param0 = fread1($f);
					/* @var $constant JavaConstant */
					$constant = $code->constantPool->get($param0);
					
					$this->stackPush($constant->getValue());
				break;
				case JavaOpcodes::OP_ASTORE:
				case JavaOpcodes::OP_ISTORE:
					$index = fread1($f);
					$locals[$index] = $this->stackPop();
				break;
				case JavaOpcodes::OP_ISTORE_0:
				case JavaOpcodes::OP_ISTORE_1:
				case JavaOpcodes::OP_ISTORE_2:
				case JavaOpcodes::OP_ISTORE_3:
					$locals[$op - JavaOpcodes::OP_ISTORE_0] = $this->stackPop();
				break;
				case JavaOpcodes::OP_ASTORE_0:
				case JavaOpcodes::OP_ASTORE_1:
				case JavaOpcodes::OP_ASTORE_2:
				case JavaOpcodes::OP_ASTORE_3:
					$locals[$op - JavaOpcodes::OP_ASTORE_0] = $this->stackPop();
				break;
				case JavaOpcodes::OP_ALOAD:
				case JavaOpcodes::OP_ILOAD:
					$index = fread1($f);
					$this->stackPush($locals[$index]);
				break;
				// @OTOD. BUG. It is a reference not the value itself!
				case JavaOpcodes::OP_ALOAD_0:
				case JavaOpcodes::OP_ALOAD_1:
				case JavaOpcodes::OP_ALOAD_2:
				case JavaOpcodes::OP_ALOAD_3:
					$this->stackPush($locals[$op - JavaOpcodes::OP_ALOAD_0]);
				break;
				case JavaOpcodes::OP_ILOAD_0:
				case JavaOpcodes::OP_ILOAD_1:
				case JavaOpcodes::OP_ILOAD_2:
				case JavaOpcodes::OP_ILOAD_3:
					$this->stackPush($locals[$op - JavaOpcodes::OP_ILOAD_0]);
				break;
				case JavaOpcodes::OP_INVOKESPECIAL:
				case JavaOpcodes::OP_INVOKEVIRTUAL:
				case JavaOpcodes::OP_INVOKESTATIC:
					$param0 = fread2_be($f);
					/* @var $methodRef JavaConstantMethodReference */
					$methodRef = $code->constantPool->get($param0);

					$this->callMethodStack($methodRef, $invokeStatic = ($op == JavaOpcodes::OP_INVOKESTATIC));
				break;
				case JavaOpcodes::OP_INVOKEINTERFACE:
					$param0 = fread2_be($f);
					$param1 = fread1_s($f);
					$param2 = fread1_s($f);
					/* @var $methodRef JavaConstantMethodReference */
					$methodRef = $code->constantPool->get($param0);
					
					$this->callMethodStack($methodRef, $invokeStatic = false);
					
					break;
				case JavaOpcodes::OP_GOTO:
					$relativeAddress = fread2_be_s($f);
					fseek($f, $instruction_offset + $relativeAddress);
				break;
				case JavaOpcodes::OP_IF_ICMPEQ:
				case JavaOpcodes::OP_IF_ICMPNE:
				case JavaOpcodes::OP_IF_ICMPLE:
				case JavaOpcodes::OP_IF_ICMPLT:
				case JavaOpcodes::OP_IF_ICMPGE:
				case JavaOpcodes::OP_IF_ICMPGT:
					$relativeAddress = fread2_be_s($f);
					$valueRight = $this->stackPop();
					$valueLeft  = $this->stackPop();
					
					$result = NULL;
					
					switch ($op) {
						case JavaOpcodes::OP_IF_ICMPEQ: $result = ($valueLeft == $valueRight); break;
						case JavaOpcodes::OP_IF_ICMPNE: $result = ($valueLeft != $valueRight); break;
						case JavaOpcodes::OP_IF_ICMPLE: $result = ($valueLeft <= $valueRight); break;
						case JavaOpcodes::OP_IF_ICMPLT: $result = ($valueLeft < $valueRight); break;
						case JavaOpcodes::OP_IF_ICMPGE: $result = ($valueLeft >= $valueRight); break;
						case JavaOpcodes::OP_IF_ICMPGT: $result = ($valueLeft > $valueRight); break;
					}
					
					if ($result === NULL) throw(new Exception("Unexpected !!!"));
					
					if ($result) {
						fseek($f, $instruction_offset + $relativeAddress);
					}
					//echo "$valueLeft; $valueRight\n";
				break;
				case JavaOpcodes::OP_IFEQ:
				case JavaOpcodes::OP_IFNE:
				case JavaOpcodes::OP_IFLE:
				case JavaOpcodes::OP_IFLT:
				case JavaOpcodes::OP_IFGE:
				case JavaOpcodes::OP_IFGT:
					$relativeAddress = fread2_be_s($f);
					$valueRight = 0;
					$valueLeft  = $this->stackPop();
					$result = NULL;
					
					switch ($op) {
						case JavaOpcodes::OP_IFEQ: $result = ($valueLeft == $valueRight); break;
						case JavaOpcodes::OP_IFNE: $result = ($valueLeft != $valueRight); break;
						case JavaOpcodes::OP_IFLE: $result = ($valueLeft <= $valueRight); break;
						case JavaOpcodes::OP_IFLT: $result = ($valueLeft <  $valueRight); break;
						case JavaOpcodes::OP_IFGE: $result = ($valueLeft >= $valueRight); break;
						case JavaOpcodes::OP_IFGT: $result = ($valueLeft >  $valueRight); break;
					}
					
					if ($result === NULL) throw(new Exception("Unexpected !!!"));
					
					if ($result) {
						fseek($f, $instruction_offset + $relativeAddress);
					}
					//echo "$valueLeft; $valueRight\n";
				break;
				case JavaOpcodes::OP_IINC:
					$param0 = fread1($f);
					$param1 = fread1_s($f);
					$locals[$param0] += $param1;
				break;
				case JavaOpcodes::OP_IADD:
				case JavaOpcodes::OP_ISUB:
				case JavaOpcodes::OP_IMUL:
				case JavaOpcodes::OP_IDIV:
				case JavaOpcodes::OP_IXOR:
				case JavaOpcodes::OP_IOR:
				case JavaOpcodes::OP_IAND:
				case JavaOpcodes::OP_IUSHR:
				case JavaOpcodes::OP_ISHR:
				{	
					$valueRight = $this->stackPop();
					$valueLeft  = $this->stackPop();
					$result = NULL;
					switch ($op) {
						case JavaOpcodes::OP_IADD: $result = (int)($valueLeft + $valueRight); break;
						case JavaOpcodes::OP_ISUB: $result = (int)($valueLeft - $valueRight); break;
						case JavaOpcodes::OP_IMUL: $result = (int)($valueLeft * $valueRight); break;
						case JavaOpcodes::OP_IDIV: $result = (int)($valueLeft / $valueRight); break;
						case JavaOpcodes::OP_IXOR: $result = (int)($valueLeft ^ $valueRight); break;
						case JavaOpcodes::OP_IOR: $result = (int)($valueLeft | $valueRight); break;
						case JavaOpcodes::OP_IAND: $result = (int)($valueLeft & $valueRight); break;
						case JavaOpcodes::OP_IUSHR: $result = (int)(\IUSHR($valueLeft, $valueRight)); break;
						case JavaOpcodes::OP_ISHR: $result = (int)($valueLeft >> $valueRight); break;
					}
					if ($result === NULL) throw(new Exception("Unexpected !!!"));
					$this->stackPush($result);
				}
				break;
				case JavaOpcodes::OP_I2B:
					$this->stackPush(value_get_byte($this->stackPop()));
				break;
				case JavaOpcodes::OP_NEW:
					$param0 = fread2_be($f);
					/* @var $classRef JavaConstantClassReference */
					$classRef = $code->constantPool->get($param0);
					$this->stackPush($this->newObject($classRef));
				break;
				case JavaOpcodes::OP_ANEWARRAY:
					$classIndex = fread2_be($f);
					
					$array = new ArrayObject();
					$count = $this->stackPop();
					for ($n = 0; $n < $count; $n++) $array[] = null;
					$this->stackPush($array);
				break;
				case JavaOpcodes::OP_NEWARRAY:
					$type = fread1($f);
					
					$array = new ArrayObject();
					$count = $this->stackPop();
					for ($n = 0; $n < $count; $n++) $array[] = null;
					$this->stackPush($array);
				break;
				case JavaOpcodes::OP_ARRAYLENGTH:
					$v = $this->stackPop();
					//echo count($v);
					//$this->stackPush($v);
					$this->stackPush(count($v));
				break;
				case JavaOpcodes::OP_AASTORE:
				case JavaOpcodes::OP_BASTORE:
				case JavaOpcodes::OP_IASTORE:
					$value = $this->stackPop();
					$index = $this->stackPop();
					$array = $this->stackPop();
					$array[$index] = $value;
					if ($trace) {
						echo "VALUE:"; var_dump($value);
						echo "INDEX:"; var_dump($index);
						echo "ARRAY:"; var_dump($array);
					}
				break;
				case JavaOpcodes::OP_AALOAD:
				case JavaOpcodes::OP_BALOAD:
				case JavaOpcodes::OP_IALOAD:
					$index = $this->stackPop();
					$array = $this->stackPop();
					if ($trace) {
						echo "INDEX:"; var_dump($index);
						echo "ARRAY:"; var_dump($array);
					}
					$this->stackPush($array[$index]);
				break;
				case JavaOpcodes::OP_POP:
					$this->stackPop();
				break;
				case JavaOpcodes::OP_CHECKCAST:
					$classIndex = fread2_be($f);
					$value = $this->stackPop();
					// @TODO: Check the type.
					$this->stackPush($value);
				break;
				case JavaOpcodes::OP_DUP:
					$v = $this->stackPop();
					$this->stackPush($v);
					$this->stackPush($v);
				break;
				case JavaOpcodes::OP_RETURN:
					return;
				break;
				case JavaOpcodes::OP_ARETURN:
				case JavaOpcodes::OP_IRETURN:
					return $this->stackPop();
				break;
				default: throw(new Exception(sprintf("Don't know how to interpret opcode(0x%02X) : %s", $op, JavaOpcodes::getOpcodeName($op))));
			}
		}
	}
}
