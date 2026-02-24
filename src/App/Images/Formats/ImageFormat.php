<?php

declare(strict_types=1);

namespace App\Images\Formats;

class ImageFormat {

	public string $extension;

	public string $mimeType;

	public ?string $image_create_func;

	public ?string $image_save_func;

	public array $altExtensions;

	public bool $transparent = true;

	public function __construct(
		string $extension,
		string $mimeType,
		?string $image_create_func,
		?string $image_save_func,
		array $altExtensions = [],
		bool $transparent = true
	) {
		$this->extension = $extension;
		$this->mimeType = $mimeType;
		$this->altExtensions = $altExtensions;
		$this->image_create_func = $image_create_func;
		$this->image_save_func = $image_save_func;
		$this->transparent = $transparent;
	}

	public function hasExtension(string $ext) {
		$ext = strtolower($ext);
		return $this->extension == $ext || in_array($ext, $this->altExtensions);
	}

}
