<?

class Color {

	private $r, $g, $b;

	public function __construct($value)
	{
		if(is_string($value)) $value = hexdec($value);
		$this->r = ($value & 0xFF0000) >> 16;
		$this->g = ($value & 0xFF00) >> 8;
		$this->b = $value & 0xFF;
	}

	public function adjust($brightness)
	{
		if($brightness == 0) return $this;
		$color = (($this->r + (($brightness < 0 ? $this->r : 255 - $this->r) * ($brightness / 100))) << 16) +
				 (($this->g + (($brightness < 0 ? $this->g : 255 - $this->g) * ($brightness / 100))) << 8) +
				  ($this->b + (($brightness < 0 ? $this->b : 255 - $this->b) * ($brightness / 100)));
		return new Color($color);
	}

	public function hex()
	{
		return sprintf('%06X', $this->int());
	}

	public function int()
	{
		return ($this->r << 16) + ($this->g << 8) + $this->b;
	}

	public function gray()
	{
		$avg =	(int)(0.2989 * $this->r +
					  0.5870 * $this->g +
					  0.1140 * $this->b);
		$avg = ($avg << 16) + ($avg << 8) + $avg;
		return new Color($avg);
	}

	public function contrast($light, $dark) {
		if(!($light instanceof Color)) $light = new Color($light);
		if(!($dark instanceof Color)) $dark = new Color($dark);
		$gray = $this->gray();
		return ($gray->int() > 0xB0B0B0) ? $dark : $light;
	}

	public function __toString() {
		return $this->hex();
	}

}