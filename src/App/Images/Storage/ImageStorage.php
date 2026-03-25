<?php

declare(strict_types=1);

namespace App\Images\Storage;

use App\Images\Info\ImageInfo;
use App\Images\Request\ResizeRequest;

interface ImageStorage {

	public function obtainNewTempName(string $ext): string;

	public function obtainNewTempPath(string $ext): string;

	public function getOriginalPath(string $name): string;

	public function getResizedPath(ResizeRequest $imageRequest): string;

	public function fileExists(string $path): bool;

	public function originalExists(string $name): bool;

	public function resizeExists(ResizeRequest $imageRequest): bool;

	public function deleteOriginal(string $name): void;

	public function deleteAllResized(string $name): void;

	public function deleteResized(ResizeRequest $imageRequest): void;

	public function importImageFile(string $tmpPath): ImageInfo;
}
