<?php

declare(strict_types=1);

namespace Harbor\Cache;

require_once __DIR__.'/../Config/config.php';

require_once __DIR__.'/../Support/value.php';

require_once __DIR__.'/CacheDriver.php';

require_once __DIR__.'/cache_array.php';

require_once __DIR__.'/cache_file.php';

use function Harbor\Config\config_get;
use function Harbor\Support\harbor_is_blank;
use function Harbor\Support\harbor_is_null;

function cache_driver(string|CacheDriver $default_driver = CacheDriver::ARRAY): string
{
    $resolved_default_driver = cache_resolve_driver($default_driver);
    if (harbor_is_null($resolved_default_driver)) {
        $resolved_default_driver = CacheDriver::ARRAY;
    }

    $configured_driver = cache_config_get('driver', 'cache_driver', $resolved_default_driver->value);
    $resolved_driver = cache_resolve_driver($configured_driver);

    if (harbor_is_null($resolved_driver)) {
        return $resolved_default_driver->value;
    }

    return $resolved_driver->value;
}

function cache_is_array(): bool
{
    return CacheDriver::ARRAY->value === cache_driver();
}

function cache_is_file(): bool
{
    return CacheDriver::FILE->value === cache_driver();
}

function cache_set(string $key, mixed $value, int $ttl_seconds = 0): bool
{
    if (cache_is_file()) {
        return cache_file_set($key, $value, $ttl_seconds);
    }

    return cache_array_set($key, $value, $ttl_seconds);
}

function cache_get(string $key, mixed $default = null): mixed
{
    if (cache_is_file()) {
        return cache_file_get($key, $default);
    }

    return cache_array_get($key, $default);
}

function cache_has(string $key): bool
{
    if (cache_is_file()) {
        return cache_file_has($key);
    }

    return cache_array_has($key);
}

function cache_delete(string $key): bool
{
    if (cache_is_file()) {
        return cache_file_delete($key);
    }

    return cache_array_delete($key);
}

function cache_clear(): bool
{
    if (cache_is_file()) {
        return cache_file_clear();
    }

    return cache_array_clear();
}

function cache_all(): array
{
    if (cache_is_file()) {
        return cache_file_all();
    }

    return cache_array_all();
}

function cache_count(): int
{
    if (cache_is_file()) {
        return cache_file_count();
    }

    return cache_array_count();
}

function cache_resolve_driver(mixed $driver): ?CacheDriver
{
    if ($driver instanceof CacheDriver) {
        return $driver;
    }

    if (! is_string($driver)) {
        return null;
    }

    $normalized_driver = strtolower(trim($driver));
    if (harbor_is_blank($normalized_driver)) {
        return null;
    }

    foreach (CacheDriver::cases() as $driver_case) {
        if ($driver_case->value === $normalized_driver) {
            return $driver_case;
        }
    }

    return null;
}

function cache_config_get(string $key, string $legacy_key, mixed $default = null): mixed
{
    $key_path = 'cache.'.$key;
    $cache_config_value = config_get($key_path);
    if (! harbor_is_null($cache_config_value)) {
        return $cache_config_value;
    }

    return config_get($legacy_key, $default);
}
