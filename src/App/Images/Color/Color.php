<?php

declare(strict_types=1);

namespace App\Images\Color;

class Color {

	public int $r;

	public int $g;

	public int $b;

	public function __construct(int $r, int $g, int $b) {
		$this->r = $r;
		$this->g = $g;
		$this->b = $b;
	}

	public static function fromHex(string $hex): Color|null {
		if (empty($hex)) return null;
		$hex = ltrim($hex, '#');
		return new Color(
			hexdec(substr($hex, 0, 2)),
			hexdec(substr($hex, 2, 2)),
			hexdec(substr($hex, 4, 2))
		);
	}

	public function getHex(): string {
		return sprintf("#%02x%02x%02x", $this->r, $this->g, $this->b);
	}

}
