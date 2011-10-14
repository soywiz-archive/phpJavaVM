<?php

class JavaDisassembler {
	/**
	 * @var JavaCode
	 */
	public $code;

	public function __construct(JavaCode $code) {
		$this->code = $code;
	}

	public function disasm() {
		$f = $this->code->fcode;

		echo "-----------------------------------------------------------------------\n";
		while (!feof($f)) {
			$instruction_offset = ftell($f);
			$op = fread1($f);
			printf("[%08X]%s(0x%02X)\n", $instruction_offset, JavaOpcodes::getOpcodeName($op), $op);
			$opcode = &JavaOpcodes::$OPCODES[$op];
			if (!isset($opcode)) {
				die(sprintf("Can't load opcode 0x%02X : '%s'\n", $op, JavaOpcodes::getOpcodeName($op)));
			}
			list($paramsString) = JavaOpcodes::$OPCODES[$op];
			for ($n = 0; $n < strlen($paramsString); $n++) {
				$paramType = $paramsString[$n];
				switch ($paramType) {
					case 'b':
						$param = fread1_s($f);
						printf("             PARAM: %d\n", $param);
					break;
					case 'w':
						$param = fread2_be_s($f);
						printf("             PARAM: %d\n", $param);
					break;
					case '1':
						$param = fread1($f);
						printf("             PARAM: %d (%s)\n", $param, $this->code->constantPool->get($param)->getNormalizedString());
					break;
					case '2':
						$param = fread2_be($f);
						printf("             PARAM: %d (%s)\n", $param, $this->code->constantPool->get($param)->getNormalizedString());
					break;
					default:
						throw(new Exception("Unknown paramType:'{$paramType}'"));
					break;
				}
			}
		}
	}
}
