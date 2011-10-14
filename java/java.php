<?php

require_once(__DIR__ . '/utils.php');
require_once(__DIR__ . '/java_opcodes.php');
require_once(__DIR__ . '/java_disassembler.php');
require_once(__DIR__ . '/java_type.php');
require_once(__DIR__ . '/java_constant.php');

require_once(__DIR__ . '/jre/java_lang.php');
require_once(__DIR__ . '/jre/java_io.php');

\java\lang\System::$out = new \java\io\PrintStream(fopen('php://output', 'wb'));

class JavaInterpreter {
}

$javaClass = new JavaClassFile();
$javaClass->readClassFile(fopen('Test.class', 'rb'));