<?php

declare(strict_types=1);

use Harbor\HelperLoader;
use function Harbor\Config\config;
use function Harbor\Lang\t;
use function Harbor\Lang\translation_init;

HelperLoader::load('translation');

translation_init([
    'en' => [__DIR__.'/../../lang/en.php'],
]);

echo t('home.welcome', ['app_name' => config('app_name', 'Harbor Site')]);
