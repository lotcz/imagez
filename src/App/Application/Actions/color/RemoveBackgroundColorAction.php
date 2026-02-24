<?php

declare(strict_types=1);

namespace App\Application\Actions\color;

use App\Application\Actions\ActionError;
use App\Application\Actions\GenericImageAction;
use App\Application\Errors\ForbiddenException;
use App\Images\Color\ColorAnalyzer;
use App\Images\Formats\ImageFormats;
use App\Images\Storage\ImageStorage;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Stream;
use Zavadil\Common\Helpers\HashHelper;
use Zavadil\Common\Helpers\IntegerHelper;
use Zavadil\Common\Helpers\PathHelper;
use Zavadil\Common\Helpers\StringHelper;
use Zavadil\Common\Settings\Settings;

class RemoveBackgroundColorAction extends GenericImageAction {

	protected ColorAnalyzer $colorAnalyzer;

	public function __construct(
		LoggerInterface $logger,
		Settings $settings,
		ImageFormats $formats,
		ImageStorage $imageStorage,
		ColorAnalyzer $colorAnalyzer
	) {
		parent::__construct($logger, $settings, $formats, $imageStorage);
		$this->colorAnalyzer = $colorAnalyzer;
	}

	protected function action(): Response {
		$hex = $this->getQueryParam('hex', '');
		$threshold = IntegerHelper::parse($this->getQueryParam('threshold', ''));
		$name = $this->requireArg('name');

		$secretToken = $this->settings->get('secretToken');
		// validate token if set
		if (StringHelper::notBlank($secretToken)) {
			$userToken = StringHelper::lowercase($this->requireQueryParam('token'));
			$rawToken = "$secretToken-$name";
			if (!empty($hex)) $rawToken .= "-$hex";
			if (!empty($threshold)) $rawToken .= "-$threshold";
			$hash = HashHelper::crc32hex($rawToken);
			if ($this->settings->get('debugMode')) {
				$this->logger->info("Hash for $rawToken: $hash");
			}
			if ($hash !== $userToken) {
				throw new ForbiddenException("Verification token invalid");
			}
		}

		$path = $this->imageStorage->getOriginalPath($name);

		if (!$this->imageStorage->fileExists($path)) {
			return $this->respondWithError(
				new ActionError(
					ActionError::RESOURCE_NOT_FOUND,
					"Image $name not found"
				),
				404
			);
		}

		$img = $this->colorAnalyzer->removeBackgroundColor($path, $hex, $threshold);

		if (empty($img)) {
			return $this->respondWithImage($path, $name);
		}

		$tempStream = fopen('php://temp', 'r+');
		imagepng($img, $tempStream);
		$size = ftell($tempStream);
		rewind($tempStream);
		imagedestroy($img);

		$filename = PathHelper::getFileBase($name) . '.png';

		return $this->response
			->withStatus(200)
			->withHeader('Content-Type', 'image/png')
			->withHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
			->withHeader('Content-Length', (string)$size)
			->withBody(new Stream($tempStream));
	}
}
