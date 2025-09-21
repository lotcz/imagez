<?php

declare(strict_types=1);

namespace App\Images\Actions;

use App\Application\Actions\Action;
use App\Application\Settings\Settings;
use App\Images\Resizer\ImageResizer;
use App\Images\Storage\ImageStorage;
use Psr\Log\LoggerInterface;

abstract class ImageAction extends Action {

	protected ImageResizer $imageResizer;

	protected ImageStorage $imageStorage;

	public function __construct(LoggerInterface $logger, Settings $settings, ImageResizer $imageResizer, ImageStorage $imageStorage) {
		parent::__construct($logger, $settings);
		$this->imageResizer = $imageResizer;
		$this->imageStorage = $imageStorage;
	}
}
