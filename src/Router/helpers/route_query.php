<?php

declare(strict_types=1);

namespace Harbor\Router;

require_once __DIR__.'/../../Support/array.php';
require_once __DIR__.'/../../Support/value.php';

use function Harbor\Support\array_forget;
use function Harbor\Support\harbor_is_blank;
use function Harbor\Support\harbor_is_null;

/**
 * Query functions.
 */
function route_query(?string $key = null, mixed $default = null): mixed
{
    $query = route_queries();

    if (harbor_is_blank($key)) {
        return $query;
    }

    return route_array_get($query, $key, $default);
}

function route_query_only(string ...$keys): array
{
    if ([] === $keys) {
        return [];
    }

    $query = route_queries();
    $only = [];

    foreach ($keys as $key) {
        if (harbor_is_blank($key) || ! route_array_has($query, $key)) {
            continue;
        }

        $only[$key] = route_array_get($query, $key);
    }

    return $only;
}

function route_query_except(string ...$keys): array
{
    $query = route_queries();

    foreach ($keys as $key) {
        if (harbor_is_blank($key)) {
            continue;
        }

        array_forget($query, $key);
    }

    return $query;
}

function route_query_int(string $key, int $default = 0): int
{
    $value = route_query($key);

    if (is_int($value)) {
        return $value;
    }

    if (is_numeric($value)) {
        return (int) $value;
    }

    return $default;
}

function route_query_float(string $key, float $default = 0.0): float
{
    $value = route_query($key);

    if (is_float($value) || is_int($value)) {
        return (float) $value;
    }

    if (is_string($value) && is_numeric($value)) {
        return (float) $value;
    }

    return $default;
}

function route_query_str(string $key, string $default = ''): string
{
    $value = route_query($key);

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

function route_query_bool(string $key, bool $default = false): bool
{
    $value = route_query($key);

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

function route_query_arr(string $key, array $default = []): array
{
    $value = route_query($key);

    if (is_array($value)) {
        return $value;
    }

    if ($value instanceof \Traversable) {
        return iterator_to_array($value);
    }

    if (is_string($value)) {
        $decoded = route_query_decode_json($value, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        $decoded_value = rawurldecode($value);
        if (str_contains($decoded_value, ',')) {
            return array_values(array_filter(
                array_map('trim', explode(',', $decoded_value)),
                static fn (string $item): bool => '' !== $item
            ));
        }
    }

    return $default;
}

function route_query_obj(string $key, ?object $default = null): ?object
{
    $value = route_query($key);

    if (is_object($value)) {
        return $value;
    }

    if (is_array($value)) {
        return (object) $value;
    }

    if (is_string($value)) {
        $decoded = route_query_decode_json($value, false);

        if (is_object($decoded)) {
            return $decoded;
        }

        if (is_array($decoded)) {
            return (object) $decoded;
        }
    }

    return $default;
}

function route_query_json(string $key, mixed $default = null): mixed
{
    $value = route_query($key);

    if (! is_string($value)) {
        return $default;
    }

    $decoded = route_query_decode_json($value, true);

    return harbor_is_null($decoded) ? $default : $decoded;
}

function route_queries(): array
{
    global $route;

    $query = $route['query'] ?? [];

    return is_array($query) ? $query : [];
}

function route_queries_count(): int
{
    return count(route_queries());
}

function route_query_exists(string $key): bool
{
    return route_array_has(route_queries(), $key);
}

function route_query_decode_json(string $value, bool $assoc): mixed
{
    $decoded = json_decode(rawurldecode($value), $assoc);

    if (JSON_ERROR_NONE !== json_last_error()) {
        return null;
    }

    return $decoded;
}

function route_array_get(array $array, string $key, mixed $default = null): mixed
{
    if (array_key_exists($key, $array)) {
        return $array[$key];
    }

    $keys = explode('.', $key);
    $current = $array;

    foreach ($keys as $segment_key) {
        if (! is_array($current) || ! array_key_exists($segment_key, $current)) {
            return $default;
        }

        $current = $current[$segment_key];
    }

    return $current;
}

function route_array_has(array $array, string $key): bool
{
    if (array_key_exists($key, $array)) {
        return true;
    }

    $keys = explode('.', $key);
    $current = $array;

    foreach ($keys as $segment_key) {
        if (! is_array($current) || ! array_key_exists($segment_key, $current)) {
            return false;
        }

        $current = $current[$segment_key];
    }

    return true;
}
