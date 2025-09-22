<?php

declare(strict_types=1);

namespace App;

use App\Application\Errors\HttpErrorHandler;
use App\Application\Errors\ShutdownHandler;
use App\Application\ResponseEmitter\ImageResponseEmitter;
use App\Application\ResponseEmitter\ResponseEmitter;
use App\Application\Settings\Settings;
use App\Images\Actions\UploadImageAction;
use App\Images\Actions\ViewImageHealthAction;
use App\Images\Actions\ViewImageOriginalAction;
use App\Images\Actions\ViewImageResizedAction;
use App\Images\Resizer\GdImageResizer;
use App\Images\Resizer\ImageResizer;
use App\Images\Storage\DiskImageStorage;
use App\Images\Storage\ImageStorage;
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
		if ($this->settings->get('compileContainer')) {
			$containerBuilder->enableCompilation($this->settings->get('cachePath'));
		}

		// settings
		$containerBuilder->addDefinitions([Settings::class => $this->settings]);

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

		$containerBuilder->addDefinitions([LoggerInterface::class => $logger]);

		// resizer
		$containerBuilder->addDefinitions([ImageResizer::class => \DI\autowire(GdImageResizer::class)]);

		// storage
		$containerBuilder->addDefinitions([ImageStorage::class => \DI\autowire(DiskImageStorage::class)]);

		$container = $containerBuilder->build();

		// APP

		AppFactory::setContainer($container);
		$this->app = AppFactory::create();

		// MIDDLEWARE

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

		$this->app->group('/images', function (RouteCollectorProxy $group) {
			$group->post('/upload', UploadImageAction::class);
			$group->get('/health/{name}', ViewImageHealthAction::class);
			$group->get('/original/{name}', ViewImageOriginalAction::class);
			$group->get('/resized/{name}', ViewImageResizedAction::class);
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

		$contentType = $response->getHeaderLine('Content-Type');
		$responseEmitter = (str_starts_with($contentType, 'image/')) ? new ImageResponseEmitter() : new ResponseEmitter();
		$responseEmitter->emit($response);
	}

}
