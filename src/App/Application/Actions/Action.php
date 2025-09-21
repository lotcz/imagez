<?php

declare(strict_types=1);

namespace App\Application\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Psr7\Stream;

abstract class Action {

	protected LoggerInterface $logger;

	protected Request $request;

	protected Response $response;

	protected array $args;

	public function __construct(LoggerInterface $logger) {
		$this->logger = $logger;
	}

	public function __invoke(Request $request, Response $response, array $args): Response {
		$this->request = $request;
		$this->response = $response;
		$this->args = $args;

		return $this->action();
	}

	abstract protected function action(): Response;

	protected function getFormData() {
		return $this->request->getParsedBody();
	}

	protected function resolveArg(string $name) {
		if (!isset($this->args[$name])) {
			throw new HttpBadRequestException($this->request, "Could not resolve argument `{$name}`.");
		}

		return $this->args[$name];
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
