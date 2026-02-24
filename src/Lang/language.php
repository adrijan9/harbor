<?php

declare(strict_types=1);

namespace Harbor\Lang;

require_once __DIR__.'/../Config/config.php';
require_once __DIR__.'/../Support/value.php';

use function Harbor\Config\config;
use function Harbor\Support\harbor_is_blank;

function lang_get(string $default = 'en'): string
{
    $locale = config('lang', $default);

    if (is_string($locale)) {
        $normalized_locale = trim($locale);

        if (! harbor_is_blank($normalized_locale)) {
            return $normalized_locale;
        }
    }

    return $default;
}

function lang_set(string $locale): void
{
    $normalized_locale = trim($locale);
    if (harbor_is_blank($normalized_locale)) {
        throw new \InvalidArgumentException('Language locale cannot be empty.');
    }

    $environment = is_array($_ENV) ? $_ENV : [];
    $environment['lang'] = $normalized_locale;

    $_ENV = $environment;
    $GLOBALS['_ENV'] = $_ENV;
}

function lang_is(string $locale): bool
{
    return lang_get() === trim($locale);
}
