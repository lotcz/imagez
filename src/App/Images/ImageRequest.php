<?php

declare(strict_types=1);

namespace App\Images;

use App\Application\Helpers\PathHelper;
use App\Application\Helpers\StringHelper;

class ImageRequest {

	public string $name;

	public int $maxWidth;

	public int $maxHeight;

	public string $resizeType;

	public string $cropPositionHorizontal;

	public string $cropPositionVertical;

	public ?string $imageExt;

	public function __construct(
		string  $name,
		int     $maxWidth,
		int     $maxHeight,
		string  $resizeType = ResizeType::FIT,
		string  $cropPositionHorizontal = CropPosition::CENTER,
		string  $cropPositionVertical = CropPosition::CENTER,
		?string $imageExt = null
	) {
		$this->name = $name;
		$this->maxWidth = $maxWidth;
		$this->maxHeight = $maxHeight;
		$this->resizeType = $resizeType;
		$this->cropPositionHorizontal = $cropPositionHorizontal;
		$this->cropPositionVertical = $cropPositionVertical;
		$this->imageExt = $imageExt;
	}

	public function getPathName(): string {
		$resize = "{$this->maxWidth}-{$this->maxHeight}-{$this->resizeType}-{$this->cropPositionHorizontal}-{$this->cropPositionVertical}";
		$file = StringHelper::isBlank($this->imageExt) ? $this->name : PathHelper::getFileBase($this->name) . '.' . $this->imageExt;
		return PathHelper::of($resize, $file);
	}

}
