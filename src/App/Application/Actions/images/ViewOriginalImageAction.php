<?php

declare(strict_types=1);

namespace App\Application\Actions\images;

use App\Application\Actions\ActionError;
use App\Application\Actions\GenericImageAction;
use Psr\Http\Message\ResponseInterface as Response;

class ViewOriginalImageAction extends GenericImageAction {

	protected function action(): Response {
		$name = $this->requireArg('name');
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

		return $this->respondWithImage($path, $name);
	}
}
