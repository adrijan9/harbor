<?php

declare(strict_types=1);

use Harbor\Environment;

return [
    'app_name' => 'Harbor Site',
    'environment' => Environment::LOCAL,
    'lang' => 'en',
    'cache_file_path' => __DIR__.'/cache',
];
