<?php

declare(strict_types=1);

namespace App\Application\Errors;

use App\Application\Actions\ActionError;
use Exception;

class ServerErrorException extends Exception implements HttpException {

	public function getStatusCode(): int {
		return 500;
	}

	public function getErrorType(): string {
		return ActionError::SERVER_ERROR;
	}
}
