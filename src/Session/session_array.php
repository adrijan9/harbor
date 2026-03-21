<?php

declare(strict_types=1);

namespace Harbor\Session;

require_once __DIR__.'/../Support/value.php';

use function Harbor\Support\harbor_is_blank;
use function Harbor\Support\harbor_is_null;

$session_array_store = [];

/** Public */
function session_array_set(string $key, mixed $value, int $ttl_seconds = 0): bool
{
    global $session_array_store;

    $normalized_key = session_array_internal_normalize_key($key);

    $session_array_store[$normalized_key] = [
        'value' => $value,
        'expires_at' => session_array_internal_expiration_timestamp($ttl_seconds),
    ];

    return true;
}

function session_array_get(string $key, mixed $default = null): mixed
{
    global $session_array_store;

    $normalized_key = session_array_internal_normalize_key($key);

    if (! array_key_exists($normalized_key, $session_array_store)) {
        return $default;
    }

    $session_item = $session_array_store[$normalized_key];
    if (! session_array_internal_item_is_valid($session_item) || session_array_internal_item_is_expired($session_item)) {
        unset($session_array_store[$normalized_key]);

        return $default;
    }

    return $session_item['value'];
}

function session_array_has(string $key): bool
{
    global $session_array_store;

    $normalized_key = session_array_internal_normalize_key($key);

    if (! array_key_exists($normalized_key, $session_array_store)) {
        return false;
    }

    $session_item = $session_array_store[$normalized_key];
    if (! session_array_internal_item_is_valid($session_item) || session_array_internal_item_is_expired($session_item)) {
        unset($session_array_store[$normalized_key]);

        return false;
    }

    return true;
}

function session_array_forget(string $key): bool
{
    global $session_array_store;

    $normalized_key = session_array_internal_normalize_key($key);

    if (! array_key_exists($normalized_key, $session_array_store)) {
        return true;
    }

    unset($session_array_store[$normalized_key]);

    return true;
}

function session_array_all(): array
{
    global $session_array_store;

    session_array_internal_prune_expired();

    $session_values = [];

    foreach ($session_array_store as $session_key => $session_item) {
        if (! session_array_internal_item_is_valid($session_item)) {
            continue;
        }

        $session_values[$session_key] = $session_item['value'];
    }

    return $session_values;
}

function session_array_clear(): bool
{
    global $session_array_store;

    $session_array_store = [];

    return true;
}

/** Private */
function session_array_internal_prune_expired(): void
{
    global $session_array_store;

    foreach ($session_array_store as $session_key => $session_item) {
        if (! session_array_internal_item_is_valid($session_item) || session_array_internal_item_is_expired($session_item)) {
            unset($session_array_store[$session_key]);
        }
    }
}

function session_array_internal_item_is_valid(mixed $session_item): bool
{
    if (! is_array($session_item)) {
        return false;
    }

    if (! array_key_exists('value', $session_item) || ! array_key_exists('expires_at', $session_item)) {
        return false;
    }

    $expires_at = $session_item['expires_at'];

    if (harbor_is_null($expires_at)) {
        return true;
    }

    return is_int($expires_at);
}

function session_array_internal_item_is_expired(array $session_item): bool
{
    $expires_at = $session_item['expires_at'] ?? null;

    if (harbor_is_null($expires_at)) {
        return false;
    }

    if (! is_int($expires_at)) {
        return true;
    }

    return $expires_at <= time();
}

function session_array_internal_expiration_timestamp(int $ttl_seconds): ?int
{
    if ($ttl_seconds <= 0) {
        return null;
    }

    return time() + $ttl_seconds;
}

function session_array_internal_normalize_key(string $key): string
{
    $normalized_key = trim($key);

    if (harbor_is_blank($normalized_key)) {
        throw new \InvalidArgumentException('Session key cannot be empty.');
    }

    return $normalized_key;
}
