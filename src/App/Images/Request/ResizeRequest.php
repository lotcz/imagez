<?php

declare(strict_types=1);

namespace App\Images\Request;

use App\Application\Errors\BadRequestException;
use App\Application\Helpers\PathHelper;
use App\Application\Helpers\StringHelper;
use App\Images\Info\ImageSize;

class ResizeRequest {

	public string $name;

	public ImageSize $size;

	public string $resizeType;

	public ?string $imageExt;

	public function __construct(
		string $name,
		ImageSize $size,
		string $resizeType,
		?string $imageExt = null
	) {
		$this->name = $name;
		$this->size = $size;

		if (!ResizeType::exists($resizeType)) {
			throw new BadRequestException("Resize type $resizeType does not exist");
		}
		$this->resizeType = $resizeType;

		$this->imageExt = $imageExt;
	}

	public function getResizedDirName(): string {
		return "{$this->size->x}-{$this->size->y}-{$this->resizeType}";
	}

	public function getResizedFileName(): string {
		return StringHelper::isBlank($this->imageExt) ? $this->name : PathHelper::getFileBase($this->name) . '.' . $this->imageExt;
	}

	public function getResizedPath(): string {
		return PathHelper::of($this->getResizedDirName(), $this->getResizedFileName());
	}

	public function getSecurityRawValue(string $securityToken): string {
		$base = "$securityToken-{$this->name}-{$this->getResizedDirName()}";
		if (!StringHelper::isBlank($this->imageExt)) {
			$base .= "-{$this->imageExt}";
		}
		return $base;
	}

}
