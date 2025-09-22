<?php

declare(strict_types=1);

namespace App\Application\Actions;

use Psr\Http\Message\ResponseInterface as Response;

class StatusAction extends Action {

	protected function action(): Response {
		$this->response->getBody()->write("Imagez: {$this->settings->get('version')}");
		return $this->response;
	}
}
