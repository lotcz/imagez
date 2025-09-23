<?php

declare(strict_types=1);

namespace App\Images\Actions;

use App\Application\Actions\ActionError;
use App\Application\Helpers\DownloadHelper;
use App\Application\Helpers\HashHelper;
use App\Application\Helpers\PathHelper;
use App\Application\Helpers\StringHelper;
use App\Images\Info\ImageInfo;
use Psr\Http\Message\ResponseInterface as Response;

class UploadImageFromUrlAction extends ImageAction {

	protected function action(): Response {
		$this->checkSecureToken();

		$urlEncoded = $this->requireQueryParam('url');
		$url = urldecode($urlEncoded);

		$this->logger->info("Downloading from $url");

		$tmpDir = PathHelper::of($this->settings->get('tmpPath'), 'download');
		if (!file_exists($tmpDir)) {
			mkdir($tmpDir, 0777, true);
		}
		$tmpFileName = DownloadHelper::fileNameFromUrl($url);
		$tmpPath = PathHelper::of($tmpDir, $tmpFileName);
		if (file_exists($tmpPath)) {
			unlink($tmpPath);
		}

		if (!DownloadHelper::download($url, $tmpPath)) {
			return $this->respondWithError(
				new ActionError(
					ActionError::BAD_REQUEST,
					"Image could not be downloaded from $url"
				)
			);
		}

		$imageInfo = new ImageInfo($tmpPath);

		/* check if image exists */
		if (!$imageInfo->exists()) {
			return $this->respondWithError(
				new ActionError(
					ActionError::BAD_REQUEST,
					"Something went wrong, file $tmpPath does not exist"
				)
			);
		}

		/* check file size */
		$size = $imageInfo->getFileSize();
		if ($size <= 0) {
			unlink($tmpPath);
			return $this->respondWithError(
				new ActionError(
					ActionError::BAD_REQUEST,
					"Downloaded image $tmpFileName is empty"
				)
			);
		}

		$maxBytes = $this->settings->get('maxImageSizeBytes', 0);
		if ($maxBytes > 0) {
			if ($size > $maxBytes) {
				unlink($tmpPath);
				return $this->respondWithError(
					new ActionError(
						ActionError::BAD_REQUEST,
						"Image size $size exceeds max allowed size $maxBytes"
					)
				);
			}
		}

		/* check mime type/extension */
		if (StringHelper::isBlank($imageInfo->getMimeType()) && StringHelper::isBlank($imageInfo->getExtension())) {
			unlink($tmpPath);
			return $this->respondWithError(
				new ActionError(
					ActionError::BAD_REQUEST,
					"Downloaded image $tmpFileName has neither a mimetype or extension!"
				)
			);
		}

		/* check format */
		$originalFilename = $tmpFileName;
		$originalExtension = PathHelper::getFileExt($originalFilename);

		$imageFormat = $this->formats->findByExtension($originalExtension);
		if ($imageFormat === null) {
			$imageFormat = $this->formats->findByMimeType($imageInfo->getMimeType());
		}

		if ($imageFormat === null) {
			unlink($tmpPath);
			return $this->respondWithError(
				new ActionError(
					ActionError::BAD_REQUEST,
					"Image $tmpFileName is not of a supported type!"
				)
			);
		}

		/* check image dimensions */
		if ($imageInfo->getDimensions()->isZero()) {
			unlink($tmpPath);
			return $this->respondWithError(
				new ActionError(
					ActionError::BAD_REQUEST,
					"Downloaded $tmpFileName image has zero size!"
				)
			);
		}

		/* store if doesn't exist yet*/
		$hash = HashHelper::fileHash($tmpPath);
		$name = $hash . '.' . $imageFormat->extension;

		if ($this->imageStorage->originalExists($name)) {
			unlink($tmpPath);
		} else {
			$path = $this->imageStorage->getOriginalPath($name);
			rename($tmpPath, $path);
		}

		$health = $this->imageStorage->getHealthPayload($name);
		return $this->respondWithData($health);
	}
}
