<?php
use Slim\Factory\AppFactory;
use Intervention\Image\ImageManager;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();
$manager = new ImageManager(['driver' => 'gd']); // or 'imagick'

// Resize endpoint
$app->post('/resize', function ($request, $response) use ($manager) {
    $uploadedFiles = $request->getUploadedFiles();

    if (!isset($uploadedFiles['image'])) {
        $response->getBody()->write(json_encode(['error' => 'No image uploaded']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $file = $uploadedFiles['image'];
    if ($file->getError() !== UPLOAD_ERR_OK) {
        $response->getBody()->write(json_encode(['error' => 'Upload failed']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $params = $request->getQueryParams();
    $width  = isset($params['w']) ? (int)$params['w'] : 300;
    $height = isset($params['h']) ? (int)$params['h'] : 300;

    $image = $manager->make($file->getStream()->getMetadata('uri'))
                     ->resize($width, $height);

    $response->getBody()->write($image->encode('jpg'));
    return $response->withHeader('Content-Type', 'image/jpeg');
});

$app->run();
