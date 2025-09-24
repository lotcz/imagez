<?php

declare(strict_types=1);

namespace App\Images\Info;

use App\Application\Helpers\PathHelper;
use Exception;

class ImageInfo {

	private string $path;

	private ?array $info = null;

	public function __construct(string $filePath) {
		$this->path = $filePath;
	}

	public function exists(): bool {
		return file_exists($this->path);
	}

	public function getInfo(): array {
		if (!$this->exists()) {
			throw new Exception("Image $this->path does not exist, cannot get info");
		}
		if ($this->info === null) {
			$info = @getimagesize($this->path);
			$this->info = is_array($info) ? $info : [];
		}
		return $this->info;
	}

	public function getDimensions(): ?ImageDimensions {
		$info = $this->getInfo();
		if (!(isset($info[0]) && isset($info[1]))) {
			return new ImageDimensions(0, 0);
		}
		return new ImageDimensions(intval($info[0]), intval($info[1]));
	}

	public function getMimeType(): ?string {
		$info = $this->getInfo();
		return isset($info['mime']) ? $info['mime'] : null;
	}

	public function getFileName(): ?string {
		return PathHelper::getFileName($this->path);
	}

	public function getExtension(): ?string {
		return PathHelper::getFileExt($this->path);
	}

	public function getFileSize(): int {
		return filesize($this->path);
	}

	public function getHealthPayload(?string $name = null): array {
		$size = $this->getDimensions();
		return [
			'name' => $name || $this->getFileName(),
			'size' => $this->getFileSize(),
			'width' => $size->x,
			'height' => $size->y,
			'mime' => $this->getMimeType()
		];
	}
}
