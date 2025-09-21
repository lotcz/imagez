<?php

declare(strict_types=1);

namespace App\Application\Settings;

interface Settings {

	public function get(string $key = ''): mixed;

}
