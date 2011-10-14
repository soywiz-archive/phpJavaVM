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
			$c = fread1($f);
			printf("%s(0x%02X)\n", JavaOpcodes::getOpcodeName($c), $c);
			$opcode = &JavaOpcodes::$OPCODES[$c];
			if (!isset($opcode)) {
				die(sprintf("Can't load opcode 0x%02X : '%s'\n", $c, JavaOpcodes::getOpcodeName($c)));
			}
			list($paramsString) = JavaOpcodes::$OPCODES[$c];
			for ($n = 0; $n < strlen($paramsString); $n++) {
				$paramType = $paramsString[$n];
				switch ($paramType) {
					case '1':
						$param = fread1($f);
						printf("  PARAM: %d (%s)\n", $param, $this->code->constantPool->get($param)->getNormalizedString());
					break;
					case '2':
						$param = fread2_be($f);
						printf("  PARAM: %d (%s)\n", $param, $this->code->constantPool->get($param)->getNormalizedString());
					break;
					default:
						throw(new Exception("Unknown paramType:'{$paramType}'"));
					break;
				}
			}
		}
	}
}
