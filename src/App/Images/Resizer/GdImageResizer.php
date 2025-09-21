<?php

declare(strict_types=1);

namespace App\Images\Resizer;

use App\Images\ImageRequest;
use App\Images\Storage\ImageStorage;

class GdImageResizer implements ImageResizer {

	private ImageStorage $imageStorage;

	public function __construct(ImageStorage $storage) {
		$this->imageStorage = $storage;
	}

	public function exists(string $name): bool {
		return $this->imageStorage->originalExists($name);
	}

	public function resize(ImageRequest $imageRequest): string {
		return $this->imageStorage->getResizedPath($imageRequest);
	}
}
