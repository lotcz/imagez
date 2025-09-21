<?php

declare(strict_types=1);

namespace App\Images\Actions;

use App\Application\Actions\ActionError;
use App\Images\ImageRequest;
use Psr\Http\Message\ResponseInterface as Response;

class ViewImageResizedAction extends ImageAction {

	protected function action(): Response {
		$name = $this->resolveArg('name');

		if (!$this->imageStorage->originalExists($name)) {
			return $this->respondWithError(
				new ActionError(
					ActionError::RESOURCE_NOT_FOUND,
					"Image $name not found"
				),
				500
			);
		}

		$path = $this->imageResizer->resize(new ImageRequest($name, 100, 100));
		return $this->respondWithImage($path, $name);
	}
}
