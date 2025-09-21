<?php

declare(strict_types=1);

namespace App\Application\Settings;

class ArraySettings implements Settings {

	private array $settings;

	public function __construct(array $settings) {
		$this->settings = $settings;
	}

	public static function fromFile(string $path): Settings {
		return new ArraySettings(require $path);
	}

	public function get(string $key = '', mixed $default = null): mixed {
		return (empty($key) || !isset($this->settings[$key])) ? $default : $this->settings[$key];
	}

}
