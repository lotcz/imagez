<?php

declare(strict_types=1);

use App\Application\Settings\ArraySettings;
use App\ImagezApp;

require __DIR__ . '/../vendor/autoload.php';

$settings = new ArraySettings(
	[
		'debugMode' => true,
		'cachePath' => __DIR__ . '/../var/cache/',
		'maxImageSizeBytes' => 1024 * 1024 * 10,
		'logger' => [
			'name' => 'imagez-app',
			'path' => __DIR__ . '/../var/logs/',
			'level' => \Monolog\Logger::DEBUG,
		],
	]
);

$app = new ImagezApp($settings);
$app->run();
