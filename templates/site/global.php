<?php

declare(strict_types=1);

use Harbor\Environment;

/*
 * This file contains global configuration environment variables for the site.
 * You can access these variables in your route entries using the `config()` function.
 * For example, `config('app_name')` will return "Harbor Site".
 */
return [
    'app_name' => 'Harbor Site',
    'environment' => Environment::LOCAL,
    'lang' => 'en',
    'cache_file_path' => __DIR__.'/cache',
];
