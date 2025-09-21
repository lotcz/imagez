<?php

declare(strict_types=1);

namespace App\Images\Actions;

use App\Application\Actions\ActionError;
use App\Application\Helpers\PathHelper;
use Psr\Http\Message\ResponseInterface as Response;

class UploadImageAction extends ImageAction {

	protected function action(): Response {
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

		/* check file size */
		$maxBytes = $this->settings->get('maxImageSizeBytes', 0);
		if ($maxBytes > 0) {
			$size = $uploadedFile->getSize();
			if (is_numeric($size) && $size > $maxBytes) {
				return $this->respondWithError(
					new ActionError(
						ActionError::BAD_REQUEST,
						"Image size $size exceeds max allowed size $maxBytes"
					)
				);
			}
		}

		$originalFilename = $uploadedFile->getClientFilename();
		$originalExtension = PathHelper::getFileExt($originalFilename);
		$basename = bin2hex(random_bytes(12));
		$filename = $basename . '.' . $originalExtension;
		$path = $this->imageStorage->getOriginalPath($filename);
		$uploadedFile->moveTo($path);

		return $this->respondWithData(
			[
				'name' => $filename
			]
		);
	}
}
