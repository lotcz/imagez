<?php

declare(strict_types=1);

use App\Application\Settings\ArraySettings;
use App\ImagezApp;

require __DIR__ . '/../vendor/autoload.php';

$settings = new ArraySettings(
	[
		'debugMode' => true,
		'compileContainer' => false,
		'tmpPath' => __DIR__ . '/../var/tmp/',
		'imageStorePath' => __DIR__ . '/../var/image-store',
		'maxImageSizeBytes' => 1024 * 1024 * 10,
		'defaultResizedExt' => 'webp',
		'secretToken' => 'some-secure-value',
		'logger' => [
			'name' => 'imagez-app',
			'path' => __DIR__ . '/../var/logs/',
			'level' => \Monolog\Logger::DEBUG,
		],
	]
);

$app = new ImagezApp($settings);
$app->run();
