<?php

declare(strict_types=1);

namespace App\Images\Storage;

use App\Application\Helpers\PathHelper;
use App\Application\Settings\Settings;
use App\Images\ImageRequest;
use Psr\Log\LoggerInterface;

class DiskImageStorage implements ImageStorage {

	private string $baseDir;

	private string $originalDir;

	public function __construct(LoggerInterface $logger, Settings $settings) {
		$this->baseDir = PathHelper::of($settings->get('cachePath'), 'image-store');
		if (!file_exists($this->baseDir)) {
			$logger->info("Creating base dir: $this->baseDir");
			mkdir($this->baseDir, 0777, true);
		}

		$this->originalDir = PathHelper::of($this->baseDir, 'original');
		if (!file_exists($this->originalDir)) {
			$logger->info("Creating original dir: $this->originalDir");
			mkdir($this->originalDir, 0777, true);
		}
	}

	public function getOriginalPath(string $name): string {
		return PathHelper::of($this->originalDir, $name);
	}

	public function getResizedPath(ImageRequest $imageRequest): string {
		return PathHelper::of($this->baseDir, $imageRequest->getPathName());
	}

	private function fileExists(string $path): bool {
		return file_exists($path);
	}

	public function originalExists(string $name): bool {
		return $this->fileExists($this->getOriginalPath($name));
	}

	public function resizeExists(ImageRequest $imageRequest): bool {
		return $this->fileExists($this->getResizedPath($imageRequest));
	}

	private function deleteFile(string $path): void {
		if ($this->fileExists($path)) {
			unlink($path);
		}
	}

	public function deleteOriginal(string $name): void {
		$this->deleteFile($this->getOriginalPath($name));
		$this->deleteAllResized($name);
	}

	public function deleteAllResized(string $name): void {
		// TODO: Implement deleteAllResized() method.
	}

	public function deleteResized(ImageRequest $imageRequest): void {
		$this->deleteFile($this->getResizedPath($imageRequest));
	}

}
