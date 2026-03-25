<?php

declare(strict_types=1);

namespace App\Application\Errors;

use App\Application\Actions\ActionError;
use Exception;

class NotFoundException extends Exception implements HttpException {

	public function getStatusCode(): int {
		return 404;
	}

	public function getErrorType(): string {
		return ActionError::RESOURCE_NOT_FOUND;
	}
}
