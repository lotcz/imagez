<?php

declare(strict_types=1);

namespace App\Application\Actions;

use JsonSerializable;

class ActionError implements JsonSerializable {
	public const BAD_REQUEST = 'BAD_REQUEST';
	public const INSUFFICIENT_PRIVILEGES = 'INSUFFICIENT_PRIVILEGES';
	public const NOT_ALLOWED = 'NOT_ALLOWED';
	public const NOT_IMPLEMENTED = 'NOT_IMPLEMENTED';
	public const RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
	public const SERVER_ERROR = 'SERVER_ERROR';
	public const UNAUTHENTICATED = 'UNAUTHENTICATED';
	public const VALIDATION_ERROR = 'VALIDATION_ERROR';
	public const VERIFICATION_ERROR = 'VERIFICATION_ERROR';

	private string $type;

	private ?string $message;

	public function __construct(string $type, ?string $description = null) {
		$this->type = $type;
		$this->message = $description;
	}

	public function getType(): string {
		return $this->type;
	}

	public function setType(string $type): self {
		$this->type = $type;
		return $this;
	}

	public function getMessage(): ?string {
		return $this->message;
	}

	public function setMessage(?string $message = null): self {
		$this->message = $message;
		return $this;
	}

	#[\ReturnTypeWillChange]
	public function jsonSerialize(): array {
		return [
			'type' => $this->type,
			'message' => $this->message,
		];
	}
}
