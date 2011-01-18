<?php
//RegisterFunction("MessageBox", "issi", "user32.dll", "MessageBoxA", CALL_TYPE_WINDOWS);

function parse_comma_expression($comma) {
	$tokens = array_slice(token_get_all("<?php {$comma}"), 1);
	$expect_value = true;
	$values = array();
	foreach ($tokens as $token) {
		$value = is_array($token) ? $token[1] : $token;
		if (trim($value) == '') continue;
		if ($expect_value) {
			$values[] = $value;
		} else {
			if ($value != ',') throw(new Exception("Invalid comma list"));
		}
		$expect_value = !$expect_value;
	}
	return $values;
}

function parse_comma_expression_expand($comma, $lookup = array()) {
	$list = parse_comma_expression($comma);
	return array_map(function($item) use (&$lookup) {
		if (substr($item, 0, 1) == '"' || substr($item, 0, 1) == "'") {
			return stripslashes(substr($item, 1, -1));
		} else if (is_numeric($item)) {
			return $item;
		} else {
			return $lookup[$item];
		}
	}, $list);
}

//if (!defined('DYNACALL_PATH')) define('DYNACALL_PATH', '.');

//echo DYNACALL_PATH;

foreach (glob(sprintf('%s/*.dynalib', DYNACALL_PATH)) as $file) {
	$dll = '';
	$calltype = CALL_TYPE_C;
	foreach (file($file) as $line) {
		$line = trim($line);
		if (preg_match('/^@(\w+)(?:\s+(.*))?$/', $line, $matches)) {
			list(, $type, $params) = $matches;
			switch ($type) {
				case 'dll':
					$dll = trim($params);
				break;
				case 'calltype':
					$items = parse_comma_expression_expand($params, array(
						'CALL_TYPE_WINDOWS' => CALL_TYPE_WINDOWS,
						'CALL_TYPE_C'       => CALL_TYPE_C,
					));
					$calltype = $items[0];
				break;
				case 'function':
					$items = parse_comma_expression_expand($params);
					//print_r($items);
					RegisterFunction($items[0], $items[1], sprintf('%s.%s', $dll, PHP_SHLIB_SUFFIX), $items[2], $calltype);
				break;
			}
			//print_r($matches);
		}
	}
}
