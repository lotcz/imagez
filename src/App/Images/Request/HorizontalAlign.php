<?php

declare(strict_types=1);

namespace App\Images\Request;

class HorizontalAlign {

	public const LEFT = 'left';

	public const CENTER = 'center';

	public const RIGHT = 'right';

	public static function all(): array {
		return [self::LEFT, self::CENTER, self::RIGHT];
	}

	public static function exists(string $align): bool {
		return in_array($align, self::all());
	}
}
