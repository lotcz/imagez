<?php

declare(strict_types=1);

namespace App\Images\Actions;

use App\Application\Actions\Action;
use App\Application\Actions\ActionError;
use App\Application\Settings\Settings;
use App\Images\Resizer\ImageResizer;
use App\Images\Storage\ImageStorage;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Stream;

abstract class ImageAction extends Action {

	protected ImageResizer $imageResizer;

	protected ImageStorage $imageStorage;

	public function __construct(LoggerInterface $logger, Settings $settings, ImageResizer $imageResizer, ImageStorage $imageStorage) {
		parent::__construct($logger, $settings);
		$this->imageResizer = $imageResizer;
		$this->imageStorage = $imageStorage;
	}

	protected function respondWithImage(string $path, string $name, int $statusCode = 200): Response {
		if (!file_exists($path)) {
			return $this->respondWithError(
				new ActionError(
					ActionError::RESOURCE_NOT_FOUND,
					"Image file on path $path not found"
				),
				404
			);
		}

		$info = finfo_open(FILEINFO_MIME_TYPE);
		$mimeType = finfo_file($info, $path);
		finfo_close($info);

		$stream = new Stream(fopen($path, 'rb'));

		return $this->response
			->withBody($stream)
			->withStatus($statusCode)
			->withHeader('Content-Type', $mimeType)
			->withHeader('Content-Disposition', 'inline; filename="' . basename($name) . '"')
			->withHeader('Content-Length', filesize($path));
	}
	
}
