<?php

declare(strict_types=1);

namespace App\Images\Info;

use App\Application\Helpers\PathHelper;

class ImageInfo {

	private string $path;

	private ?array $info;

	public function __construct(string $filePath) {
		$this->path = $filePath;
		$this->info = @getimagesize($filePath);
	}

	public function hasValidInfo(): bool {
		return is_array($this->info) && isset($this->info[0]) && isset($this->info[1]);
	}

	public function getSize(): ImageSize {
		return new ImageSize(intval($this->info[0]), intval($this->info[1]));
	}

	public function getMimeType(): ?string {
		return isset($this->info['mime']) ? $this->info['mime'] : null;
	}

	public function getExtension(): ?string {
		return PathHelper::getFileExt($this->path);
	}

}
