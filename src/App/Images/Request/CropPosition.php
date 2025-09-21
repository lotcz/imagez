<?php

declare(strict_types=1);

namespace App\Images\Request;

class CropPosition {

	/**
	 * Crop from left or top
	 */
	public const START = 'start';

	/**
	 * Crop from right or bottom
	 */
	public const END = 'end';

	/**
	 * Crop from the center
	 */
	public const CENTER = 'center';

}
