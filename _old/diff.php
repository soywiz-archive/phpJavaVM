<?php
function diff($old, $new) {
	static $self = __FUNCTION__;
	$maxlen = 0;
	foreach($old as $oindex => $ovalue){
		$nkeys = array_keys($new, $ovalue);
		foreach($nkeys as $nindex){
			$matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ? $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
			if($matrix[$oindex][$nindex] > $maxlen){
				$maxlen = $matrix[$oindex][$nindex];
				$omax = $oindex + 1 - $maxlen;
				$nmax = $nindex + 1 - $maxlen;
			}
		}       
	}
	if ($maxlen == 0) {
		return array(array($old, $new));
	}
	return array_merge(
		$self(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
		array_slice($new, $nmax, $maxlen),
		$self(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen))
	);
}

function count_blocks(&$v) {
	$c = 0;
	foreach ($v as $a) if (is_array($a)) $c += max(count($a[0]), count($a[1]));
	return $c;
}

function diff_text_flush(&$buf, &$r) {
	foreach ($buf as $z) { $i = $z[0]; if (strlen($i)) $r .= "<sub>{$i}</sub>\n"; }
	foreach ($buf as $z) { $i = $z[1]; if (strlen($i)) $r .= "<add>{$i}</add>\n"; }
	$buf = array();
}

function diff_text($t1, $t2, $threshold = 0.5) {
	$r = '';
	$buf = array();
	$flush = 'diff_text_flush';
	foreach (diff(array_map('trim', explode("\n", trim($t1))), array_map('trim', explode("\n", trim($t2)))) as $line) {
		if (is_array($line)) {
			list($line_o, $line_i) = $line;
			$lines = max(count($line_o), count($line_i));
			for ($n = 0; $n < $lines; $n++) {
				$cline_o = &$line_o[$n];
				$cline_i = &$line_i[$n];
				if (isset($cline_o, $cline_i)) {
					$diff = diff($b1 = explode(" ", $cline_o), explode(" ", $cline_i));
					//if (count_blocks($diff) < count($b1) / 2) {
					//$r .= sprintf("%d, %d\n", count_blocks($diff), count($b1));
					if (count_blocks($diff) >= max(1, count($b1) * $threshold)) {
						@$buf[] = array($cline_o, $cline_i);
					} else {
						$flush($buf, $r);
						$pos = 0;
						foreach ($diff as $word) {
							if (is_array($word)) {
								if (count($word[0]) || count($word[1])) {
									if ($pos++ > 0) $r .= ' ';
									if (count($word[0])) $r .= '<sub>' . implode(' ', $word[0]) . '</sub>';
									if (count($word[1])) $r .= '<add>' . implode(' ', $word[1]) . '</add>';
								}
							} else {
								if ($pos++ > 0) $r .= ' ';
								$r .= $word;
							}
						}
						$r .= "\n";
					}
				} else {
					@$buf[] = array($cline_o, $cline_i);
				}
			}
		} else {
			$flush($buf, $r);
			$r .= "$line\n";
		}
	}
	$flush($buf, $r);
	return $r;
}
