<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\AppVersion;
use Psr\Http\Message\ResponseInterface as Response;

class StatusAction extends Action {

	protected function action(): Response {
		$version = AppVersion::APP_VERSION;
		$this->response->getBody()->write("Imagez: $version");
		return $this->response;
	}
}
