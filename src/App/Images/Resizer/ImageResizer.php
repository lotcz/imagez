<?php

declare(strict_types=1);

namespace App\Images\Resizer;

use App\Images\Request\ResizeRequest;

interface ImageResizer {

	public function getResizedImagePath(string $originalPath, ResizeRequest $imageRequest): string;

}
