<?php

declare(strict_types=1);

namespace App\Images\Request;

class ResizeType {

	/**
	 * Simply scale down - this will change aspect ratio!
	 */
	public const SCALE = 'scale';

	/**
	 * Scale and crop to match exactly the requested dimensions.
	 * Crop position area will be determined by CropPosition
	 */
	public const CROP = 'crop';

	/**
	 * Scale to fit desired dimensions without changing aspect ratio - this is the default
	 */
	public const FIT = 'fit';

	public static function all(): array {
		return [self::SCALE, self::CROP, self::FIT];
	}

	public static function exists(string $type): bool {
		return in_array($type, self::all());
	}
}
