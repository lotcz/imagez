<?php

declare(strict_types=1);

namespace App\Application\Actions\upload;

use App\Application\Actions\GenericAction;
use Psr\Http\Message\ResponseInterface as Response;

class UploadAction extends GenericAction {

	protected function action(): Response {
		$this->response->getBody()->write(file_get_contents(__DIR__ . '/upload.html'));
		return $this->response;
	}
}
