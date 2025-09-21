<?php

declare(strict_types=1);

namespace App\Application\Handlers;

use App\Application\Actions\ActionError;
use App\Application\Actions\ActionPayload;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Handlers\ErrorHandler as SlimErrorHandler;
use Throwable;

class HttpErrorHandler extends SlimErrorHandler {
	/**
	 * @inheritdoc
	 */
	protected function respond(): Response {
		$exception = $this->exception;
		$statusCode = 500;
		$error = new ActionError(
			ActionError::SERVER_ERROR,
			'An internal error has occurred while processing your request.'
		);

		if ($exception instanceof BadRequestException) {
			$statusCode = 400;
			$error->setType(ActionError::BAD_REQUEST);
			$error->setDescription($exception->getMessage());
		} else if ($exception instanceof Throwable && $this->displayErrorDetails) {
			$error->setDescription($exception->getMessage());
		}

		$payload = new ActionPayload($statusCode, null, $error);
		$encodedPayload = json_encode($payload, JSON_PRETTY_PRINT);

		$response = $this->responseFactory->createResponse($statusCode);
		$response->getBody()->write($encodedPayload);

		return $response->withHeader('Content-Type', 'application/json');
	}
}
