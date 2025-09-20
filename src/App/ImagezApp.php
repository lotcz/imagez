<?php

declare(strict_types=1);

namespace App;

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use App\Application\Handlers\HttpErrorHandler;
use App\Application\Handlers\ShutdownHandler;
use App\Application\Middleware\SessionMiddleware;
use App\Application\ResponseEmitter\ResponseEmitter;
use App\Application\Settings\Settings;
use App\Domain\User\UserRepository;
use App\Infrastructure\Persistence\User\InMemoryUserRepository;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Slim\Routing\RouteCollectorProxy;

class ImagezApp {

	private Settings $settings;

	private App $app;

	private bool $debugMode = true;

	public function __construct(Settings $settings) {
		$this->settings = $settings;
		$this->debugMode = $this->settings->get('debugMode', $this->debugMode);

		// DI CONTAINER

		$containerBuilder = new ContainerBuilder();
		if (!$this->debugMode) {
			$containerBuilder->enableCompilation($this->settings->get('cachePath'));
		}

		// logger

		$loggerSettings = $this->settings->get('logger');
		$loggerPath = $loggerSettings['path'];
		$loggerName = $loggerSettings['name'];
		if (!file_exists($loggerPath)) {
			mkdir($loggerPath, 0777, true);
		}
		$logger = new Logger($loggerName);
		$processor = new UidProcessor();
		$logger->pushProcessor($processor);
		$handler = new StreamHandler($loggerPath . $loggerName . '.log', $loggerSettings['level']);
		$logger->pushHandler($handler);

		$containerBuilder->addDefinitions([
			LoggerInterface::class => $logger
		]);

		$containerBuilder->addDefinitions([
			UserRepository::class => \DI\autowire(InMemoryUserRepository::class),
		]);

		$container = $containerBuilder->build();

		// APP

		AppFactory::setContainer($container);
		$this->app = AppFactory::create();

		// MIDDLEWARE

		$this->app->add(SessionMiddleware::class);
		$this->app->addRoutingMiddleware();
		$this->app->addBodyParsingMiddleware();

		// ROUTES

		$this->app->options('/{routes:.*}', function (Request $request, Response $response) {
			// CORS Pre-Flight OPTIONS Request Handler
			return $response;
		});

		$this->app->get('/', function (Request $request, Response $response) {
			$response->getBody()->write('Hello world!');
			return $response;
		});

		$this->app->group('/users', function (RouteCollectorProxy $group) {
			$group->get('', ListUsersAction::class);
			$group->get('/{id}', ViewUserAction::class);
		});
	}

	public function run() {

		// ERROR HANDLING

		$displayErrorDetails = $this->debugMode;
		$logError = true;
		$logErrorDetails = $this->debugMode;

		$serverRequestCreator = ServerRequestCreatorFactory::create();
		$request = $serverRequestCreator->createServerRequestFromGlobals();

		$callableResolver = $this->app->getCallableResolver();

		$responseFactory = $this->app->getResponseFactory();
		$errorHandler = new HttpErrorHandler($callableResolver, $responseFactory);

		$shutdownHandler = new ShutdownHandler($request, $errorHandler, $displayErrorDetails);
		register_shutdown_function($shutdownHandler);

		$errorMiddleware = $this->app->addErrorMiddleware($displayErrorDetails, $logError, $logErrorDetails);
		$errorMiddleware->setDefaultErrorHandler($errorHandler);

		// PROCESS REQUEST

		$response = $this->app->handle($request);
		$responseEmitter = new ResponseEmitter();
		$responseEmitter->emit($response);
	}

}
