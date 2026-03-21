<?php

declare(strict_types=1);

namespace Harbor\Cookie;

require_once __DIR__.'/../Support/value.php';

use function Harbor\Support\harbor_is_blank;

/** Public */
function cookie_set(string $key, string $value, int $ttl_seconds = 0, array $options = []): bool
{
    $normalized_key = cookie_internal_normalize_key($key);
    $resolved_options = cookie_internal_resolve_options($options);
    $encoded_value = $ttl_seconds < 0 ? '' : cookie_internal_encode_value($value, $resolved_options);
    $expires_at = cookie_internal_resolve_expires_at($ttl_seconds);

    if (headers_sent()) {
        return false;
    }

    $is_set = setcookie($normalized_key, $encoded_value, [
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

    $_COOKIE[$normalized_key] = $encoded_value;

    return true;
}

function cookie_get(?string $key = null, mixed $default = null, array $options = []): mixed
{
    if (harbor_is_blank($key)) {
        return cookie_all();
    }

    $normalized_key = cookie_internal_normalize_key($key);
    $cookies = cookie_all();
    if (! array_key_exists($normalized_key, $cookies)) {
        return $default;
    }

    $resolved_options = cookie_internal_resolve_options($options);
    $decoded_cookie = cookie_internal_decode_value($cookies[$normalized_key], $resolved_options);

    if ($decoded_cookie['is_secure'] && ! $decoded_cookie['is_valid']) {
        return $default;
    }

    return $decoded_cookie['value'];
}

function cookie_set_signed(string $key, string $value, string $signing_key, int $ttl_seconds = 0, array $options = []): bool
{
    $options['signed'] = true;
    $options['signing_key'] = $signing_key;

    return cookie_set($key, $value, $ttl_seconds, $options);
}

function cookie_get_signed(string $key, string $signing_key, mixed $default = null): mixed
{
    return cookie_get($key, $default, [
        'signed' => true,
        'signing_key' => $signing_key,
    ]);
}

function cookie_set_encrypted(string $key, string $value, string $encryption_key, int $ttl_seconds = 0, array $options = []): bool
{
    $options['encrypted'] = true;
    $options['encryption_key'] = $encryption_key;

    return cookie_set($key, $value, $ttl_seconds, $options);
}

function cookie_get_encrypted(string $key, string $encryption_key, mixed $default = null): mixed
{
    return cookie_get($key, $default, [
        'encrypted' => true,
        'encryption_key' => $encryption_key,
    ]);
}

function cookie_has(string $key): bool
{
    $normalized_key = cookie_internal_normalize_key($key);

    return array_key_exists($normalized_key, cookie_all());
}

function cookie_forget(string $key, array $options = []): bool
{
    $normalized_key = cookie_internal_normalize_key($key);
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
function cookie_internal_normalize_key(string $key): string
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

function cookie_internal_resolve_options(array $options): array
{
    $shared_key = cookie_internal_option_to_string($options['key'] ?? $options['secret'] ?? null);
    $is_signed = cookie_internal_value_to_bool($options['signed'] ?? false, false);
    $is_encrypted = cookie_internal_value_to_bool($options['encrypted'] ?? false, false);
    $signing_key = cookie_internal_option_to_string($options['signing_key'] ?? $shared_key);
    $encryption_key = cookie_internal_option_to_string($options['encryption_key'] ?? $shared_key);

    if ($is_signed && ! is_string($signing_key)) {
        throw new \InvalidArgumentException('Cookie signing key is required when "signed" is enabled.');
    }

    if ($is_encrypted && ! is_string($encryption_key)) {
        throw new \InvalidArgumentException('Cookie encryption key is required when "encrypted" is enabled.');
    }

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
        'secure' => cookie_internal_value_to_bool($options['secure'] ?? false, false),
        'http_only' => cookie_internal_value_to_bool($options['http_only'] ?? true, true),
        'same_site' => cookie_internal_resolve_same_site($options['same_site'] ?? 'Lax'),
        'signed' => $is_signed,
        'encrypted' => $is_encrypted,
        'signing_key' => $signing_key,
        'encryption_key' => $encryption_key,
    ];
}

function cookie_internal_resolve_same_site(mixed $same_site): string
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

function cookie_internal_resolve_expires_at(int $ttl_seconds): int
{
    if (0 === $ttl_seconds) {
        return 0;
    }

    return time() + $ttl_seconds;
}

function cookie_internal_value_to_bool(mixed $value, bool $default): bool
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

function cookie_internal_option_to_string(mixed $value): ?string
{
    if (! is_scalar($value) && ! is_string($value)) {
        return null;
    }

    $normalized_value = trim((string) $value);

    if (harbor_is_blank($normalized_value)) {
        return null;
    }

    return $normalized_value;
}

function cookie_internal_encode_value(string $value, array $options): string
{
    $encoded_value = $value;

    if ($options['encrypted']) {
        $encoded_value = cookie_internal_encrypt_value($encoded_value, $options['encryption_key']);
    }

    if ($options['signed']) {
        $encoded_value = cookie_internal_sign_value($encoded_value, $options['signing_key']);
    }

    return $encoded_value;
}

/**
 * @return array{is_secure: bool, is_valid: bool, value: mixed}
 */
function cookie_internal_decode_value(mixed $value, array $options): array
{
    $is_secure_cookie = $options['signed'] || $options['encrypted'];

    if (! $is_secure_cookie) {
        return [
            'is_secure' => false,
            'is_valid' => true,
            'value' => $value,
        ];
    }

    if (! is_string($value)) {
        return [
            'is_secure' => true,
            'is_valid' => false,
            'value' => null,
        ];
    }

    $decoded_value = $value;

    if ($options['signed']) {
        $decoded_value = cookie_internal_unsign_value($decoded_value, $options['signing_key']);

        if (! is_string($decoded_value)) {
            return [
                'is_secure' => true,
                'is_valid' => false,
                'value' => null,
            ];
        }
    }

    if ($options['encrypted']) {
        $decoded_value = cookie_internal_decrypt_value($decoded_value, $options['encryption_key']);

        if (! is_string($decoded_value)) {
            return [
                'is_secure' => true,
                'is_valid' => false,
                'value' => null,
            ];
        }
    }

    return [
        'is_secure' => true,
        'is_valid' => true,
        'value' => $decoded_value,
    ];
}

function cookie_internal_sign_value(string $value, string $signing_key): string
{
    $payload = cookie_internal_base64_url_encode($value);
    $signature = hash_hmac('sha256', $payload, $signing_key, true);

    return sprintf(
        'hcs1.%s.%s',
        $payload,
        cookie_internal_base64_url_encode($signature)
    );
}

function cookie_internal_unsign_value(string $signed_value, string $signing_key): ?string
{
    $parts = explode('.', $signed_value);
    if (3 !== count($parts)) {
        return null;
    }

    if ('hcs1' !== $parts[0]) {
        return null;
    }

    $payload = $parts[1];
    $provided_signature = cookie_internal_base64_url_decode($parts[2]);
    if (! is_string($provided_signature)) {
        return null;
    }

    $expected_signature = hash_hmac('sha256', $payload, $signing_key, true);
    if (! hash_equals($expected_signature, $provided_signature)) {
        return null;
    }

    return cookie_internal_base64_url_decode($payload);
}

function cookie_internal_encrypt_value(string $value, string $encryption_key): string
{
    if (! function_exists('openssl_encrypt')) {
        throw new \RuntimeException('Encrypted cookies require the OpenSSL extension.');
    }

    $cipher = 'aes-256-gcm';
    $initialization_vector = random_bytes(12);
    $tag = '';
    $encrypted_value = openssl_encrypt(
        $value,
        $cipher,
        cookie_internal_hash_key($encryption_key),
        OPENSSL_RAW_DATA,
        $initialization_vector,
        $tag,
        '',
        16
    );

    if (false === $encrypted_value || harbor_is_blank($tag)) {
        throw new \RuntimeException('Failed to encrypt cookie value.');
    }

    return sprintf(
        'hce1.%s.%s.%s',
        cookie_internal_base64_url_encode($initialization_vector),
        cookie_internal_base64_url_encode($tag),
        cookie_internal_base64_url_encode($encrypted_value)
    );
}

function cookie_internal_decrypt_value(string $encrypted_value, string $encryption_key): ?string
{
    if (! function_exists('openssl_decrypt')) {
        throw new \RuntimeException('Encrypted cookies require the OpenSSL extension.');
    }

    $parts = explode('.', $encrypted_value);
    if (4 !== count($parts)) {
        return null;
    }

    if ('hce1' !== $parts[0]) {
        return null;
    }

    $initialization_vector = cookie_internal_base64_url_decode($parts[1]);
    $tag = cookie_internal_base64_url_decode($parts[2]);
    $encrypted_payload = cookie_internal_base64_url_decode($parts[3]);

    if (! is_string($initialization_vector) || ! is_string($tag) || ! is_string($encrypted_payload)) {
        return null;
    }

    $decrypted_value = openssl_decrypt(
        $encrypted_payload,
        'aes-256-gcm',
        cookie_internal_hash_key($encryption_key),
        OPENSSL_RAW_DATA,
        $initialization_vector,
        $tag
    );

    if (false === $decrypted_value) {
        return null;
    }

    return $decrypted_value;
}

function cookie_internal_hash_key(string $key): string
{
    return hash('sha256', $key, true);
}

function cookie_internal_base64_url_encode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function cookie_internal_base64_url_decode(string $value): ?string
{
    if (harbor_is_blank(trim($value))) {
        return null;
    }

    $normalized_value = strtr($value, '-_', '+/');
    $padding_length = strlen($normalized_value) % 4;

    if (0 !== $padding_length) {
        $normalized_value .= str_repeat('=', 4 - $padding_length);
    }

    $decoded_value = base64_decode($normalized_value, true);

    if (false === $decoded_value) {
        return null;
    }

    return $decoded_value;
}
