<?php

declare(strict_types=1);

namespace App\Images\Actions;

use App\Application\Actions\ActionError;
use Psr\Http\Message\ResponseInterface as Response;

class UploadImageAction extends ImageAction {

	protected function action(): Response {
		$this->checkSecureToken();

		/** @var \Psr\Http\Message\UploadedFileInterface[] $uploadedFiles */
		$uploadedFiles = $this->request->getUploadedFiles();

		if (empty($uploadedFiles['image'])) {
			return $this->respondWithError(new ActionError(ActionError::BAD_REQUEST, "No image was uploaded"));
		}

		$uploadedFile = $uploadedFiles['image'];

		if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
			return $this->respondWithError(
				new ActionError(
					ActionError::SERVER_ERROR,
					"Error {$uploadedFile->getError()} when uploading image"
				),
				500
			);
		}

		$tmpPath = $uploadedFile->getClientFilename();

		try {
			$info = $this->imageResizer->importImageFile($tmpPath);
			$health = $info->getHealthPayload();
			return $this->respondWithData($health);
		} finally {
			if (file_exists($tmpPath)) {
				unlink($tmpPath);
			}
		}
	}
}
