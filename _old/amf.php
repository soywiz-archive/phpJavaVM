<?php
class AMF {
	static public function decode($f) {
		// Converts string into a stream. PHP >= 5.1
		if (is_string($f)) { $_f = fopen('php://memory', 'wb'); fwrite($_f, $f); fseek($_f, 0); $f = $_f; }

		// Version.
		$version = self::u16($f); assert('in_array($version, array(0, 3))');
		
		$refs = array('o' => array(), 's' => array(), 'd' => array()); // objects, strings, definitions

		// Header.
		$header_count = self::u16($f);
		assert('$header_count == 0'); // Header not processed yet.
		for ($n = 0; $n < $header_count; $n++) {
		}

		// Header.
		$body_count = self::u16($f);
		for ($n = 0; $n < $body_count; $n++) {
			$target   = self::utf8($f);
			$response = self::utf8($f);
			$length   = self::u32($f);
			self::decodeType($refs, $f);
		}
		
		echo "\n$version: $header_count, $body_count\n\n";
	}
	
	static public function encode($object) {
		throw(new Exception("Not implemented yet"));
	}

	static public function u8  ($f, $v = null) { if ($v === null) return ord(fread($f, 1)); else { fwrite($f, chr($v)); return $v; } }
	static public function u16 ($f, $v = null) { if ($v === null) return (($v = unpack('n', fread($f, 2))) === null) ? null : $v[1]; else { fwrite($f, pack('n', $v)); return $v; } }
	static public function u32 ($f, $v = null) { if ($v === null) return (($v = unpack('N', fread($f, 4))) === null) ? null : $v[1]; else { fwrite($f, pack('N', $v)); return $v; } }
	static public function dbl ($f, $v = null) { if ($v === null) return (($v = unpack('d', strrev(fread($f, 8)))) === null) ? null : $v[1]; else { fwrite($f, strrev(pack('d', $v))); return $v; }  }
	static public function utf8($f, $v = null) { if ($v === null) return fread($f, self::u16($f)); else { self::u16($f, strlen($v)); fwrite($f, $v); return $v; } }
	static public function u29 ($f, $v = null) {
		if ($v === null) {
			$v = 0; $shift = 0; $cur = 4;
			
			do {
				$cv = ord(fread($f, 1));
				$v |= ($cv & (($cur > 0) ? 0x7F : 0xFF)) << $shift;
				$shift += 7;
			} while (($cv & 0x80) && ($cur++ == 0));
			
			return $v;
		} else {
			throw(new Exception("Not implemented u29 writting"));
		}
	}

	static protected function decodeType(&$refs, $f, $type = -1) {
		if ($type == -1) $type = self::u8($f);
		$ret = null;
		printf("type: 0x%02X\n", $type);
		switch ($type) {
			case 0x00: break; // number
			case 0x01: break; // boolean
			case 0x02: break; // string
			case 0x03: break; // object Object
			//case 0x04: break; // 
			case 0x05: break; // null
			case 0x06: break; // undefined
			case 0x07: break; // Circular references are returned here
			case 0x08: break; // mixed array with numeric and string keys
			//case 0x09: break; //
			case 0x0A: // array
				$ret = array(); $refs['o'][] = &$ret;
				$len = self::u32($f);
				while ($len--) $ret[] = self::decodeType($refs, $f);
			break;
			case 0x0B: break; // date
			case 0x0C: break; // string, strlen(string) > 2^16
			case 0x0D: break;  // mainly internal AS objects
			//case 0x0E: break;  //
			case 0x0F: break;  // XML
			case 0x10: break;  // Custom Class
			case 0x11: // AMF3-specific
				$ret = self::decodeTypeAmf3($refs, $f);
			break; 
			default: throw(new Exception(sprintf("Invalid amf type 0x%02X", $type)));
		}
		return $ret;
	}
	
	const AMF3_UNDEFINED  = 0x00;
	const AMF3_NULL       = 0x01;
	const AMF3_FALSE      = 0x02;
	const AMF3_TRUE       = 0x03;
	const AMF3_INTEGER    = 0x04;
	const AMF3_DOUBLE     = 0x05;
	const AMF3_STRING     = 0x06;
	const AMF3_XML_DOC    = 0x07;
	const AMF3_DATE       = 0x08;
	const AMF3_ARRAY      = 0x09;
	const AMF3_OBJECT     = 0x0A;
	const AMF3_XML        = 0x0B;
	const AMF3_BYTE_ARRAY = 0x0C;
	
	static protected function decodeTypeAmf3(&$refs, $f, $type = -1) {
		if ($type == -1) $type = self::u8($f);
		printf("amf_type: 0x%02X\n", $type);
		$ret = null;
		switch ($type) {
			//case self::AMF3_UNDEFINED: break;
			//case self::AMF3_NULL: break;
			//case self::AMF3_FALSE: break;
			//case self::AMF3_TRUE: break;
			//case self::AMF3_INTEGER: break;
			//case self::AMF3_DOUBLE: break;
			case self::AMF3_STRING:
				echo fread($f, 0x20); exit;
				$info = self::u29($f);
				$inline_str = !!($info & 1);
				
				// Reference to a previously defined string.
				if (!$inline_str) return $refs['s'][$info >> 1];

				// Inline string.
				$ret = fread($f, $info >> 1);
				$refs['s'][] = $ret;
			break;
			//case self::AMF3_XML_DOC: break;
			//case self::AMF3_DATE: break;
			//case self::AMF3_ARRAY: break;
			case self::AMF3_OBJECT:
				//die(fread($f, 0x40));
				$info = self::u29($f);
				$inline_obj = !!($info & 1);
				$inline_def = !!($info & 2);

				// Reference to a previously defined object.
				if (!$inline_obj) return $refs['o'][$info >> 1];

				// Inline object definition.
				if ($inline_def) {
					$ident = self::decodeTypeAmf3($refs, $f, self::AMF3_STRING);
				}
				var_dump($inline_def);
				exit;
			break;
			//case self::AMF3_XML: break;
			//case self::AMF3_BYTE_ARRAY: break;
			default: throw(new Exception(sprintf("Invalid amf type 0x%02X", $type)));
		}
		return $ret;
	}
}
