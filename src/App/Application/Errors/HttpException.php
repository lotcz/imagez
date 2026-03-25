<?php

declare(strict_types=1);

namespace App\Application\Errors;

interface HttpException extends \Throwable {

	public function getStatusCode(): int;

	public function getErrorType(): string;
}
