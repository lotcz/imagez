<?php

declare(strict_types=1);

namespace App\Application\Actions\color;

use App\Application\Actions\ActionError;
use App\Application\Actions\GenericAction;
use App\Images\Color\ColorAnalyzer;
use App\Images\Storage\ImageStorage;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Zavadil\Common\Settings\Settings;

class GuessBackgroundColorAction extends GenericAction {

	protected ImageStorage $imageStorage;

	protected ColorAnalyzer $colorAnalyzer;

	public function __construct(
		LoggerInterface $logger,
		Settings $settings,
		ImageStorage $imageStorage,
		ColorAnalyzer $colorAnalyzer
	) {
		parent::__construct($logger, $settings);
		$this->imageStorage = $imageStorage;
		$this->colorAnalyzer = $colorAnalyzer;
	}

	protected function action(): Response {
		$this->checkSecureToken();
		$name = $this->requireArg('name');
		$path = $this->imageStorage->getOriginalPath($name);

		if (!$this->imageStorage->fileExists($path)) {
			return $this->respondWithError(
				new ActionError(
					ActionError::RESOURCE_NOT_FOUND,
					"Image $name not found"
				),
				404
			);
		}

		$color = $this->colorAnalyzer->guessBackgroundColor($path);
		return $this->respondWithData(['hex' => $color?->getHex()]);
	}
}
