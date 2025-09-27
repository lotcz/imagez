<?php

declare(strict_types=1);

namespace App\Images\Actions;

use App\Application\Actions\ActionError;
use Psr\Http\Message\ResponseInterface as Response;
use Zavadil\Common\Helpers\DownloadHelper;
use Zavadil\Common\Helpers\PathHelper;

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
