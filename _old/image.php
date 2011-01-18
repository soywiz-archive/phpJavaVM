<?php
class Font {
	public $file, $size, $angle;

	function __construct($file, $size, $angle = 0) {
		if (!is_file($file)) {
			if ((PHP_OS == 'WINNT')) {
				$file = strtolower($file);
				$fonts = array();
			
				$reg_file = 'temp.font.reg.ini';
				`reg EXPORT "HKEY_LOCAL_MACHINE\SOFTWARE\Microsoft\Windows NT\CurrentVersion\Fonts" {$reg_file} /y`;
				$reg = file_get_contents($reg_file);
				$reg = mb_convert_encoding($reg, 'utf-8', 'unicode');
				$fonts_folder = getenv('SystemRoot') . '/Fonts';
				unlink($reg_file);

				foreach (explode("\n", $reg) as $line) {
					$line = trim($line);
					if (preg_match('@^"(.*)"="(.*)"$@Umsi', $line, $matches)) {
						$font_name = $matches[1];
						$font_file = $matches[2];
						$font_name = strtolower(trim(preg_replace('@\\(.*\\)$@', '', $font_name)));
						$fonts[$font_name] = $font_file;
					}
				}
				
				if (!isset($fonts[$file])) throw(new Exception("Can't find font file '{$file}'"));
				$file = $fonts_folder . '/' . $fonts[$file];
				
				//echo $reg;
				//exit;
			} else {
				// Linux impl.
			}
		}
		
		if (!is_file($file)) throw(new Exception("Can't find font file '{$file}'"));

		$this->file  = $file;
		$this->size  = $size;
		$this->angle = $angle;
	}

	function getBoxExtended($text) {
		return imagettfbbox($this->size, $this->angle, $this->file, $text);
	}
	
	function getBox($text) {
		list(,,$x2,$y2,,,$x1,$y1) = $this->getBoxExtended($text);
		//echo "($x1,$y1)-($x2,$y2)\n";
		return array(abs($x2 - $x1), abs($y2 - $y1));
	}
	
	function getBaseLine($text) {
		$b = $this->getBoxExtended($text);
		return -$b[7];
	}
}

class Image {
	const PNG  = IMAGETYPE_PNG;
	const JPEG = IMAGETYPE_JPEG;
	const GIF  = IMAGETYPE_GIF;
	const AUTO = -1;
	
	private static $map = array(self::GIF => 'gif', self::PNG => 'png', self::JPEG => 'jpeg');
	private static $map_r = array('gif' => self::GIF, 'png' => self::PNG, 'jpeg' => self::JPEG, 'jpg' => self::JPEG);

	public $i;
	public $x, $y;
	public $w, $h;
	
	function __construct($w = null, $h = null, $bpp = 32) {
		if (empty($w) && empty($h)) return;
		$this->w = $w; $this->h = $h;
		switch ($bpp) {
			case 32: case 24: default:
				$i = $this->i = ImageCreateTrueColor($w, $h);
				if ($bpp == 32) {
					ImageSaveAlpha($i, true);
					ImageAlphaBlending($i, false);
					Imagefilledrectangle($i, 0, 0, $w, $h, imagecolorallocatealpha($i, 0, 0, 0, 0x7f));
					ImageAlphaBlending($i, true);
				}
			break;
			case 8:
				$i = $this->i = imagecreate($w, $h);
			break;
		}
	}
	
	static function fromFile($url) {
		$i = new Image();
		list($i->w, $i->h, $type) = getimagesize($url);
		if (!isset(self::$map[$type])) throw(new Exception('Invalid file format'));
		$call = 'imagecreatefrom' . self::$map[$type];
		$i->i = $call($url);
		ImageSaveAlpha($i->i, true);
		return $i;
	}
	
	static function fromGD($igd) {
		$i = new Image();
		list($i->i, $i->x, $i->y, $i->w, $i->h) = array($igd, 0, 0, imageSX($igd), imageSY($igd));
		return $i;
	}

	function checkBounds($x, $y) {
		return ($x < 0 || $y < 0 || $x >= $this->w || $y >= $this->h);
	}
	
	function isSlice() {
		return ($this->x != 0 || $this->y != 0 || $this->w != imageSX($this->i) || $this->h != imageSY($this->i));
	}
	
	function get($x, $y) {
		if ($this->checkBounds($x, $y)) return -1;
		return imageColorAt($i, $x + $this->x, $y + $this->y);
	}
	
	function color($r = 0x00, $g = 0x00, $b = 0x00, $a = 0xFF) {
		if (is_string($r)) sscanf($r, '#%02X%02X%02X%02X', $r, $g, $b, $a);
		return imagecolorallocatealpha($this->i, $r, $g, $b, round(0x7F - (($a * 0x7F) / 0xFF)));
	}
	
	function put($i, $x, $y, $w = null, $h = null) {
		if ($i instanceof Image) {
			if ($w === null) $w = $i->w;
			if ($h === null) $h = $i->h;
			imagecopyresampled($this->i, $i->i, $x, $y, $i->x, $i->y, $w, $h, $i->w, $i->h);
		} else {
			imagesetpixel($this->i, $x, $y, $i);
		}
	}
	
	function drawText($f, $x, $y, $text, $color = 0xFFFFFF, $anchorX = -1, $anchorY = -1, $baseLine = false) {
		if ($this->isSlice()) {
			throw(new Exception("Drawing in slices not implemented"));
		} else {
			$b = $f->getBox($text);
			$rx = $x - ((($anchorX + 1) * $b[0]) / 2);
			$ry = $y - ((($anchorY + 1) * $b[1]) / 2);
			if (!$baseLine) $ry += $f->getBaseLine($text);
			//echo "";
			imagettftext($this->i, $f->size, $f->angle, $rx, $ry, $color, $f->file, $text);
		}
	}
	
	function slice($x, $y, $w, $h) {
		$i = new Image();
		$i->i = $this->i;
		list($i->x, $i->y, $i->w, $i->h) = array($x, $y, $w, $h);
		return $i;
	}
	
	function save($name, $f = self::AUTO) {
		$i = $this;
	
		if ($f == self::AUTO) {
			$f = self::PNG;
			$f = @self::$map_r[substr(strtolower(strrchr($name, '.')), 1)];
		}
		
		if ($i->isSlice()) {
			$i2 = $i;
			$i = new Image($i->w, $i->h);
			$i->put(0, 0, $i2);
		}
		
		$p = array($i->i, $name);
		call_user_func_array('image' . self::$map[$f], $p);
	}
}
