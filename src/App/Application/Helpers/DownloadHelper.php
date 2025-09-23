<?php

declare(strict_types=1);

namespace App\Application\Helpers;

use App\Application\Errors\BadRequestException;

class DownloadHelper {

	public static function fileNameFromUrl(string $url): ?string {
		$path = parse_url($url, PHP_URL_PATH);
		if (!$path) {
			return null;
		}
		return basename($path);
	}

	public static function download(string $url, string $path): bool {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
		curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout in seconds
		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			[
				"User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:143.0) Gecko/20100101 Firefox/143.0",
				"Accept: image/*"
			]
		);

		$data = curl_exec($ch);

		if ($data === false) {
			curl_close($ch);
			return false;
		}

		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($httpCode !== 200) {
			throw new BadRequestException("Could not download file from $url. Response code: $httpCode");
		}

		return file_put_contents($path, $data) !== false;
	}

}
