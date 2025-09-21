<?php

declare(strict_types=1);

namespace App\Application\Helpers;

class StringHelper {

	public static function isBlank(?string $str): bool {
		if ($str === null) return true;
		return (strlen(StringHelper::trim($str)) === 0);
	}

	public static function trim(?string $str, ?string $characters = null): string {
		if ($str === null) return '';
		return $characters === null ? trim($str) : trim($str, $characters);
	}

}
