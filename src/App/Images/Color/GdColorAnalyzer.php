<?php

declare(strict_types=1);

namespace App\Images\Color;

use App\Images\Formats\ImageFormats;
use App\Images\Info\ImageInfo;
use GdImage;
use Psr\Log\LoggerInterface;
use Zavadil\Common\Helpers\IntegerHelper;

class GdColorAnalyzer implements ColorAnalyzer {

	private LoggerInterface $logger;

	private ImageFormats $formats;

	public function __construct(LoggerInterface $logger, ImageFormats $formats) {
		$this->logger = $logger;
		$this->formats = $formats;
	}

	public function guessBackgroundColor(string $path): Color|null {
		$info = new ImageInfo($path);

		$originalFormat = $this->formats->findByMimeType($info->getMimeType());
		if ($originalFormat === null) {
			$originalFormat = $this->formats->findByExtension($info->getExtension());
		}

		if ($originalFormat === null) {
			$this->logger->error("Could not detect original format of image $path");
			return null;
		}

		if ($originalFormat->image_create_func === null) {
			return null;
		}

		$dimensions = $info->getDimensions();

		// Coordinates of the four corners
		$corners = [
			['x' => 0, 'y' => 0],
			['x' => $dimensions->x - 1, 'y' => 0],
			['x' => 0, 'y' => $dimensions->y - 1],
			['x' => $dimensions->x - 1, 'y' => $dimensions->y - 1]
		];

		$totalR = $totalG = $totalB = 0;

		$image_create_func = $originalFormat->image_create_func;
		$img = @$image_create_func($path);

		foreach ($corners as $pos) {
			$rgb = imagecolorat($img, $pos['x'], $pos['y']);

			// Use bit-shifting to extract R, G, B, A channels
			$totalR += ($rgb >> 16) & 0xFF;
			$totalG += ($rgb >> 8) & 0xFF;
			$totalB += $rgb & 0xFF;
		}

		return new Color(
			IntegerHelper::round($totalR / 4),
			IntegerHelper::round($totalG / 4),
			IntegerHelper::round($totalB / 4)
		);
	}

	function removeBackgroundColor(string $path, string|null $hex = null, int|null $threshold = 30): GdImage|null {
		$info = new ImageInfo($path);

		$originalFormat = $this->formats->findByMimeType($info->getMimeType());
		if ($originalFormat === null) {
			$originalFormat = $this->formats->findByExtension($info->getExtension());
		}

		if ($originalFormat === null) {
			$this->logger->error("Could not detect original format of image $path");
			return null;
		}

		if ($originalFormat->image_create_func === null) {
			return null;
		}

		$threshold = empty($threshold) ? 30 : $threshold;
		$color = empty($hex) ? $this->guessBackgroundColor($path) : Color::fromHex($hex);

		if ($color === null) {
			$this->logger->error("Could not determine background color for image $path from hex value $hex");
			return null;
		}

		$dimensions = $info->getDimensions();

		$output = imagecreatetruecolor($dimensions->x, $dimensions->y);
		imagealphablending($output, false);
		imagesavealpha($output, true);

		$transparent = imagecolorallocatealpha($output, 0, 0, 0, 127);

		$image_create_func = $originalFormat->image_create_func;
		$img = @$image_create_func($path);

		try {
			for ($x = 0; $x < $dimensions->x; $x++) {
				for ($y = 0; $y < $dimensions->y; $y++) {
					$rgb = imagecolorat($img, $x, $y);
					$r = ($rgb >> 16) & 0xFF;
					$g = ($rgb >> 8) & 0xFF;
					$b = $rgb & 0xFF;

					// 4. Calculate Euclidean Distance
					$distance = sqrt(
						pow($r - $color->r, 2) +
						pow($g - $color->g, 2) +
						pow($b - $color->b, 2)
					);

					if ($distance <= $threshold) {
						imagesetpixel($output, $x, $y, $transparent);
					} else {
						$alpha = ($rgb >> 24) & 0x7F;
						$original_color = imagecolorallocatealpha($output, $r, $g, $b, $alpha);
						imagesetpixel($output, $x, $y, $original_color);
					}
				}
			}
		} finally {
			imagedestroy($img);
		}

		return $output;
	}
}
