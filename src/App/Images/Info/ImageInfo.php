<?php

declare(strict_types=1);

namespace App\Images\Info;

use App\Application\Helpers\PathHelper;

class ImageInfo {

	private string $path;

	private ?array $info = null;

	public function __construct(string $filePath) {
		$this->path = $filePath;
	}

	public function getInfo(): array {
		if ($this->info === null) {
			$this->info = @getimagesize($this->path);
			if ($this->info === null) {
				$this->info = [];
			}
		}
		return $this->info;
	}

	public function getDimensions(): ImageDimensions {
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

	public function getExtension(): ?string {
		return PathHelper::getFileExt($this->path);
	}

	public function getFileSize(): int {
		return filesize($this->path);
	}

}
