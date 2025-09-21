<?php

declare(strict_types=1);

namespace App\Images\Resizer;

use App\Images\ImageRequest;
use App\Images\ResizeType;
use App\Images\Storage\ImageStorage;
use Psr\Log\LoggerInterface;
use Throwable;

class GdImageResizer implements ImageResizer {

	private ImageStorage $imageStorage;

	private LoggerInterface $logger;

	public function __construct(LoggerInterface $logger, ImageStorage $storage) {
		$this->logger = $logger;
		$this->imageStorage = $storage;
	}

	public function getResizedImagePath(string $originalPath, ImageRequest $imageRequest): string {
		$resizedPath = $this->imageStorage->getResizedPath($imageRequest);
		if (!$this->imageStorage->fileExists($resizedPath)) {
			$this->prepareResizedImage($originalPath, $resizedPath, $imageRequest);
		}
		return $resizedPath;
	}

	private function prepareResizedImage(string $originalPath, string $resizedPath, ImageRequest $imageRequest) {
		$info = @getimagesize($originalPath);
		if (empty($info)) {
			$this->logger->error("Image $originalPath has no info");
			return;
		}
		if (!(isset($info[0]) && isset($info[1]) && isset($info['mime']))) {
			$this->logger->error(sprintf('Image %s has incomplete info: %s.', $originalPath, print_r($info, true)));
			return;
		}

		$mime = $info['mime'];

		switch ($mime) {
			case 'image/png':
				$image_create_func = 'imagecreatefrompng';
				$image_save_func = 'imagepng';
				$new_image_ext = 'png';
				break;

			case 'image/gif':
				$image_create_func = 'imagecreatefromgif';
				$image_save_func = 'imagegif';
				$new_image_ext = 'gif';
				break;

			case 'image/webp':
				$image_create_func = 'imagecreatefromwebp';
				$image_save_func = 'imagewebp';
				$new_image_ext = 'webp';
				break;

			default: //case 'image/jpeg':
				$image_create_func = 'imagecreatefromjpeg';
				$image_save_func = 'imagejpeg';
				$new_image_ext = 'jpg';
				break;
		}

		$formatDesc = $imageRequest->getResizedDirName();
		$this->logger->info("Resizing image $originalPath to $formatDesc");

		try {
			$img = @$image_create_func($originalPath);
		} catch (Throwable $e) {
			$message = sprintf('Error when resizing %s to format %s: %s', $originalPath, $formatDesc, $e->getMessage());
			$this->logger->error($message);
			return;
		}

		$originalWidth = intval($info[0]);
		$originalHeight = intval($info[1]);

		$src_x = 0;
		$src_y = 0;
		$src_width = $originalWidth;
		$src_height = $originalHeight;

		switch ($imageRequest->resizeType) {
			case ResizeType::SCALE:
				$newWidth = $imageRequest->maxWidth;
				$newHeight = $imageRequest->maxHeight;
				break;

			case ResizeType::CROP:
				$original_aspect = $originalWidth / $originalHeight;
				$new_aspect = $imageRequest->maxWidth / $imageRequest->maxHeight;

				if ($original_aspect > $new_aspect) {
					$src_width = $originalHeight * $new_aspect;
					$src_x = ($originalWidth - $src_width) / 2;
				} else {
					$src_height = $originalWidth / $new_aspect;
					$src_y = ($originalHeight - $src_height) / 2;
				}

				$newWidth = $imageRequest->maxWidth;
				$newHeight = $imageRequest->maxHeight;

				break;

			case ResizeType::FIT:
			default:
				if ($originalWidth > $imageRequest->maxWidth) {
					$newWidth = $imageRequest->maxWidth;
					$newHeight = ($originalHeight / $originalWidth) * $imageRequest->maxHeight;
				} else {
					$newWidth = $originalWidth;
					$newHeight = $originalHeight;
				}

				if ($newHeight > $imageRequest->maxHeight) {
					$newWidth = ($newWidth / $newHeight) * $imageRequest->maxHeight;
					$newHeight = $imageRequest->maxHeight;
				}
				break;
		}

		$tmp = @imagecreatetruecolor(intval(round($newWidth)), intval(round($newHeight)));

		switch ($new_image_ext) {
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
			intval($src_x),
			intval($src_y),
			intval(round($newWidth)),
			intval(round($newHeight)),
			intval($src_width),
			intval($src_height)
		);

		$image_save_func($tmp, $resizedPath);

		@imagedestroy($img);
		@imagedestroy($tmp);
	}
}
