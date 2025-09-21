<?php

declare(strict_types=1);

namespace App\Images\Resizer;

use App\Images\ImageRequest;

interface ImageResizer {

	public function exists(string $name): bool;

	public function resize(ImageRequest $imageRequest): string;

}
