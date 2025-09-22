<?php

declare(strict_types=1);

namespace App\Images\Storage;

use App\Application\Helpers\PathHelper;
use App\Application\Settings\Settings;
use App\Images\Info\ImageInfo;
use App\Images\Request\ResizeRequest;
use Psr\Log\LoggerInterface;

class DiskImageStorage implements ImageStorage {

	private LoggerInterface $logger;

	private string $baseDir;

	private string $originalDir;

	public function __construct(LoggerInterface $logger, Settings $settings) {
		$this->logger = $logger;

		$this->baseDir = PathHelper::of($settings->get('cachePath'), 'image-store');
		if (!file_exists($this->baseDir)) {
			$this->logger->info("Creating base dir: $this->baseDir");
			mkdir($this->baseDir, 0777, true);
		}

		$this->originalDir = PathHelper::of($this->baseDir, 'original');
		if (!file_exists($this->originalDir)) {
			$this->logger->info("Creating original dir: $this->originalDir");
			mkdir($this->originalDir, 0777, true);
		}
	}

	public function getOriginalPath(string $name): string {
		return PathHelper::of($this->originalDir, $name);
	}

	public function getResizedPath(ResizeRequest $imageRequest): string {
		$resizedDir = PathHelper::of($this->baseDir, $imageRequest->getResizedDirName());
		if (!file_exists($resizedDir)) {
			$this->logger->info("Creating resized dir: $resizedDir");
			mkdir($resizedDir, 0777, true);
		}
		return PathHelper::of($resizedDir, $imageRequest->getResizedFileName());
	}

	public function fileExists(string $path): bool {
		return file_exists($path);
	}

	public function originalExists(string $name): bool {
		return $this->fileExists($this->getOriginalPath($name));
	}

	public function resizeExists(ResizeRequest $imageRequest): bool {
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

	public function deleteResized(ResizeRequest $imageRequest): void {
		$this->deleteFile($this->getResizedPath($imageRequest));
	}

	public function getHealthPayload(string $name): array {
		$path = $this->getOriginalPath($name);
		$info = new ImageInfo($path);
		$size = $info->getDimensions();
		return [
			'size' => $info->getFileSize(),
			'width' => $size->x,
			'height' => $size->y,
			'mime' => $info->getMimeType()
		];
	}

}
