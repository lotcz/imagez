<?php

declare(strict_types=1);

namespace App\Images\Resizer;

use App\Application\Errors\BadRequestException;
use App\Images\Formats\ImageFormats;
use App\Images\Info\ImageDimensions;
use App\Images\Info\ImageInfo;
use App\Images\Request\HorizontalAlign;
use App\Images\Request\ResizeRequest;
use App\Images\Request\ResizeType;
use App\Images\Request\VerticalAlign;
use App\Images\Storage\ImageStorage;
use Psr\Log\LoggerInterface;
use Throwable;
use Zavadil\Common\Helpers\IntegerHelper;
use Zavadil\Common\Helpers\StringHelper;
use Zavadil\Common\Settings\Settings;

class GdImageResizer implements ImageResizer {

	private LoggerInterface $logger;

	private Settings $settings;

	private ImageStorage $imageStorage;

	private ImageFormats $formats;

	public function __construct(LoggerInterface $logger, Settings $settings, ImageStorage $storage, ImageFormats $formats) {
		$this->logger = $logger;
		$this->settings = $settings;
		$this->imageStorage = $storage;
		$this->formats = $formats;
	}

	public function getResizedImagePath(string $originalPath, ResizeRequest $imageRequest): string {
		$resizedPath = $this->imageStorage->getResizedPath($imageRequest);
		if ($this->imageStorage->fileExists($resizedPath)) return $resizedPath;
		return $this->prepareResizedImage($originalPath, $resizedPath, $imageRequest);
	}

	private function prepareResizedImage(string $originalPath, string $resizedPath, ResizeRequest $resizeRequest): ?string {
		$info = new ImageInfo($originalPath);

		$originalFormat = $this->formats->findByMimeType($info->getMimeType());
		if ($originalFormat === null) {
			$originalFormat = $this->formats->findByExtension($info->getExtension());
		}
		if ($originalFormat === null) {
			throw new BadRequestException("Could not detect original format of image $originalPath");
		}
		if ($originalFormat->image_create_func === null) {
			// we don't know how to load this format
			return $originalPath;
		}

		if ($originalFormat->image_create_func === 'Imagick') {
			$tmpFormat = $this->formats->findByExtension("png");
			$tmpPath = $this->imageStorage->obtainNewTempPath($tmpFormat->extension);
			$page = $resizeRequest->page ?: 0;
			$img = new \Imagick();
			$img->setResolution(150, 150);
			$path = $originalPath . "[$page]";
			$img->readImage($path);
			$img->setImageFormat($tmpFormat->extension);
			$img->flattenImages();
			$img->writeImage($tmpPath);
			$img->clear();
			$img->destroy();
			return $this->prepareResizedImage($tmpPath, $resizedPath, $resizeRequest);
		}

		$originalSize = $info->getDimensions();
		if ($originalSize->isZero()) {
			throw new BadRequestException("Image $originalPath has zero size!");
		}

		$targetFormat = StringHelper::isBlank($resizeRequest->imageExt)
			? $originalFormat
			: $this->formats->findByExtension($resizeRequest->imageExt);
		if ($targetFormat === null) {
			throw new BadRequestException("Could not detect target format $resizeRequest->imageExt for image $originalPath");
		}
		if ($originalFormat->image_save_func === null) {
			// we don't know how to save this format
			return $originalPath;
		}

		try {
			$image_create_func = $originalFormat->image_create_func;
			$img = @$image_create_func($originalPath);

		} catch (Throwable $e) {
			throw new BadRequestException("Error when loading image $originalPath: {$e->getMessage()}");
		}

		$srcStart = new ImageDimensions(0, 0);
		$srcSize = new ImageDimensions($originalSize->x, $originalSize->y);
		$destSize = new ImageDimensions($resizeRequest->size->x, $resizeRequest->size->y);

		switch ($resizeRequest->resizeType) {
			case ResizeType::SCALE:
				// all set already
				break;

			case ResizeType::CROP:
				$original_aspect = (float)$originalSize->x / $originalSize->y;
				$new_aspect = (float)$resizeRequest->size->x / $resizeRequest->size->y;

				if ($original_aspect > $new_aspect) {
					$srcSize->x = IntegerHelper::round((float)$originalSize->y * $new_aspect);
					$xdiff = $originalSize->x - $srcSize->x;
					switch ($resizeRequest->horizontalAlign) {
						case HorizontalAlign::LEFT:
							$srcStart->x = 0;
							break;
						case HorizontalAlign::RIGHT:
							$srcStart->x = $xdiff;
							break;
						case HorizontalAlign::CENTER:
						default:
							$srcStart->x = IntegerHelper::round((float)$xdiff / 2);
					}
				} else {
					$srcSize->y = IntegerHelper::round((float)$originalSize->x / $new_aspect);
					$ydiff = $originalSize->y - $srcSize->y;
					switch ($resizeRequest->verticalAlign) {
						case VerticalAlign::TOP:
							$srcStart->y = 0;
							break;
						case VerticalAlign::BOTTOM:
							$srcStart->y = $ydiff;
							break;
						case VerticalAlign::CENTER:
						default:
							$srcStart->y = IntegerHelper::round((float)$ydiff / 2);
					}
				}
				break;

			case ResizeType::FIT:
			default:
				if ($originalSize->x > $resizeRequest->size->x) {
					$destSize->x = $resizeRequest->size->x;
					$destSize->y = IntegerHelper::round(((float)$originalSize->y / $originalSize->x) * $destSize->x);
				} else {
					$destSize->x = $originalSize->x;
					$destSize->y = $originalSize->y;
				}

				if ($destSize->y > $resizeRequest->size->y) {
					$destSize->y = $resizeRequest->size->y;
					$destSize->x = IntegerHelper::round(((float)$originalSize->x / $originalSize->y) * $destSize->y);
				}
				break;
		}

		$tmp = @imagecreatetruecolor($destSize->x, $destSize->y);

		switch ($targetFormat->extension) {
			case "png":
			case "webp":

				// integer representation of the color black (rgb: 0,0,0)
				$background = @imagecolorallocate($tmp, 0, 0, 0);

				// removing the black from the placeholder
				@imagecolortransparent($tmp, $background);

				// turning off alpha blending (to ensure alpha channel information
				// is preserved, rather than removed (blending with the rest of the
				// image in the form of black))
				@imagealphablending($tmp, false);

				// turning on alpha channel information saving (to ensure the full range
				// of transparency is preserved)
				@imagesavealpha($tmp, true);

				break;
			case "gif":

				// integer representation of the color black (rgb: 0,0,0)
				$background = @imagecolorallocate($tmp, 0, 0, 0);

				// removing the black from the placeholder
				@imagecolortransparent($tmp, $background);

				break;
		}

		@imagecopyresampled(
			$tmp,
			$img,
			0,
			0,
			$srcStart->x,
			$srcStart->y,
			$destSize->x,
			$destSize->y,
			$srcSize->x,
			$srcSize->y
		);

		$image_save_func = $targetFormat->image_save_func;
		$image_save_func($tmp, $resizedPath);

		@imagedestroy($img);
		@imagedestroy($tmp);

		return $resizedPath;
	}

}
