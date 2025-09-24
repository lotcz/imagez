<?php

declare(strict_types=1);

namespace App\Application\Helpers;

class PathHelper {

	public static function trimSlashes(?string $str): string {
		return StringHelper::trim($str, '/');
	}

	public static function ofParts(array $parts): string {
		if (empty($parts)) return '';
		$trimmed = [];
		foreach ($parts as $part) {
			$str = PathHelper::trimSlashes($part);
			if (!StringHelper::isBlank($str)) {
				$trimmed[] = $str;
			}
		}
		$imploded = implode("/", $trimmed);
		return str_starts_with($parts[0], "/") ? "/" . $imploded : $imploded;
	}

	public static function of(...$parts): string {
		if (empty($parts)) return '';
		$strings = [];
		foreach ($parts as $part) {
			if (!empty($part)) {
				$strings[] = strval($part);
			}
		}
		return PathHelper::ofParts($strings);
	}

	public static function getFileName(?string $path): ?string {
		if (StringHelper::isBlank($path)) return null;
		return basename($path);
	}

	public static function getFileExt(?string $fileName): ?string {
		if (StringHelper::isBlank($fileName)) return null;
		return pathinfo($fileName, PATHINFO_EXTENSION);
	}

	public static function getFileBase(?string $fileName): ?string {
		if (StringHelper::isBlank($fileName)) return null;
		return pathinfo($fileName, PATHINFO_FILENAME);
	}

}
