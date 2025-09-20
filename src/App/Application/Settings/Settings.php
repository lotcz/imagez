<?php

declare(strict_types=1);

namespace App\Application\Settings;

class Settings implements SettingsInterface {

    private array $settings;

    public function __construct(array $settings) {
        $this->settings = $settings;
    }

    public static function fromFile(string $path): SettingsInterface {
        return new Settings(require $path); 
    }

    public function get(string $key = '', mixed $default = null): mixed {
        return (empty($key) || !isset($this->settings[$key])) ? $default : $this->settings[$key];
    }
}
