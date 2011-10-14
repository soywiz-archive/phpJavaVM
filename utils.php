<?php

function value_get_byte($value) {
	$v = ((int)$value) & 0xFF;
	if ($v & 0x80) $v = -((~$v) & 0xFF) - 1;
	return $v;
}

function value_get_short($value) {
	$v = ((int)$value) & 0xFFFF;
	if ($v & 0x8000) $v = -((~$v) & 0xFFFF) - 1;
	return $v;
}

function value_get_int($value) {
	$v = ((int)$value) & 0xFFFFFFFF;
	if ($v & 0x80000000) $v = -((~$v) & 0xFFFFFFFF) - 1;
	return $v;
}

function string_to_array($str) {
	$array = array();
	for ($n = 0; $n < strlen($str); $n++) {
		$array[] = ord($str[$n]);
	}
	return $array;
}

// Unsigned freads
function fread1($f) {
	return ord(fread($f, 1));
}

function fread2_be($f) {
	$v = unpack('n', fread($f, 2));
	return $v[1];
}

function fread4_be($f) {
	$v = unpack('N', fread($f, 4));
	return $v[1];
}

// Signed freads
function fread1_s($f) {
	return value_get_byte(fread1($f));
}

function fread2_be_s($f) {
	return value_get_short(fread2_be($f));
}

function fread4_be_s($f) {
	return value_get_int(fread4_be($f));
}


function string_to_stream($str) {
	$f = fopen('php://memory', 'r+b');
	fwrite($f, $str);
	fseek($f, 0);
	return $f;
}

function fread_stream($f, $count) {
	return string_to_stream(fread($f, $count));
}

function fread_until($f, $stopChar) {
	$r = '';
	while (!feof($f)) {
		$c = fread($f, 1);
		if ($c == $stopChar) break;
		$r .= $c;
	}
	
	return $r;
}
