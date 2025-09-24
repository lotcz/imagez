<?php

declare(strict_types=1);

namespace App\Images\Formats;

class ImageFormats {

	private array $formats;

	public function __construct() {
		$this->formats[] = new ImageFormat(
			"jpg",
			"image/jpeg",
			"imagecreatefromjpeg",
			"imagejpeg",
			["jpeg", "jfif", "jfif-tbnl", "jpe"]
		);
		$this->formats[] = new ImageFormat(
			"png",
			"image/png",
			"imagecreatefrompng",
			"imagepng"
		);
		$this->formats[] = new ImageFormat(
			"webp",
			"image/webp",
			"imagecreatefromwebp",
			"imagewebp"
		);
		$this->formats[] = new ImageFormat(
			"gif",
			"image/gif",
			"imagecreatefromgif",
			"imagegif"
		);
	}

	public function findByExtension(?string $ext): ?ImageFormat {
		if (empty($ext)) return null;
		foreach ($this->formats as $f) {
			if ($f->hasExtension($ext)) {
				return $f;
			}
		}
		return null;
	}

	public function findByMimeType(?string $mime): ?ImageFormat {
		if (empty($mime)) return null;
		foreach ($this->formats as $f) {
			if ($f->mimeType == $mime) {
				return $f;
			}
		}
		return null;
	}

	public function getResizedExtensions(): array {
		$extensions = [];
		foreach ($this->formats as $f) {
			$extensions[] = $f->extension;
		}
		return $extensions;
	}

}
