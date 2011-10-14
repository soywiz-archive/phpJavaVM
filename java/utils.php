<?php

function fread4_be($f) {
	$v = unpack('N', fread($f, 4));
	return $v[1];
}

function fread2_be($f) {
	$v = unpack('n', fread($f, 2));
	return $v[1];
}

function fread1($f) {
	return ord(fread($f, 1));
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
