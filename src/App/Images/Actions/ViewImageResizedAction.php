<?php

declare(strict_types=1);

namespace App\Images\Actions;

use App\Application\Actions\ActionError;
use App\Application\Errors\ForbiddenException;
use App\Application\Helpers\HashHelper;
use App\Application\Helpers\StringHelper;
use App\Images\Info\ImageDimensions;
use App\Images\Request\ResizeRequest;
use Psr\Http\Message\ResponseInterface as Response;

class ViewImageResizedAction extends ImageAction {

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
			$this->getQueryParam('ext')
		);

		$secretToken = $this->settings->get('secretToken');
		// validate token if set
		if (StringHelper::notBlank($secretToken)) {
			$userToken = strtolower($this->requireQueryParam('token'));
			$rawToken = $resizeRequest->getVerificationTokenRawValue($secretToken);
			$hash = HashHelper::crc32hex($rawToken);
			if ($this->settings->get('debugMode')) {
				$this->logger->info("Hash for $name: $hash");
			}
			if ($hash !== $userToken) {
				throw new ForbiddenException("Verification token invalid");
			}
		}

		// set default extension if not explicitly requested and settings exists
		if (StringHelper::isBlank($resizeRequest->imageExt)
			&& StringHelper::notBlank($this->settings->get('defaultResizedExt'))) {
			$resizeRequest->imageExt = $this->settings->get('defaultResizedExt');
		}

		$path = $this->imageResizer->getResizedImagePath($originalPath, $resizeRequest);
		return $this->respondWithImage($path, $name);
	}
}
