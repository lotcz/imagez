<?php

declare(strict_types=1);

namespace App\Application\Errors;

use App\Application\Actions\ActionError;
use Exception;

class BadRequestException extends Exception implements HttpException {

	public function getStatusCode(): int {
		return 400;
	}

	public function getErrorType(): string {
		return ActionError::BAD_REQUEST;
	}
}
