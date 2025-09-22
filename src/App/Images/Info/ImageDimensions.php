<?php

declare(strict_types=1);

namespace App\Images\Info;

class ImageDimensions {

	public int $x;

	public int $y;

	public function __construct(int $x, int $y) {
		$this->x = $x;
		$this->y = $y;
	}

	public function isZero(): bool {
		return $this->x <= 0 && $this->y <= 0;
	}

}
