<?php

declare(strict_types=1);

namespace App\Application\ResponseEmitter;

use Psr\Http\Message\ResponseInterface;
use Slim\ResponseEmitter as SlimResponseEmitter;

class ImageResponseEmitter extends SlimResponseEmitter {
	public function emit(ResponseInterface $response): void {
		// Calculate far future expiry date (1 year)
		$expiryDate = gmdate('D, d M Y H:i:s T', time() + 31536000);

		$response = $response
			->withHeader('Access-Control-Allow-Origin', '*')
			->withHeader('Access-Control-Allow-Methods', 'GET, OPTIONS')
			->withHeader('Cache-Control', 'public, max-age=31536000, immutable')
			->withHeader('Expires', $expiryDate); // old but doesn't hurt

		if (ob_get_contents()) {
			ob_clean();
		}

		parent::emit($response);
	}
}
