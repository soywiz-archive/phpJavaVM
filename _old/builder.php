<?php

class Builder {
	static function expand($patterns) {
		$out = array();
		foreach ($patterns as $pattern) {
			if (preg_match('@[\\*\\?]@', $pattern)) {
				$out = array_merge($out, glob($pattern));
			} else {
				$out[] = $pattern;
			}
		}
		return $out;
	}
	
	static function compare_times($new, $old) {
		if (!is_array($old)) $old = array($old);
		$old_time = 0; foreach ($old as $c_old) $old_time = max($old_time, @filemtime($c_old));
		if (@filesize($new) === 0) return false;
		return @filemtime($new) == $old_time;
	}
	
	static function update_times($new, $old) {
		if (!is_array($old)) $old = array($old);
		$old_time = 0; foreach ($old as $c_old) $old_time = max($old_time, @filemtime($c_old));
		@touch($new, $old_time, $old_time);
	}

	static function build($info) {
		if (!isset($info['in']       )) $info['in'] = array();
		if (!isset($info['in_extra'] )) $info['in_extra'] = array();
		if (!isset($info['out']      )) $info['out'] = '';
		if (!isset($info['include']  )) $info['include'] = array();
		if (!isset($info['opts']     )) $info['opts'] = '';
		if (!isset($info['link_opts'])) $info['link_opts'] = '';
		if (!isset($info['libs']     )) $info['libs'] = '';
		
		$includes = '';
		
		foreach ($info['include'] as $include) {
			$includes .= ' /I' . escapeshellarg($include);
		}
		
		$objs = array();
		
		$in_extra = static::expand($info['in_extra']);
		
		//print_r($info['in']);
		foreach (static::expand($info['in']) as $file) {
			$file_ext = pathinfo($file, PATHINFO_EXTENSION);

			$file_deps = array_merge(array($file), $in_extra);

			//echo "{$file_ext}\n";
			switch ($file_ext) {
				case 'c': case 'cpp': case 'cc':
					$file_out = preg_replace('@\\.(.*)$@Usi', '.obj', $file);
					$objs[] = $file_out;
					
					if (!static::compare_times($file_out, $file_deps)) {
						echo "{$file} -> {$file_out}\n";
						@unlink($file_out);
						$cmd = "cl /nologo /c{$includes} {$file} /Fo" . escapeshellarg($file_out) . " {$info['opts']}";
						//echo "{$cmd}\n";
						passthru($cmd, $retval);
						static::update_times($file_out, $file_deps);
						if (filesize($file_out) == 0) @unlink($file_out);
					}
				break;
				default:
					$objs[] = $file;
				break;
			}
		}
		
		$in_extra = static::expand($info['in_extra']);
		
		$objs = array_unique($objs);

		$out = $info['out'];
		$out_ext = pathinfo($out, PATHINFO_EXTENSION);

		switch ($out_ext) {
			case 'exe':
			case 'lib':
				if (!static::compare_times($out, array_merge($objs, $in_extra))) {
					$cmd = sprintf(
						'%s /nologo %s %s /OUT:%s %s',
						($out_ext == 'lib') ? 'lib' : 'link',
						implode(' ', array_map('escapeshellarg', $objs)),
						$info['libs'],
						escapeshellarg($out),
						$info['link_opts']
					);
					//echo "{$cmd}\n";
					passthru($cmd, $retval);
					static::update_times($out, array_merge($objs, $in_extra));
					if (filesize($out) == 0) @unlink($out);
				}
			break;
		}
	}
}
