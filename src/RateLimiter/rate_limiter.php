<?php

declare(strict_types=1);

namespace Harbor\RateLimiter;

require_once __DIR__.'/../Cache/cache.php';

require_once __DIR__.'/../Support/value.php';

use function Harbor\Cache\cache_delete;
use function Harbor\Cache\cache_get;
use function Harbor\Cache\cache_set;
use function Harbor\Support\harbor_is_blank;

/** Public */
function rate_limiter_hit(string $key, int $decay_seconds = 60, int $amount = 1): int
{
    $normalized_decay_seconds = max(1, $decay_seconds);
    $normalized_amount = max(1, $amount);
    $bucket_key = rate_limiter_bucket_key($key);
    $now = time();
    $bucket = rate_limiter_current_bucket($bucket_key);

    if (! is_array($bucket)) {
        $bucket = [
            'attempts' => 0,
            'expires_at' => $now + $normalized_decay_seconds,
        ];
    }

    $bucket['attempts'] += $normalized_amount;
    rate_limiter_store_bucket($bucket_key, $bucket);

    return $bucket['attempts'];
}

function rate_limiter_attempts(string $key): int
{
    $bucket = rate_limiter_current_bucket(rate_limiter_bucket_key($key));
    if (! is_array($bucket)) {
        return 0;
    }

    return $bucket['attempts'];
}

function rate_limiter_too_many_attempts(string $key, int $max_attempts): bool
{
    $normalized_max_attempts = max(1, $max_attempts);

    return rate_limiter_attempts($key) >= $normalized_max_attempts;
}

function rate_limiter_remaining(string $key, int $max_attempts): int
{
    $normalized_max_attempts = max(1, $max_attempts);
    $remaining_attempts = $normalized_max_attempts - rate_limiter_attempts($key);

    return max(0, $remaining_attempts);
}

function rate_limiter_available_in(string $key): int
{
    $bucket = rate_limiter_current_bucket(rate_limiter_bucket_key($key));
    if (! is_array($bucket)) {
        return 0;
    }

    return max(0, $bucket['expires_at'] - time());
}

function rate_limiter_clear(string $key): bool
{
    return cache_delete(rate_limiter_bucket_key($key));
}

/** Private */
function rate_limiter_bucket_key(string $key): string
{
    $normalized_key = rate_limiter_normalize_key($key);

    return 'rate_limiter:'.sha1($normalized_key);
}

function rate_limiter_current_bucket(string $bucket_key): ?array
{
    $bucket = cache_get($bucket_key, null);
    if (! rate_limiter_bucket_is_valid($bucket)) {
        return null;
    }

    $expires_at = $bucket['expires_at'];
    if ($expires_at <= time()) {
        cache_delete($bucket_key);

        return null;
    }

    return [
        'attempts' => $bucket['attempts'],
        'expires_at' => $expires_at,
    ];
}

function rate_limiter_bucket_is_valid(mixed $bucket): bool
{
    if (! is_array($bucket)) {
        return false;
    }

    if (! array_key_exists('attempts', $bucket) || ! array_key_exists('expires_at', $bucket)) {
        return false;
    }

    return is_int($bucket['attempts']) && $bucket['attempts'] >= 0 && is_int($bucket['expires_at']);
}

function rate_limiter_store_bucket(string $bucket_key, array $bucket): void
{
    $expires_at = $bucket['expires_at'] ?? 0;
    $attempts = $bucket['attempts'] ?? 0;

    if (! is_int($expires_at) || ! is_int($attempts)) {
        throw new \RuntimeException('Invalid rate limiter bucket payload.');
    }

    $ttl_seconds = max(1, $expires_at - time());
    cache_set($bucket_key, [
        'attempts' => max(0, $attempts),
        'expires_at' => $expires_at,
    ], $ttl_seconds);
}

function rate_limiter_normalize_key(string $key): string
{
    $normalized_key = trim($key);
    if (harbor_is_blank($normalized_key)) {
        throw new \InvalidArgumentException('Rate limiter key cannot be empty.');
    }

    return $normalized_key;
}
