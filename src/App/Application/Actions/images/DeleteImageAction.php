<?php

declare(strict_types=1);

namespace App\Application\Actions\images;

use App\Application\Actions\ActionError;
use App\Application\Actions\GenericImageAction;
use Psr\Http\Message\ResponseInterface as Response;

class DeleteImageAction extends GenericImageAction {

	protected function action(): Response {
		$this->checkSecureToken();
		$name = $this->requireArg('name');
		if (!$this->imageStorage->originalExists($name)) {
			return $this->respondWithError(
				new ActionError(
					ActionError::RESOURCE_NOT_FOUND,
					"Image $name does not exist"
				),
				404
			);
		}
		$this->imageStorage->deleteOriginal($name);
		return $this->respondWithData(['message' => "Image $name deleted"]);
	}
}
