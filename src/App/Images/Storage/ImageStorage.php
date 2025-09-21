<?php

declare(strict_types=1);

namespace App\Images\Storage;

use App\Images\ImageRequest;

interface ImageStorage {

	public function getOriginalPath(string $name): string;

	public function getResizedPath(ImageRequest $imageRequest): string;

	public function fileExists(string $path): bool;

	public function originalExists(string $name): bool;

	public function resizeExists(ImageRequest $imageRequest): bool;

	public function deleteOriginal(string $name): void;

	public function deleteAllResized(string $name): void;

	public function deleteResized(ImageRequest $imageRequest): void;
}
