<?php

declare(strict_types=1);

namespace App\Images;

class ResizeType {

	/**
	 * Get original image
	 */
	public const NONE = 'none';

	/**
	 * Simply scale down to not exceed requested dimensions
	 */
	public const SCALE = 'scale';

	/**
	 * Crop to requested dimensions.
	 * Cropped area will be determined by CropPosition
	 */
	public const CROP = 'crop';

	/**
	 * Scale and crop to fit desired dimensions - this is what is usually desired and is the default
	 */
	public const FIT = 'fit';

}
