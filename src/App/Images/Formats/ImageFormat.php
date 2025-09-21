<?php

declare(strict_types=1);

namespace App\Images\Formats;

class ImageFormat {

	public string $extension;

	public string $mimeType;

	public string $image_create_func;

	public $image_save_func;

	public array $altExtensions;

	public function __construct(
		string $extension,
		string $mimeType,
		string $image_create_func,
		string $image_save_func,
		array $altExtensions = []
	) {
		$this->extension = $extension;
		$this->mimeType = $mimeType;
		$this->altExtensions = $altExtensions;
		$this->image_create_func = $image_create_func;
		$this->image_save_func = $image_save_func;
	}

	public function hasExtension(string $ext) {
		return $this->extension == $ext || in_array($ext, $this->altExtensions);
	}

}
