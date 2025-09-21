<?php

declare(strict_types=1);

namespace App\Images\Request;

use App\Application\Helpers\PathHelper;
use App\Application\Helpers\StringHelper;
use App\Images\CropPosition;
use App\Images\Info\ImageSize;
use App\Images\ResizeTypeKey;

class ResizeRequest {

	public string $name;

	public ImageSize $size;

	public string $resizeType;

	public string $cropPositionHorizontal;

	public string $cropPositionVertical;

	public ?string $imageExt;

	public function __construct(
		string $name,
		ImageSize $size,
		string $resizeType = ResizeTypeKey::FIT,
		string $cropPositionHorizontal = CropPosition::CENTER,
		string $cropPositionVertical = CropPosition::CENTER,
		?string $imageExt = null
	) {
		$this->name = $name;
		$this->size = $size;
		$this->resizeType = $resizeType;
		$this->cropPositionHorizontal = $cropPositionHorizontal;
		$this->cropPositionVertical = $cropPositionVertical;
		$this->imageExt = $imageExt;
	}

	public function getResizedDirName(): string {
		return "{$this->size->x}-{$this->size->y}-{$this->resizeType}-{$this->cropPositionHorizontal}-{$this->cropPositionVertical}";
	}

	public function getResizedFileName(): string {
		return StringHelper::isBlank($this->imageExt) ? $this->name : PathHelper::getFileBase($this->name) . '.' . $this->imageExt;
	}

	public function getResizedPath(): string {
		return PathHelper::of($this->getResizedDirName(), $this->getResizedFileName());
	}

}
