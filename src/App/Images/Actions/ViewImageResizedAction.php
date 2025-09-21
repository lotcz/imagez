<?php

declare(strict_types=1);

namespace App\Images\Actions;

use App\Application\Actions\ActionError;
use App\Images\Info\ImageSize;
use App\Images\Request\CropPosition;
use App\Images\Request\ResizeRequest;
use App\Images\Request\ResizeType;
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
				500
			);
		}

		$imageRequest = new ResizeRequest(
			$name,
			new ImageSize(
				$this->requireIntQueryParam('width'),
				$this->requireIntQueryParam('height')
			),
			$this->getQueryParam('type', ResizeType::FIT),
			$this->getQueryParam('horiz', CropPosition::CENTER),
			$this->getQueryParam('vert', CropPosition::CENTER),
			$this->getQueryParam('ext')
		);

		$path = $this->imageResizer->getResizedImagePath($originalPath, $imageRequest);
		return $this->respondWithImage($path, $name);
	}
}
