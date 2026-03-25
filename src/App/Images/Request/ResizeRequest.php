<?php

declare(strict_types=1);

namespace App\Images\Request;

use App\Application\Errors\BadRequestException;
use App\Images\Info\ImageDimensions;
use Zavadil\Common\Helpers\ImagezHelper;
use Zavadil\Common\Helpers\PathHelper;
use Zavadil\Common\Helpers\StringHelper;

class ResizeRequest {

	public string $name;

	public ImageDimensions $size;

	public string $resizeType;

	public ?string $verticalAlign;

	public ?string $horizontalAlign;

	public ?string $imageExt;

	public ?int $page;

	public function __construct(
		string $name,
		ImageDimensions $size,
		string $resizeType,
		?string $imageExt = null,
		?string $verticalAlign = null,
		?string $horizontalAlign = null,
		?int $page = null
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
		$this->verticalAlign = StringHelper::lowercase($verticalAlign);

		if (StringHelper::notBlank($horizontalAlign) && !HorizontalAlign::exists($horizontalAlign)) {
			throw new BadRequestException("Horizontal align $horizontalAlign does not exist");
		}
		$this->horizontalAlign = StringHelper::lowercase($horizontalAlign);

		$this->page = $page;
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
		if ($this->page !== null) {
			$base .= "[{$this->page}]";
		}
		$base .= '.';
		$base .= StringHelper::isBlank($this->imageExt) ? PathHelper::getFileExt($this->name) : $this->imageExt;
		return $base;
	}

	public function getVerificationTokenRawValue(string $secretToken): string {
		return ImagezHelper::createTokenRaw(
			$secretToken,
			$this->name,
			$this->size->x,
			$this->size->y,
			$this->resizeType,
			$this->imageExt,
			$this->verticalAlign,
			$this->horizontalAlign,
			$this->page
		);
	}

}
