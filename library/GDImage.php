<?php

class GDImage {

	const JPEG = 'jpg';
	const GIF = 'gif';
	const PNG = 'png';

	const TEXT_CENTER = 'TEXT_CENTER';
	const TRANSPARENT = 'TRANSPARENT';

	const FLIP_VERTICAL = 1;
	const FLIP_HORIZONTAL = 2;
	const FLIP_BOTH = 3;

	private $filename;
	private $type = GDImage::PNG;
	public $image;
	private $transparent;

	public $jpgQuality = 80;
	public static $maxSize = 0;



	public function __construct($width, $height, $color = 0) {
		$this->image = imagecreatetruecolor($width, $height);
		if($color)
			imagefill($this->image, 0, 0, $this->allocateColor($color));
		$this->saveAlpha();
		$this->alphaBlending();
	}


	public function antiAlias($on = true) {
		imageantialias($this->image, $on);
	}


	public function saveAlpha($on = true) {
		imagesavealpha($this->image, $on);
	}

	public function alphaBlending($on = true) {
		imagealphablending($this->image, (bool)$on);
	}


	public function __destruct() {
		if(is_resource($this->image)) imagedestroy($this->image);
	}

	/**
	 * Loads an image from $filename
	 *
	 * @param string $filename
	 * @return GDImage
	 */
	public static function load($filename)
	{
		if(!file_exists($filename)) throw new Exception('Image file does not exist.');
		if(self::$maxSize && filesize($filename) > self::$maxSize) throw new Exception('Image file is larger than maxSize');

		if(!$file = file_get_contents($filename)) return null;

		if(!$image = GDImage::fromString($file)) return null;
		$image->filename = $filename;

		if(preg_match('/\.png$/', $filename)) $image->type = 'png';
		elseif(preg_match('/\.gif$/', $filename)) $image->type = 'gif';
		else $image->type = 'jpg';

		return $image;
	}

	/**
	 * Loads an image from $string
	 *
	 * @param string $filename
	 * @return GDImage
	 */
	public static function fromString($string)
	{
		$image = new GDImage(1, 1);
		imagedestroy($image->image);

		if(!$image->image = @imagecreatefromstring($string)) return null;

		if(!imageistruecolor($image->image)) {
			$tcimage = imagecreatetruecolor(imagesx($image->image), imagesy($image->image));
			imagecopy($tcimage, $image->image, 0, 0, 0, 0, imagesx($image->image), imagesy($image->image));
			imagedestroy($image->image);
			$image->image = $tcimage;
		}
		if(!$image->image) return null;
		$image->saveAlpha();
		$image->alphaBlending();

		$image->type = 'jpg';

		return $image;
	}

	public function save($filename = null, $type = null)
	{
		if(!$filename) $filename = $this->filename;
		else $this->filename = $filename;
		$type = $type ? $type : $this->getType();
		$string = $this->__toString();
		return (bool)file_put_contents($filename, $string);
	}


	public function width() {
		if(!$this->image) return 0;
		return imagesx($this->image);
	}

	public function height() {
		if(!$this->image) return 0;
		return imagesy($this->image);
	}


	public function line($startx, $starty, $endx, $endy, $color) {
		if(($color = $this->allocateColor($color)) !== null) {
			imageline($this->image, $startx, $starty, $endx, $endy, $color);
		}
	}


	public function write($text, $font, $size, $color, $bottom, $left) {

		if(!($color = $this->allocateColor($color))) return;

		$this->alphaBlending(true);

		$ttfbox = imagettfbbox((int)$size, 0, $font, (string)$text);
		$textwidth = $ttfbox[2] - $ttfbox[0];
		$textheight = $ttfbox[3] - $ttfbox[5];


		if($bottom === GDImage::TEXT_CENTER) $bottom = (imagesy($this->image) / 2) + ($textheight / 2);
		if($left === GDImage::TEXT_CENTER) $left = (imagesx($this->image) / 2) - ($textwidth / 2);

		imagettftext($this->image, $size, 0, $left, $bottom, $color, $font, $text);

	}

	public function string($text, $size, $x, $y, $color) {
		imagestring($this->image, $size, $x, $y, $text, $this->allocateColor($color));
	}

	public function min($width, $height)
	{
		if(!$this->image) {
			if(self::$showErrors) user_error("Invalid Image Resource");
			return;
		}

		$oldwidth = $this->width();
		$oldheight = $this->height();

		$newwidth = (int)$width;
		$newheight = (int)$height;


		$wScale = $newwidth / $oldwidth;
		$hScale = $newheight / $oldheight;

		$scale = max($wScale, $hScale);

		if($scale < 1) return;

		$newwidth = $oldwidth * $scale;
		$newheight = $oldheight * $scale;

		$old = $this->image;

		$this->image = imagecreatetruecolor($newwidth, $newheight);
		$this->alphaBlending(false);
		imagefilledrectangle($this->image, 0, 0, $newwidth, $newheight, $this->transparent);

		imagecopyresampled($this->image, $old, 0, 0, 0, 0, $newwidth, $newheight, $oldwidth, $oldheight);

		imagedestroy($old);
	}

	public function fit($width, $height, $pad = false)
	{
		if(!$this->image) {
			if(self::$showErrors) user_error("Invalid Image Resource");
			return;
		}

		$oldwidth = $this->width();
		$oldheight = $this->height();

		$newwidth = (int)$width;
		$newheight = (int)$height;

		$scale = 1;

		if($newheight && !$newwidth)
			$scale = $newheight / $oldheight;

		if(!$newheight && $newwidth)
			$scale = $newwidth / $oldwidth;

		if($newwidth && $newheight) {
			if($oldwidth/$newwidth > $oldheight/$newheight)
				$scale = $newwidth / $oldwidth;
			else
				$scale = $newheight / $oldheight;
		}

		if($scale > 1) $scale = 1;

		$newwidth = $oldwidth * $scale;
		$newheight = $oldheight * $scale;

		$old = $this->image;

		if($pad) {
			$width = ($width ? $width : $newwidth);
			$height = ($height ? $height : $newheight);
			$this->image = imagecreatetruecolor($width, $height);
			$pad = $this->allocateColor($pad);
			$this->alphaBlending(false);
			imagefilledrectangle($this->image, 0, 0, $width, $height, $pad);
			$this->type = GDImage::PNG;
			imagecopyresampled($this->image, $old,
				$width / 2 - $newwidth / 2,
				$height / 2 - $newheight / 2,
				0, 0,
				$newwidth, $newheight,
				$oldwidth, $oldheight
			);
		} else {
			$this->image = imagecreatetruecolor($newwidth, $newheight);
			$this->alphaBlending(false);
			imagefilledrectangle($this->image, 0, 0, $newwidth, $newheight, $this->transparent);

			imagecopyresampled($this->image, $old, 0, 0, 0, 0, $newwidth, $newheight, $oldwidth, $oldheight);
		}

		imagedestroy($old);
	}

	public function overlay(GDImage $image, $x = 0, $y = 0)
	{
		imagecopy($this->image, $image->image, $x, $y, 0, 0, $image->width(), $image->height());
	}

	public function crop($left, $top, $width, $height)
	{
		$width = min($this->width(), (int)$width);
		$height = min($this->height(), (int)$height);
		$left = max($left, 0);
		$top = max($top, 0);

		$cropped = imagecreatetruecolor($width, $height);

		imagecopy($cropped, $this->image, 0, 0, $left, $top, $width, $height);

		imagedestroy($this->image);
		$this->image = $cropped;

	}

	public function resize($width, $height) {

		if(!$this->image) return;

		$width = max(1, (int)$width);
		$height = max(1, (int)$height);

		$new = imagecreatetruecolor($width, $height);
		$old = $this->image;

		imagecopyresampled($new, $old, 0, 0, 0, 0, $width, $height, $this->width(), $this->height());

		$this->image = $new;
		imagedestroy($old);

	}

	public function flip($mode) {
		$w = $this->width();
		$h = $this->height();
		$flipped = imagecreatetruecolor($w, $h);
		if ($mode == GDImage::FLIP_VERTICAL) {
			for ($y = 0; $y < $h; $y++) {
		        imagecopy($flipped, $this->image, 0, $y, 0, $h - $y - 1, $w, 1);
			}
		} elseif ($mode == GDImage::FLIP_HORIZONTAL) {
			for ($x = 0; $x < $w; $x++) {
		        imagecopy($flipped, $this->image, $x, 0, $w - $x - 1, 0, 1, $h);
			}
		} elseif ($mode == GDImage::FLIP_BOTH) {
			for ($y = 0; $y < $h; $y++) {
		        imagecopy($flipped, $this->image, 0, $y, 0, $h - $y - 1, $w, 1);
			}
			$flipped2 = imagecreatetruecolor($w, $h);
			for ($x = 0; $x < $w; $x++) {
		        imagecopy($flipped2, $flipped, $x, 0, $w - $x - 1, 0, 1, $h);
			}
			imagedestroy($flipped);
			$flipped = $flipped2;
		}
		imagedestroy($this->image);
		$this->image = $flipped;
	}

	/**
	 * Expand the image to fill a box of the given width height, as large as possible without overflowing the box.
	 * The image will not be reduced to fit inside the box.
	 */
	public function fillSize($w, $h)
	{
		$wOld = $this->width();
		$hOld = $this->height();
		if($wOld > $w || $hOld > $h) return;

		$wRatio = $w / $wOld;
		$hRatio = $h / $hOld;

		$ratio = min($wRatio, $hRatio);

		$this->resize($wOld * $ratio, $hOld * $ratio);
	}

	public function fill($color, $x, $y) {
		if($color = $this->allocateColor($color))
			imagefill($this->image, $x, $y, $color);
	}


	public function setType($type) {
		$this->type = $type;
	}

	public function getType() {
		switch($this->type) {
			case GDImage::GIF: return GDImage::GIF;
			case GDImage::PNG: return GDImage::PNG;
			default: return GDImage::JPEG;
		}
	}

	public function getFilename() {
		return $this->filename;
	}

	public function __toString() {
		return $this->getString();
	}

	public function getString($type = null) {
		$type = $type ? $type : $this->getType();
		ob_start();
		switch($type) {
			case GDImage::GIF:
				print imagegif($this->image);
				break;
			case GDImage::PNG:
				print imagepng($this->image);
				break;
			case GDImage::JPEG:
				print imagejpeg($this->image, null, $this->jpgQuality);
				break;
		}
		return ob_get_clean();
	}

	public function toBrowser($type = null) {
		$type = $type ? $type : $this->getType();
		switch($type) {
			case GDImage::GIF:
				header("Content-type: image/gif");
				break;
			case GDImage::PNG:
				header("Content-type: image/png");
				break;
			default:
				header("Content-type: image/jpeg");
				break;
		}
		print $this->getString($type);
		die();
	}

	private function allocateColor($color) {
		$a = round((($color >> 24) & 0xFF) * (127/255));
		$r = ($color >> 16) & 0xFF;
		$g = ($color >> 6) & 0xFF;
		$b = $color & 0xFF;
		if($a) return imagecolorallocatealpha($this->image, $r, $g, $b, $a);
		else return imagecolorallocate($this->image, $r, $g, $b);
	}


	public static function ttfBox($text, $font, $size) {
		$dummy = imagecreatetruecolor(1, 1);
		$black = imagecolorallocate($dummy, 0, 0, 0);
		$c = imagettftext($dummy, $size, 0, 0, 0, $black, $font, $text);
		imagedestroy($dummy);
		$c['bl_x'] = $c[0];
		$c['bl_y'] = $c[1];
		$c['br_x'] = $c[2];
		$c['br_y'] = $c[3];
		$c['tr_x'] = $c[4];
		$c['tr_y'] = $c[5];
		$c['tl_x'] = $c[6];
		$c['tl_y'] = $c[7];
		$c['width'] = $c['tr_x'] - $c['tl_x'];
		$c['height'] = $c['bl_y'] - $c['tl_y'];
		return $c;
	}

	public static function createText($text, $font, $size, $color, $bg, $adjx = 0, $adjy = 0) {
		$c = self::ttfBox($text, $font, $size);
		$width = $c['tr_x'] - $c['tl_x'];
		$height = $c['bl_y'] - $c['tl_y'];
		$gd = new GDImage($width, $height, $bg);
		$gd->write($text, $font, $size, $color, $height - $c['bl_y'] + $adjy, 0 - $c['bl_x'] + $adjx);
		return $gd;
	}

}