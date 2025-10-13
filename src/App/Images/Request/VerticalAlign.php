<?php

declare(strict_types=1);

namespace App\Images\Request;

class VerticalAlign {

	public const TOP = 'top';

	public const CENTER = 'center';

	public const BOTTOM = 'bottom';

	public static function all(): array {
		return [self::TOP, self::CENTER, self::BOTTOM];
	}

	public static function exists(string $align): bool {
		return in_array($align, self::all());
	}
}
