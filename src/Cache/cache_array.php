<?php

declare(strict_types=1);

namespace Harbor\Cache;

require_once __DIR__.'/../Support/value.php';

use function Harbor\Support\harbor_is_blank;
use function Harbor\Support\harbor_is_null;

$cache_array = [];

function cache_array_set(string $key, mixed $value, int $ttl_seconds = 0): bool
{
    global $cache_array;

    $normalized_key = cache_array_normalize_key($key);

    $cache_array[$normalized_key] = [
        'value' => $value,
        'expires_at' => cache_array_expiration_timestamp($ttl_seconds),
    ];

    return true;
}

function cache_array_get(string $key, mixed $default = null): mixed
{
    global $cache_array;

    $normalized_key = cache_array_normalize_key($key);

    if (! array_key_exists($normalized_key, $cache_array)) {
        return $default;
    }

    $cache_item = $cache_array[$normalized_key];
    if (! cache_array_item_is_valid($cache_item) || cache_array_item_is_expired($cache_item)) {
        unset($cache_array[$normalized_key]);

        return $default;
    }

    return $cache_item['value'];
}

function cache_array_has(string $key): bool
{
    global $cache_array;

    $normalized_key = cache_array_normalize_key($key);

    if (! array_key_exists($normalized_key, $cache_array)) {
        return false;
    }

    $cache_item = $cache_array[$normalized_key];
    if (! cache_array_item_is_valid($cache_item) || cache_array_item_is_expired($cache_item)) {
        unset($cache_array[$normalized_key]);

        return false;
    }

    return true;
}

function cache_array_delete(string $key): bool
{
    global $cache_array;

    $normalized_key = cache_array_normalize_key($key);

    if (! array_key_exists($normalized_key, $cache_array)) {
        return false;
    }

    unset($cache_array[$normalized_key]);

    return true;
}

function cache_array_clear(): bool
{
    global $cache_array;

    $cache_array = [];

    return true;
}

function cache_array_all(): array
{
    global $cache_array;

    cache_array_prune_expired();

    $cache_values = [];

    foreach ($cache_array as $cache_key => $cache_item) {
        if (! cache_array_item_is_valid($cache_item)) {
            continue;
        }

        $cache_values[$cache_key] = $cache_item['value'];
    }

    return $cache_values;
}

function cache_array_count(): int
{
    return count(cache_array_all());
}

function cache_array_prune_expired(): void
{
    global $cache_array;

    foreach ($cache_array as $cache_key => $cache_item) {
        if (! cache_array_item_is_valid($cache_item) || cache_array_item_is_expired($cache_item)) {
            unset($cache_array[$cache_key]);
        }
    }
}

function cache_array_item_is_valid(mixed $cache_item): bool
{
    if (! is_array($cache_item)) {
        return false;
    }

    if (! array_key_exists('value', $cache_item) || ! array_key_exists('expires_at', $cache_item)) {
        return false;
    }

    $expires_at = $cache_item['expires_at'];

    if (harbor_is_null($expires_at)) {
        return true;
    }

    return is_int($expires_at);
}

function cache_array_item_is_expired(array $cache_item): bool
{
    $expires_at = $cache_item['expires_at'] ?? null;

    if (harbor_is_null($expires_at)) {
        return false;
    }

    if (! is_int($expires_at)) {
        return true;
    }

    return $expires_at <= time();
}

function cache_array_expiration_timestamp(int $ttl_seconds): ?int
{
    if ($ttl_seconds <= 0) {
        return null;
    }

    return time() + $ttl_seconds;
}

function cache_array_normalize_key(string $key): string
{
    $normalized_key = trim($key);

    if (harbor_is_blank($normalized_key)) {
        throw new \InvalidArgumentException('Cache key cannot be empty.');
    }

    return $normalized_key;
}
