<?php

class JavaDisassembler {
	public $constantPool;
	public $code;
	public $f;

	public function __construct(JavaConstantPool $constantPool, $code) {
		$this->constantPool = $constantPool;
		$this->code = $code;
		$this->f = string_to_stream($code);
	}

	/*
		Code_attribute {
			u2 attribute_name_index;
			u4 attribute_length;
			u2 max_stack;
			u2 max_locals;
			u4 code_length;
			u1 code[code_length];
			u2 exception_table_length;
			{    	u2 start_pc;
					u2 end_pc;
					u2  handler_pc;
					u2  catch_type;
			}	exception_table[exception_table_length];
			u2 attributes_count;
			attribute_info attributes[attributes_count];
		}
	*/
	
	public function getOpcodeName($opcodeId) {
		static $mapIdName = array();
		if (empty($mapIdName)) {
			$class = new ReflectionClass('JavaOpcodes');
			foreach ($class->getConstants() as $constantName => $constantValue) {
				$mapIdName[$constantValue] = $constantName;
			}
		}
		return substr($mapIdName[$opcodeId], 3);
	}
	
	public function disasm() {
		$f = $this->f;

		$max_stacks  = fread2_be($f);
		$max_locals  = fread2_be($f);
		$code_length = fread4_be($f);
		$code        = fread($f, $code_length);
		$fcode       = string_to_stream($code);
	
		echo "-----------------------------------------------------------------------\n";
		printf("%s\n", ($v = unpack('H*', $code)) ? $v[1] : '');
		while (!feof($fcode)) {
			$c = fread1($fcode);
			printf("%s(0x%02X)\n", $this->getOpcodeName($c), $c);
			$opcode = &JavaOpcodes::$OPCODES[$c];
			if (!isset($opcode)) {
				die(sprintf("Can't load opcode 0x%02X : '%s'\n", $c, $this->getOpcodeName($c)));
			}
			list($paramsString) = JavaOpcodes::$OPCODES[$c];
			for ($n = 0; $n < strlen($paramsString); $n++) {
				$paramType = $paramsString[$n];
				switch ($paramType) {
					case '1':
						$param = fread1($fcode);
						printf("  PARAM: %d (%s)\n", $param, $this->constantPool->get($param)->getNormalizedString());
					break;
					case '2':
						$param = fread2_be($fcode);
						printf("  PARAM: %d (%s)\n", $param, $this->constantPool->get($param)->getNormalizedString());
					break;
					default:
						throw(new Exception("Unknown paramType:'{$paramType}'"));
					break;
				}
			}
		}
	}
}
