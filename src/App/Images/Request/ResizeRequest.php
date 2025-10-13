<?php

declare(strict_types=1);

namespace App\Images\Request;

use App\Application\Errors\BadRequestException;
use App\Images\Info\ImageDimensions;
use Zavadil\Common\Helpers\PathHelper;
use Zavadil\Common\Helpers\StringHelper;

class ResizeRequest {

	public string $name;

	public ImageDimensions $size;

	public string $resizeType;

	public ?string $verticalAlign;

	public ?string $horizontalAlign;

	public ?string $imageExt;

	public function __construct(
		string $name,
		ImageDimensions $size,
		string $resizeType,
		?string $imageExt = null,
		?string $verticalAlign = null,
		?string $horizontalAlign = null
	) {
		$this->name = $name;
		$this->size = $size;

		if (!ResizeType::exists($resizeType)) {
			throw new BadRequestException("Resize type $resizeType does not exist");
		}
		$this->resizeType = $resizeType;

		$this->imageExt = empty($imageExt) ? null : strtolower($imageExt);

		if (StringHelper::notBlank($verticalAlign) && !VerticalAlign::exists($verticalAlign)) {
			throw new BadRequestException("Vertical align $verticalAlign does not exist");
		}
		$this->verticalAlign = $verticalAlign;

		if (StringHelper::notBlank($horizontalAlign) && !HorizontalAlign::exists($horizontalAlign)) {
			throw new BadRequestException("Horizontal align $horizontalAlign does not exist");
		}
		$this->horizontalAlign = $horizontalAlign;
	}

	public function getResizedDirName(): string {
		return "{$this->size->x}-{$this->size->y}-{$this->resizeType}";
	}

	public function getResizedFileName(): string {
		$base = PathHelper::getFileBase($this->name);
		if (!StringHelper::isBlank($this->verticalAlign)) {
			$base .= "-{$this->verticalAlign}";
		}
		if (!StringHelper::isBlank($this->horizontalAlign)) {
			$base .= "-{$this->horizontalAlign}";
		}
		$base .= '.';
		$base .= StringHelper::isBlank($this->imageExt) ? PathHelper::getFileExt($this->name) : $this->imageExt;
		return $base;
	}

	public function getResizedPath(): string {
		return PathHelper::of($this->getResizedDirName(), $this->getResizedFileName());
	}

	public function getVerificationTokenRawValue(string $secretToken): string {
		$base = "$secretToken-{$this->name}-{$this->getResizedDirName()}";
		if (!StringHelper::isBlank($this->imageExt)) {
			$base .= "-{$this->imageExt}";
		}
		if (!StringHelper::isBlank($this->verticalAlign)) {
			$base .= "-{$this->verticalAlign}";
		}
		if (!StringHelper::isBlank($this->horizontalAlign)) {
			$base .= "-{$this->horizontalAlign}";
		}
		return $base;
	}

}
