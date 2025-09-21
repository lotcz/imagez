<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Settings\Settings;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Psr7\Stream;

abstract class Action {

	protected LoggerInterface $logger;

	protected Settings $settings;

	protected Request $request;

	protected Response $response;

	protected array $args;

	protected array $params;

	public function __construct(LoggerInterface $logger, Settings $settings) {
		$this->logger = $logger;
		$this->settings = $settings;
	}

	public function __invoke(Request $request, Response $response, array $args): Response {
		$this->request = $request;
		$this->response = $response;
		$this->args = $args;
		$this->params = $request->getQueryParams();

		return $this->action();
	}

	abstract protected function action(): Response;

	protected function getFormData() {
		return $this->request->getParsedBody();
	}

	protected function requireArg(string $name): string {
		if (!isset($this->args[$name])) {
			throw new HttpBadRequestException($this->request, "Could not resolve argument `{$name}`.");
		}
		return $this->args[$name];
	}

	protected function getArg(string $name, string $default): string {
		if (!isset($this->args[$name])) {
			return $default;
		}
		return $this->args[$name];
	}

	protected function getIntArg(string $name, int $default): int {
		if (!isset($this->args[$name])) {
			return $default;
		}
		return intval($this->args[$name]);
	}

	protected function requireQueryParam(string $name): string {
		if (!isset($this->params[$name])) {
			throw new HttpBadRequestException($this->request, "Could not resolve query parameter `{$name}`.");
		}
		return $this->params[$name];
	}

	protected function getQueryParam(string $name, string $default): string {
		if (!isset($this->params[$name])) {
			return $default;
		}
		return $this->params[$name];
	}

	protected function requireIntQueryParam(string $name): int {
		if (!isset($this->params[$name])) {
			throw new HttpBadRequestException($this->request, "Could not resolve query parameter `{$name}`.");
		}
		return intval($this->params[$name]);
	}

	protected function getIntQueryParam(string $name, int $default): int {
		if (!isset($this->params[$name])) {
			return $default;
		}
		return intval($this->params[$name]);
	}

	protected function respondWithImage(string $path, string $name, int $statusCode = 200): Response {
		if (!file_exists($path)) {
			return $this->respondWithError(
				new ActionError(
					ActionError::RESOURCE_NOT_FOUND,
					"Image file on path $path not found"
				),
				500
			);
		}

		$info = finfo_open(FILEINFO_MIME_TYPE);
		$mimeType = finfo_file($info, $path);
		finfo_close($info);

		$stream = new Stream(fopen($path, 'rb'));

		return $this->response
			->withBody($stream)
			->withStatus($statusCode)
			->withHeader('Content-Type', $mimeType)
			->withHeader('Content-Disposition', 'inline; filename="' . basename($name) . '"')
			->withHeader('Content-Length', filesize($path));
	}

	protected function respondWithData($data = null, int $statusCode = 200): Response {
		$payload = new ActionPayload($statusCode, $data);
		return $this->respond($payload);
	}

	protected function respondWithError(ActionError $error, int $statusCode = 400): Response {
		$payload = new ActionPayload($statusCode, null, $error);
		return $this->respond($payload);
	}

	protected function respond(ActionPayload $payload): Response {
		$json = json_encode($payload, JSON_PRETTY_PRINT);
		$this->response->getBody()->write($json);

		return $this->response
			->withHeader('Content-Type', 'application/json')
			->withStatus($payload->getStatusCode());
	}
}
