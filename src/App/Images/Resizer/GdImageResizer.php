<?php

declare(strict_types=1);

namespace App\Images\Resizer;

use App\Application\Errors\BadRequestException;
use App\Images\Formats\ImageFormats;
use App\Images\Info\ImageDimensions;
use App\Images\Info\ImageInfo;
use App\Images\Request\ResizeRequest;
use App\Images\Request\ResizeType;
use App\Images\Storage\ImageStorage;
use Psr\Log\LoggerInterface;
use Throwable;
use Zavadil\Common\Helpers\HashHelper;
use Zavadil\Common\Helpers\PathHelper;
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

	public function importImageFile(string $tmpPath): ImageInfo {
		$imageInfo = new ImageInfo($tmpPath);
		$tmpFileName = $imageInfo->getFileName();

		/* check if image exists */
		if (!$imageInfo->exists()) {
			throw new BadRequestException("Something went wrong, file $tmpPath does not exist");
		}

		/* check file size */
		$size = $imageInfo->getFileSize();
		if ($size <= 0) {
			throw new BadRequestException("Downloaded image $tmpFileName is empty");
		}

		$maxBytes = $this->settings->get('maxImageSizeBytes', 0);
		if ($maxBytes > 0 && $size > $maxBytes) {
			throw new BadRequestException("Image size $size of $tmpFileName exceeds max allowed size $maxBytes");
		}

		/* check mime type/extension */
		if (StringHelper::isBlank($imageInfo->getMimeType()) && StringHelper::isBlank($imageInfo->getExtension())) {
			throw new BadRequestException("Downloaded image $tmpFileName has neither a mimetype or extension!");
		}

		/* check format */
		$originalFilename = $tmpFileName;
		$originalExtension = PathHelper::getFileExt($originalFilename);

		$imageFormat = $this->formats->findByExtension($originalExtension);
		if ($imageFormat === null) {
			$imageFormat = $this->formats->findByMimeType($imageInfo->getMimeType());
		}

		if ($imageFormat === null) {
			throw new BadRequestException("Image $tmpFileName is not of a supported type!");
		}

		/* check image dimensions */
		if ($imageInfo->getDimensions()->isZero()) {
			throw new BadRequestException("Downloaded $tmpFileName image has zero size!");
		}

		/* store if doesn't exist yet */
		$hash = HashHelper::fileHash($tmpPath);
		$name = $hash . '.' . $imageFormat->extension;

		$path = $this->imageStorage->getOriginalPath($name);

		if ($this->imageStorage->fileExists($path)) {
			$this->logger->info("File $name already exists, keeping only the original file");
			unlink($tmpPath);
		} else {
			rename($tmpPath, $path);
		}

		return new ImageInfo($path);
	}
}
