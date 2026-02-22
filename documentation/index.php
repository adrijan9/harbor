<?php

declare(strict_types=1);

use PhpFramework\Router\Router;

require __DIR__.'/../vendor/autoload.php';

new Router(__DIR__.'/routes.php')->render();
