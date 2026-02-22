<?php

declare(strict_types=1);

$config = require __DIR__.'/config.php';

use Harbor\Router\Router;

require __DIR__.'/../vendor/autoload.php';

$GLOBALS['config'] = $config;

new Router(__DIR__.'/routes.php')->render();
