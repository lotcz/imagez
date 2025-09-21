<?php

declare(strict_types=1);

namespace App\Images\Actions;

use App\Application\Actions\ActionError;
use Psr\Http\Message\ResponseInterface as Response;

class ViewImageOriginalAction extends ImageAction {

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
