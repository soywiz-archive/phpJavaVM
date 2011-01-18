<?php
	class zip {
		private $stream = null, $dataSize = 0, $fileCount = 0, $ctrlDir = '', $finalized = false;
		
		function __construct($s = null) { $this->stream = isset($s) ? $s : fopen('php://output', 'wb'); }
		function __destruct() { $this->finalize(); }

		function addFile($file, $name) { $this->addStream($f = fopen($file, 'rb'), $name, filemtime($file)); fclose($f); }
		function addStream($s, $name, $time = null) { $data = ''; while (!feof($s)) $data .= fread($s, 10240); $this->addData($data, $name, $time); }

		function addData($data, $name, $time = null) {
			$name = str_replace('\\', '/', $name);
			
			$ta = isset($time)? getdate($time) : getdate();
			$dostime = (($ta['year'] - 1980) << 25) | ($ta['mon'] << 21) | ($ta['mday'] << 16) | ($ta['hours'] << 11) | ($ta['minutes'] << 5) | ($ta['seconds'] >> 1);
			
			$crc = crc32($data); $zdata = gzdeflate($data);
			
			fwrite($this->stream, $h = pack('nnvvvVVVVvv', 0x504B, 0x0304, 0x14, 0, 8, $dostime, $crc, strlen($zdata), strlen($data), strlen($name), 0) . $name);
			fwrite($this->stream, $zdata);
			$this->ctrlDir .= pack('nnvvvvVVVVvvvvvVV', 0x504B, 0x0102, 0, 0x14, 0, 8, $dostime, $crc, strlen($zdata), strlen($data), strlen($name), 0, 0, 0, 0, 32, $this->dataSize) . $name;
			
			$this->dataSize += strlen($h) + strlen($zdata);
			$this->fileCount++;
		}
		
		function finalize() {
			if ($this->finalized) return;
			fwrite($this->stream, $this->ctrlDir);
			fwrite($this->stream, pack('nnVvvVVv', 0x504B, 0x0506, 0, $this->fileCount, $this->fileCount, strlen($this->ctrlDir), $this->dataSize, 0));
			$this->finalized = true;
		}

		static function httpStartSend($file) {
			header('Content-Disposition: attachment; filename="' . rawurlencode($file) . '"');
			header('Pragma: no-cache');
			header('Content-Type: octet/stream');
		}
	}
?>