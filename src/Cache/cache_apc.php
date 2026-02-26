<?php

declare(strict_types=1);

namespace Harbor\Cache;

require_once __DIR__.'/../Support/value.php';

use function Harbor\Support\harbor_is_blank;
use function Harbor\Support\harbor_is_null;

function cache_apc_available(): bool
{
    if (
        ! function_exists('apcu_store')
        || ! function_exists('apcu_fetch')
        || ! function_exists('apcu_exists')
        || ! function_exists('apcu_delete')
    ) {
        return false;
    }

    if (! function_exists('apcu_enabled')) {
        return true;
    }

    return apcu_enabled();
}

function cache_apc_set(string $key, mixed $value, int $ttl_seconds = 0): bool
{
    $normalized_key = cache_apc_normalize_key($key);
    cache_apc_require_available();

    $cache_payload = [
        'key' => $normalized_key,
        'value' => $value,
        'expires_at' => cache_apc_expiration_timestamp($ttl_seconds),
    ];

    $stored = apcu_store(cache_apc_storage_key($normalized_key), $cache_payload, max($ttl_seconds, 0));
    if (! $stored) {
        return false;
    }

    cache_apc_index_add($normalized_key);

    return true;
}

function cache_apc_get(string $key, mixed $default = null): mixed
{
    $normalized_key = cache_apc_normalize_key($key);
    cache_apc_require_available();

    $success = false;
    $cache_item = apcu_fetch(cache_apc_storage_key($normalized_key), $success);
    if (! $success) {
        return $default;
    }

    if (! cache_apc_item_is_valid($cache_item) || $cache_item['key'] !== $normalized_key || cache_apc_item_is_expired($cache_item)) {
        cache_apc_delete($normalized_key);

        return $default;
    }

    return $cache_item['value'];
}

function cache_apc_has(string $key): bool
{
    $normalized_key = cache_apc_normalize_key($key);
    cache_apc_require_available();

    if (! apcu_exists(cache_apc_storage_key($normalized_key))) {
        return false;
    }

    $success = false;
    $cache_item = apcu_fetch(cache_apc_storage_key($normalized_key), $success);
    if (! $success) {
        return false;
    }

    if (! cache_apc_item_is_valid($cache_item) || $cache_item['key'] !== $normalized_key || cache_apc_item_is_expired($cache_item)) {
        cache_apc_delete($normalized_key);

        return false;
    }

    return true;
}

function cache_apc_delete(string $key): bool
{
    $normalized_key = cache_apc_normalize_key($key);
    cache_apc_require_available();

    $deleted = apcu_delete(cache_apc_storage_key($normalized_key));
    if ($deleted) {
        cache_apc_index_remove($normalized_key);
    }

    return $deleted;
}

function cache_apc_clear(): bool
{
    cache_apc_require_available();

    $cache_keys = cache_apc_index_values();
    foreach ($cache_keys as $cache_key) {
        apcu_delete(cache_apc_storage_key($cache_key));
    }

    apcu_delete(cache_apc_index_key());

    return true;
}

function cache_apc_all(): array
{
    cache_apc_require_available();

    $cache_values = [];
    $active_keys = [];
    $cache_keys = cache_apc_index_values();

    foreach ($cache_keys as $cache_key) {
        $success = false;
        $cache_item = apcu_fetch(cache_apc_storage_key($cache_key), $success);
        if (! $success) {
            continue;
        }

        if (! cache_apc_item_is_valid($cache_item) || cache_apc_item_is_expired($cache_item)) {
            apcu_delete(cache_apc_storage_key($cache_key));

            continue;
        }

        $active_keys[] = $cache_key;
        $cache_values[$cache_key] = $cache_item['value'];
    }

    cache_apc_index_write($active_keys);

    return $cache_values;
}

function cache_apc_count(): int
{
    return count(cache_apc_all());
}

function cache_apc_require_available(): void
{
    if (cache_apc_available()) {
        return;
    }

    throw new \RuntimeException('APCu extension is not available or enabled.');
}

function cache_apc_item_is_valid(mixed $cache_item): bool
{
    if (! is_array($cache_item)) {
        return false;
    }

    if (
        ! array_key_exists('key', $cache_item)
        || ! is_string($cache_item['key'])
        || harbor_is_blank($cache_item['key'])
        || ! array_key_exists('value', $cache_item)
        || ! array_key_exists('expires_at', $cache_item)
    ) {
        return false;
    }

    $expires_at = $cache_item['expires_at'];

    if (harbor_is_null($expires_at)) {
        return true;
    }

    return is_int($expires_at);
}

function cache_apc_item_is_expired(array $cache_item): bool
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

function cache_apc_expiration_timestamp(int $ttl_seconds): ?int
{
    if ($ttl_seconds <= 0) {
        return null;
    }

    return time() + $ttl_seconds;
}

function cache_apc_normalize_key(string $key): string
{
    $normalized_key = trim($key);

    if (harbor_is_blank($normalized_key)) {
        throw new \InvalidArgumentException('Cache key cannot be empty.');
    }

    return $normalized_key;
}

function cache_apc_storage_key(string $key): string
{
    return cache_apc_storage_prefix().$key;
}

function cache_apc_storage_prefix(): string
{
    return 'harbor:cache:apc:item:';
}

function cache_apc_index_key(): string
{
    return 'harbor:cache:apc:index';
}

function cache_apc_index_values(): array
{
    $success = false;
    $stored_index = apcu_fetch(cache_apc_index_key(), $success);
    if (! $success || ! is_array($stored_index) || empty($stored_index)) {
        return [];
    }

    $cache_keys = [];

    foreach ($stored_index as $cache_key) {
        if (! is_string($cache_key)) {
            continue;
        }

        $normalized_key = trim($cache_key);
        if (harbor_is_blank($normalized_key)) {
            continue;
        }

        $cache_keys[] = $normalized_key;
    }

    return array_values(array_unique($cache_keys));
}

function cache_apc_index_write(array $cache_keys): void
{
    apcu_store(cache_apc_index_key(), array_values(array_unique($cache_keys)));
}

function cache_apc_index_add(string $cache_key): void
{
    $cache_keys = cache_apc_index_values();
    $cache_keys[] = $cache_key;
    cache_apc_index_write($cache_keys);
}

function cache_apc_index_remove(string $cache_key): void
{
    $cache_keys = cache_apc_index_values();
    $remaining_keys = array_values(array_filter(
        $cache_keys,
        static fn (string $existing_cache_key): bool => $existing_cache_key !== $cache_key
    ));

    if (empty($remaining_keys)) {
        apcu_delete(cache_apc_index_key());

        return;
    }

    cache_apc_index_write($remaining_keys);
}
