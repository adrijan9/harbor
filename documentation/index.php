<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Harbor\Router\Router;

/*
 * This file contains global configuration environment variables for the site.
 * You can access these variables in your route entries using the `config()` function.
 * For example, `config('app_name')` will return "Harbor Site".
 */
new Router(
    __DIR__.'/routes.php',
    __DIR__.'/config.php',
)->render();
