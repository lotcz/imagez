<?php

declare(strict_types=1);

namespace App\Images\Resizer;

use App\Application\Helpers\StringHelper;
use App\Images\Formats\ImageFormats;
use App\Images\Info\ImageDimensions;
use App\Images\Info\ImageInfo;
use App\Images\Request\ResizeRequest;
use App\Images\Request\ResizeType;
use App\Images\Storage\ImageStorage;
use Psr\Log\LoggerInterface;
use Throwable;

class GdImageResizer implements ImageResizer {

	private ImageStorage $imageStorage;

	private LoggerInterface $logger;

	private ImageFormats $formats;

	public function __construct(LoggerInterface $logger, ImageStorage $storage) {
		$this->logger = $logger;
		$this->imageStorage = $storage;
		$this->formats = new ImageFormats();
	}

	public function getResizedImagePath(string $originalPath, ResizeRequest $imageRequest): string {
		$resizedPath = $this->imageStorage->getResizedPath($imageRequest);
		if (!$this->imageStorage->fileExists($resizedPath)) {
			$this->prepareResizedImage($originalPath, $resizedPath, $imageRequest);
		}
		return $resizedPath;
	}

	private function prepareResizedImage(string $originalPath, string $resizedPath, ResizeRequest $resizeRequest) {
		$info = new ImageInfo($originalPath);

		$originalSize = $info->getDimensions();
		if ($originalSize->isZero()) {
			$this->logger->error("Image $originalPath has zero size!");
			return;
		}

		$originalFormat = $this->formats->findByMimeType($info->getMimeType());
		if ($originalFormat === null) {
			$originalFormat = $this->formats->findByExtension($info->getExtension());
		}

		if ($originalFormat === null) {
			$this->logger->error("Could not detect original format of image $originalPath");
			return;
		}

		$targetFormat = StringHelper::isBlank($resizeRequest->imageExt)
			? $originalFormat
			: $this->formats->findByExtension($resizeRequest->imageExt);
		if ($targetFormat === null) {
			$this->logger->error("Could not detect target format $resizeRequest->imageExt for image $originalPath");
			return;
		}

		$formatDesc = $resizeRequest->getResizedPath();
		$this->logger->info("Resizing image {$resizeRequest->name} to $formatDesc");

		try {
			$image_create_func = $originalFormat->image_create_func;
			$img = @$image_create_func($originalPath);
		} catch (Throwable $e) {
			$message = sprintf('Error when resizing %s to format %s: %s', $originalPath, $formatDesc, $e->getMessage());
			$this->logger->error($message);
			return;
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

				// todo: position of crop from request

				if ($original_aspect > $new_aspect) {
					$srcSize->x = intval(round((float)$originalSize->y * $new_aspect));
					$srcStart->x = intval(round((float)($originalSize->x - $srcSize->x) / 2));
				} else {
					$srcSize->y = intval(round((float)$originalSize->x / $new_aspect));
					$srcStart->y = intval(round((float)($originalSize->y - $srcSize->y) / 2));
				}

				break;

			case ResizeType::FIT:
			default:
				if ($originalSize->x > $resizeRequest->size->x) {
					$destSize->x = $resizeRequest->size->x;
					$destSize->y = intval(round((float)($originalSize->y / $originalSize->x) * $destSize->x));
				} else {
					$destSize->x = $originalSize->x;
					$destSize->y = $originalSize->y;
				}

				if ($destSize->y > $resizeRequest->size->y) {
					$destSize->y = $resizeRequest->size->y;
					$destSize->x = intval(round((float)($originalSize->x / $originalSize->y) * $destSize->y));
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
	}
}
