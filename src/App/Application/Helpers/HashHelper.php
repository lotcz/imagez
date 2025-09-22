<?php

declare(strict_types=1);

namespace App\Application\Helpers;

class HashHelper {

	public static function crc32hex(string $str) {
		return dechex(crc32($str));
	}

}
