<?php

declare(strict_types=1);

namespace Harbor\Date;

if (! class_exists(\Carbon\Carbon::class)) {
    throw new \RuntimeException(
        'The "nesbot/carbon" package is required. Run "composer install" or "composer require nesbot/carbon".'
    );
}

class Carbon extends \Carbon\Carbon {}
