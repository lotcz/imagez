<?php

declare(strict_types=1);

namespace App\Application\Actions\health;

use App\Application\Actions\ActionError;
use App\Application\Actions\GenericImageAction;
use App\Images\Info\ImageInfo;
use Psr\Http\Message\ResponseInterface as Response;

class ViewHealthImageAction extends GenericImageAction {

	protected function action(): Response {
		$name = $this->requireArg('name');
		$path = $this->imageStorage->getOriginalPath($name);
		$info = new ImageInfo($path);

		if (!$info->exists()) {
			return $this->respondWithError(
				new ActionError(
					ActionError::RESOURCE_NOT_FOUND,
					"Image $name not found"
				),
				404
			);
		}

		$health = $info->getHealthPayload($name);
		return $this->respondWithData($health);
	}
}
