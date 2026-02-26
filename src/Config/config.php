<?php

declare(strict_types=1);

namespace Harbor\Config;

require_once __DIR__.'/../Support/value.php';

use function Harbor\Support\harbor_is_blank;
use function Harbor\Support\harbor_is_null;

$config_global_file_path = null;

function config_init(string ...$config_files): void
{
    if (empty($config_files)) {
        return;
    }

    $environment = config_all();

    foreach ($config_files as $config_file) {
        $normalized_path = config_normalize_file_path($config_file);
        $loaded_config = config_load_file($normalized_path);
        $config_key = config_file_key($normalized_path);
        $environment[$config_key] = $loaded_config;
    }

    config_write_environment($environment);
}

function config_init_global(string $config_file): void
{
    $normalized_path = config_normalize_file_path($config_file);
    $loaded_config = config_load_file($normalized_path);
    $environment = config_all();

    foreach ($loaded_config as $config_key => $config_value) {
        $environment[$config_key] = $config_value;
    }

    config_set_global_file_path($normalized_path);
    config_write_environment($environment);
}

function config(?string $key = null, mixed $default = null): mixed
{
    return config_get($key, $default);
}

function config_get(?string $key = null, mixed $default = null): mixed
{
    $environment = config_all();

    if (harbor_is_blank($key)) {
        return $environment;
    }

    return config_array_get($environment, $key, $default);
}

function config_all(): array
{
    return is_array($_ENV) ? $_ENV : [];
}

function config_count(): int
{
    return count(config_all());
}

function config_exists(string $key): bool
{
    return config_array_has(config_all(), $key);
}

function config_int(string $key, int $default = 0): int
{
    return config_value_to_int(config_get($key), $default);
}

function config_float(string $key, float $default = 0.0): float
{
    return config_value_to_float(config_get($key), $default);
}

function config_str(string $key, string $default = ''): string
{
    return config_value_to_str(config_get($key), $default);
}

function config_bool(string $key, bool $default = false): bool
{
    return config_value_to_bool(config_get($key), $default);
}

function config_arr(string $key, array $default = []): array
{
    return config_value_to_arr(config_get($key), $default);
}

function config_obj(string $key, ?object $default = null): ?object
{
    return config_value_to_obj(config_get($key), $default);
}

function config_json(string $key, mixed $default = null): mixed
{
    return config_value_to_json(config_get($key), $default);
}

function config_array_get(array $array, string $key, mixed $default = null): mixed
{
    if (array_key_exists($key, $array)) {
        return $array[$key];
    }

    $segments = explode('.', $key);
    $current = $array;

    foreach ($segments as $segment) {
        if (! is_array($current) || ! array_key_exists($segment, $current)) {
            return $default;
        }

        $current = $current[$segment];
    }

    return $current;
}

function config_file_key(string $config_path): string
{
    $file_key = pathinfo($config_path, PATHINFO_FILENAME);
    if (! is_string($file_key) || harbor_is_blank($file_key)) {
        throw new \RuntimeException(sprintf('Unable to derive config key from file path: %s', $config_path));
    }

    return $file_key;
}

function config_normalize_file_path(string $config_file): string
{
    $normalized_path = trim($config_file);
    if (harbor_is_blank($normalized_path)) {
        throw new \InvalidArgumentException('Config file path cannot be empty.');
    }

    return $normalized_path;
}

function config_load_file(string $config_file): array
{
    if (! is_file($config_file)) {
        throw new \RuntimeException(sprintf('Config file not found: %s', $config_file));
    }

    $loaded_config = require $config_file;
    if (! is_array($loaded_config)) {
        throw new \RuntimeException(sprintf('Config file "%s" must return an array.', $config_file));
    }

    return $loaded_config;
}

function config_write_environment(array $environment): void
{
    $_ENV = $environment;
    $GLOBALS['_ENV'] = $_ENV;
}

function config_global_file_path(): ?string
{
    global $config_global_file_path;

    if (! is_string($config_global_file_path) || harbor_is_blank($config_global_file_path)) {
        return null;
    }

    return $config_global_file_path;
}

function config_global_directory_path(): ?string
{
    $global_file_path = config_global_file_path();
    if (harbor_is_null($global_file_path)) {
        return null;
    }

    $global_directory_path = dirname($global_file_path);
    if (! is_string($global_directory_path) || harbor_is_blank($global_directory_path)) {
        return null;
    }

    return rtrim($global_directory_path, '/\\');
}

function config_set_global_file_path(string $path): void
{
    global $config_global_file_path;

    $config_global_file_path = $path;
}

function config_array_has(array $array, string $key): bool
{
    if (array_key_exists($key, $array)) {
        return true;
    }

    $segments = explode('.', $key);
    $current = $array;

    foreach ($segments as $segment) {
        if (! is_array($current) || ! array_key_exists($segment, $current)) {
            return false;
        }

        $current = $current[$segment];
    }

    return true;
}

function config_value_to_int(mixed $value, int $default = 0): int
{
    if (is_int($value)) {
        return $value;
    }

    if (is_numeric($value)) {
        return (int) $value;
    }

    return $default;
}

function config_value_to_float(mixed $value, float $default = 0.0): float
{
    if (is_float($value) || is_int($value)) {
        return (float) $value;
    }

    if (is_numeric($value)) {
        return (float) $value;
    }

    return $default;
}

function config_value_to_str(mixed $value, string $default = ''): string
{
    if (is_string($value)) {
        return $value;
    }

    if (is_scalar($value)) {
        return (string) $value;
    }

    if (is_object($value) && method_exists($value, '__toString')) {
        return (string) $value;
    }

    return $default;
}

function config_value_to_bool(mixed $value, bool $default = false): bool
{
    if (is_bool($value)) {
        return $value;
    }

    if (is_int($value) || is_float($value)) {
        return 0.0 !== (float) $value;
    }

    if (is_string($value)) {
        $parsed = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        if (null !== $parsed) {
            return $parsed;
        }
    }

    return $default;
}

function config_value_to_arr(mixed $value, array $default = []): array
{
    if (is_array($value)) {
        return $value;
    }

    if ($value instanceof \Traversable) {
        return iterator_to_array($value);
    }

    if (is_string($value)) {
        $decoded = config_decode_json($value, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (str_contains($value, ',')) {
            return array_values(array_filter(
                array_map('trim', explode(',', $value)),
                static fn (string $item): bool => '' !== $item
            ));
        }
    }

    return $default;
}

function config_value_to_obj(mixed $value, ?object $default = null): ?object
{
    if (is_object($value)) {
        return $value;
    }

    if (is_array($value)) {
        return (object) $value;
    }

    if (is_string($value)) {
        $decoded = config_decode_json($value, false);
        if (is_object($decoded)) {
            return $decoded;
        }

        if (is_array($decoded)) {
            return (object) $decoded;
        }
    }

    return $default;
}

function config_value_to_json(mixed $value, mixed $default = null): mixed
{
    if (! is_string($value)) {
        return $default;
    }

    $decoded = config_decode_json($value, true);

    return harbor_is_null($decoded) ? $default : $decoded;
}

function config_decode_json(string $value, bool $assoc): mixed
{
    try {
        return json_decode($value, $assoc, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException) {
        return null;
    }
}
