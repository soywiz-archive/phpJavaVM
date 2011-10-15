<?php

require_once(__DIR__ . '/utils.php');
require_once(__DIR__ . '/java_opcodes.php');
require_once(__DIR__ . '/java_disassembler.php');
require_once(__DIR__ . '/java_type.php');
require_once(__DIR__ . '/java_constant.php');
require_once(__DIR__ . '/java_class.php');

require_once(__DIR__ . '/java_interpreter.php');

$javaInterpreter = new JavaInterpreter();
$javaInterpreter->addClassPath(__DIR__ . '/Sample/bin');
//$javaInterpreter->addClass($javaClass);

$params = array();

$methodName = 'main';
//$methodName = 'test15_bool_logic';

//$javaInterpreter->autoDisasm = true;
//$javaInterpreter->autoTrace  = true;

$javaInterpreter->callStatic('Test', $methodName, $params);
