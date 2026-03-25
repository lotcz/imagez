<?php

declare(strict_types=1);

namespace App\Application\Actions\images;

use App\Application\Actions\ActionError;
use App\Application\Actions\GenericImageAction;
use App\Application\Errors\ForbiddenException;
use App\Images\Formats\ImageFormats;
use App\Images\Info\ImageDimensions;
use App\Images\Request\ResizeRequest;
use App\Images\Resizer\ImageResizer;
use App\Images\Storage\ImageStorage;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Zavadil\Common\Helpers\HashHelper;
use Zavadil\Common\Helpers\StringHelper;
use Zavadil\Common\Settings\Settings;

class ViewResizedImageAction extends GenericImageAction {

	protected ImageResizer $imageResizer;

	public function __construct(
		LoggerInterface $logger,
		Settings $settings,
		ImageFormats $formats,
		ImageResizer $imageResizer,
		ImageStorage $imageStorage
	) {
		parent::__construct($logger, $settings, $formats, $imageStorage);
		$this->imageResizer = $imageResizer;
	}

	protected function action(): Response {
		$name = $this->requireArg('name');
		$originalPath = $this->imageStorage->getOriginalPath($name);

		if (!$this->imageStorage->fileExists($originalPath)) {
			return $this->respondWithError(
				new ActionError(
					ActionError::RESOURCE_NOT_FOUND,
					"Image $name not found"
				),
				404
			);
		}

		$resizeRequest = new ResizeRequest(
			$name,
			new ImageDimensions(
				$this->requireIntQueryParam('width'),
				$this->requireIntQueryParam('height')
			),
			$this->requireQueryParam('type'),
			$this->getQueryParam('ext'),
			$this->getQueryParam('v'),
			$this->getQueryParam('h'),
			$this->getIntQueryParam('page')
		);

		$secretToken = $this->settings->get('secretToken');
		// validate token if set
		if (StringHelper::notBlank($secretToken)) {
			$userToken = StringHelper::lowercase($this->requireQueryParam('token'));
			$rawToken = $resizeRequest->getVerificationTokenRawValue($secretToken);
			$hash = HashHelper::crc32hex($rawToken);
			if ($this->settings->get('debugMode')) {
				$this->logger->info("Hash for $name: $hash");
			}
			if ($hash !== $userToken) {
				throw new ForbiddenException("Verification token invalid");
			}
		}

		// set extension if not explicitly requested and default settings exists
		if (StringHelper::isBlank($resizeRequest->imageExt)
			&& StringHelper::notBlank($this->settings->get('defaultResizedExt'))) {
			$resizeRequest->imageExt = $this->settings->get('defaultResizedExt');
		}

		$path = $this->imageResizer->getResizedImagePath($originalPath, $resizeRequest);
		return $this->respondWithImage($path, $name);
	}
}
