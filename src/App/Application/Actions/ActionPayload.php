<?php

declare(strict_types=1);

namespace App\Application\Actions;

use JsonSerializable;
use Zavadil\Common\Helpers\JsonHelper;

class ActionPayload implements JsonSerializable {

	private int $statusCode;

	private $data;

	private ?ActionError $error;

	public function __construct(
		int $statusCode = 200,
		$data = null,
		?ActionError $error = null
	) {
		$this->statusCode = $statusCode;
		$this->data = $data;
		$this->error = $error;
	}

	public function getStatusCode(): int {
		return $this->statusCode;
	}

	public function getData() {
		return $this->data;
	}

	public function getError(): ?ActionError {
		return $this->error;
	}

	#[\ReturnTypeWillChange]
	public function jsonSerialize(): mixed {
		return ($this->data !== null) ? JsonHelper::normalizeForJson($this->data) : $this->error->jsonSerialize();
	}
}
