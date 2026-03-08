<?php

declare(strict_types=1);

namespace Harbor\Cookie;

require_once __DIR__.'/../Support/value.php';

use function Harbor\Support\harbor_is_blank;

/** Public */
function cookie_set(string $key, string $value, int $ttl_seconds = 0, array $options = []): bool
{
    $normalized_key = cookie_normalize_key($key);
    $resolved_options = cookie_resolve_options($options);
    $expires_at = cookie_resolve_expires_at($ttl_seconds);

    if (headers_sent()) {
        return false;
    }

    $is_set = setcookie($normalized_key, $value, [
        'expires' => $expires_at,
        'path' => $resolved_options['path'],
        'domain' => $resolved_options['domain'],
        'secure' => $resolved_options['secure'],
        'httponly' => $resolved_options['http_only'],
        'samesite' => $resolved_options['same_site'],
    ]);

    if (! $is_set) {
        return false;
    }

    if ($ttl_seconds < 0) {
        unset($_COOKIE[$normalized_key]);

        return true;
    }

    $_COOKIE[$normalized_key] = $value;

    return true;
}

function cookie_get(?string $key = null, mixed $default = null): mixed
{
    if (harbor_is_blank($key)) {
        return cookie_all();
    }

    $normalized_key = cookie_normalize_key($key);

    return cookie_all()[$normalized_key] ?? $default;
}

function cookie_has(string $key): bool
{
    $normalized_key = cookie_normalize_key($key);

    return array_key_exists($normalized_key, cookie_all());
}

function cookie_forget(string $key, array $options = []): bool
{
    $normalized_key = cookie_normalize_key($key);
    $is_forgotten = cookie_set($normalized_key, '', -31536000, $options);

    if (! $is_forgotten) {
        return false;
    }

    unset($_COOKIE[$normalized_key]);

    return true;
}

function cookie_all(): array
{
    return is_array($_COOKIE) ? $_COOKIE : [];
}

/** Private */
function cookie_normalize_key(string $key): string
{
    $normalized_key = trim($key);
    if (harbor_is_blank($normalized_key)) {
        throw new \InvalidArgumentException('Cookie key cannot be empty.');
    }

    if (1 === preg_match('/[=,;\s]/', $normalized_key)) {
        throw new \InvalidArgumentException(
            sprintf('Cookie key "%s" contains invalid characters.', $key)
        );
    }

    return $normalized_key;
}

function cookie_resolve_options(array $options): array
{
    $path = '/';
    $path_option = $options['path'] ?? null;
    if (is_string($path_option) && ! harbor_is_blank(trim($path_option))) {
        $path = trim($path_option);
    }

    $domain = '';
    $domain_option = $options['domain'] ?? null;
    if (is_string($domain_option) && ! harbor_is_blank(trim($domain_option))) {
        $domain = trim($domain_option);
    }

    return [
        'path' => $path,
        'domain' => $domain,
        'secure' => cookie_value_to_bool($options['secure'] ?? false, false),
        'http_only' => cookie_value_to_bool($options['http_only'] ?? true, true),
        'same_site' => cookie_resolve_same_site($options['same_site'] ?? 'Lax'),
    ];
}

function cookie_resolve_same_site(mixed $same_site): string
{
    if (! is_string($same_site)) {
        return 'Lax';
    }

    $normalized_same_site = strtolower(trim($same_site));
    if (harbor_is_blank($normalized_same_site)) {
        return 'Lax';
    }

    return match ($normalized_same_site) {
        'strict' => 'Strict',
        'none' => 'None',
        'lax' => 'Lax',
        default => throw new \InvalidArgumentException(
            sprintf('Invalid cookie same_site value "%s". Use "Lax", "Strict", or "None".', $same_site)
        ),
    };
}

function cookie_resolve_expires_at(int $ttl_seconds): int
{
    if (0 === $ttl_seconds) {
        return 0;
    }

    return time() + $ttl_seconds;
}

function cookie_value_to_bool(mixed $value, bool $default): bool
{
    if (is_bool($value)) {
        return $value;
    }

    if (is_int($value) || is_float($value)) {
        return 0.0 !== (float) $value;
    }

    if (is_string($value)) {
        $parsed_value = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if (is_bool($parsed_value)) {
            return $parsed_value;
        }
    }

    return $default;
}
