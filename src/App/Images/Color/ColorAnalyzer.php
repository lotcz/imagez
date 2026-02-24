<?php

declare(strict_types=1);

namespace App\Images\Color;

use GdImage;

interface ColorAnalyzer {

	public function guessBackgroundColor(string $path): Color|null;

	public function removeBackgroundColor(string $path, string|null $hex = null, int|null $threshold = 30): GdImage|null;

}
