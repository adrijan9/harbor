<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Harbor\Router\Router;

new Router(
    __DIR__.'/routes.php',
    __DIR__.'/../global.php',
)->render();
