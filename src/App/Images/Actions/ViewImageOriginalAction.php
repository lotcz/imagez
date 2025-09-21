<?php

declare(strict_types=1);

namespace App\Images\Actions;

use App\Application\Actions\ActionError;
use Psr\Http\Message\ResponseInterface as Response;

class ViewImageOriginalAction extends ImageAction {

	protected function action(): Response {
		$name = $this->resolveArg('name');

		if (!$this->imageStorage->originalExists($name)) {
			return $this->respondWithError(
				new ActionError(
					ActionError::RESOURCE_NOT_FOUND,
					"Image $name not found"
				),
				404
			);
		}

		$path = $this->imageStorage->getOriginalPath($name);
		return $this->respondWithImage($path, $name);
	}
}
