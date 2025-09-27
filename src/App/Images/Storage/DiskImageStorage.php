<?php

declare(strict_types=1);

namespace App\Images\Storage;

use App\Images\Formats\ImageFormats;
use App\Images\Request\ResizeRequest;
use DirectoryIterator;
use Psr\Log\LoggerInterface;
use Zavadil\Common\Helpers\PathHelper;
use Zavadil\Common\Settings\Settings;

class DiskImageStorage implements ImageStorage {

	private LoggerInterface $logger;

	private ImageFormats $formats;

	private string $baseDir;

	private string $originalDir;

	public function __construct(LoggerInterface $logger, Settings $settings, ImageFormats $formats) {
		$this->logger = $logger;
		$this->formats = $formats;

		$this->baseDir = $settings->get('imageStorePath');
		if (!is_dir($this->baseDir)) {
			$this->logger->info("Creating base dir: $this->baseDir");
			mkdir($this->baseDir, 0777, true);
		}

		$this->originalDir = PathHelper::of($this->baseDir, 'original');
		if (!file_exists($this->originalDir)) {
			$this->logger->info("Creating original dir: $this->originalDir");
			mkdir($this->originalDir, 0777, true);
		}
	}

	public function obtainNewName(string $ext): string {
		$basename = bin2hex(random_bytes(12));
		$name = $basename . '.' . $ext;
		if ($this->originalExists($name)) {
			return $this->obtainNewName($ext);
		}
		return $name;
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
		if (!is_dir($this->baseDir)) {
			return;
		}

		$this->logger->info("Deleting all resized variants of $name");

		$base = PathHelper::getFileBase($name);
		$extensions = $this->formats->getResizedExtensions();
		$fileNames = [];
		foreach ($extensions as $ext) {
			$fileNames[] = $base . '.' . $ext;
		}

		$iterator = new DirectoryIterator($this->baseDir);

		foreach ($iterator as $dir) {
			if ($dir->isDir() && !$dir->isDot() && $dir->getFilename() != 'original') {
				foreach ($fileNames as $fileName) {
					$filePath = PathHelper::of($dir->getPathname(), $fileName);
					if (is_file($filePath)) {
						@unlink($filePath);
					}
				}
			}
		}
	}

	public function deleteResized(ResizeRequest $imageRequest): void {
		$this->deleteFile($this->getResizedPath($imageRequest));
	}

}
