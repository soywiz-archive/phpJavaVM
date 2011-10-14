<?php

require_once(__DIR__ . '/utils.php');
require_once(__DIR__ . '/java_opcodes.php');
require_once(__DIR__ . '/java_disassembler.php');
require_once(__DIR__ . '/java_type.php');
require_once(__DIR__ . '/java_constant.php');
require_once(__DIR__ . '/java_class.php');

require_once(__DIR__ . '/java_interpreter.php');

$javaClass = new JavaClass();
$javaClass->readClassFile(fopen('Sample/bin/Test.class', 'rb'));

$javaInterpreter = new JavaInterpreter();
$javaInterpreter->addClass($javaClass);

$methodName = 'test8';
$methodName = 'main';

//$javaClass->getMethod($methodName)->disasm();

$javaInterpreter->callStatic('Test', $methodName);
