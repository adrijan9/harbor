<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Harbor\Router\Router;

$config = require __DIR__.'/config.php';

if (! is_array($config)) {
    throw new RuntimeException('Site config.php must return an array.');
}

$GLOBALS['config'] = $config;

new Router(__DIR__.'/routes.php')->render();
