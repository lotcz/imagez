<?php

declare(strict_types=1);

namespace App\Images\Resizer;

use App\Images\ImageRequest;

interface ImageResizer {

	public function getResizedImagePath(string $originalPath, ImageRequest $imageRequest): string;

}
