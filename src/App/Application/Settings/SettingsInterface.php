<?php

declare(strict_types=1);

namespace App\Application\Settings;

interface SettingsInterface{
    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key = '');

    public static function fromFile(string $path): self;
}
