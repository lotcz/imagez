<?php

declare(strict_types=1);

namespace App\Application\Actions\status;

use App\Application\Actions\GenericAction;
use App\AppVersion;
use Psr\Http\Message\ResponseInterface as Response;

class StatusAction extends GenericAction {

	protected function action(): Response {
		$version = AppVersion::APP_VERSION;
		$this->response->getBody()->write("Imagez: $version");
		return $this->response;
	}
}
