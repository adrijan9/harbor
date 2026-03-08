<?php

declare(strict_types=1);

namespace Harbor\Session;

require_once __DIR__.'/../Config/config.php';

require_once __DIR__.'/../Cookie/cookie.php';

require_once __DIR__.'/../Support/value.php';

require_once __DIR__.'/SessionDriver.php';

require_once __DIR__.'/session_array.php';

require_once __DIR__.'/session_file.php';

use function Harbor\Config\config_bool;
use function Harbor\Config\config_get;
use function Harbor\Config\config_int;
use function Harbor\Config\config_resolve;
use function Harbor\Config\config_str;
use function Harbor\Cookie\cookie_all;
use function Harbor\Cookie\cookie_forget;
use function Harbor\Cookie\cookie_get;
use function Harbor\Cookie\cookie_has;
use function Harbor\Cookie\cookie_set;
use function Harbor\Support\harbor_is_blank;

/** Public */
function session_driver(SessionDriver|string $default_driver = SessionDriver::COOKIE): string
{
    $resolved_default_driver = session_resolve_driver($default_driver);
    if (null === $resolved_default_driver) {
        $resolved_default_driver = SessionDriver::COOKIE;
    }

    $configured_driver = config_resolve('session.driver', 'session_driver', $resolved_default_driver->value);
    $resolved_driver = session_resolve_driver($configured_driver);

    if (null === $resolved_driver) {
        return $resolved_default_driver->value;
    }

    return $resolved_driver->value;
}

function session_is_cookie(): bool
{
    return SessionDriver::COOKIE->value === session_driver();
}

function session_is_array(): bool
{
    return SessionDriver::ARRAY->value === session_driver();
}

function session_is_file(): bool
{
    return SessionDriver::FILE->value === session_driver();
}

function session_set(string $key, mixed $value, ?int $ttl_seconds = null): bool
{
    $normalized_key = session_normalize_key($key);
    $resolved_ttl_seconds = is_int($ttl_seconds) ? $ttl_seconds : session_ttl_seconds();

    if (session_is_file()) {
        return session_file_set($normalized_key, $value, $resolved_ttl_seconds, session_cookie_options());
    }

    if (session_is_array()) {
        return session_array_set($normalized_key, $value, $resolved_ttl_seconds);
    }

    return session_cookie_driver_set($normalized_key, $value, $resolved_ttl_seconds);
}

function session_get(?string $key = null, mixed $default = null): mixed
{
    if (harbor_is_blank($key)) {
        return session_all();
    }

    $normalized_key = session_normalize_key($key);

    if (session_is_file()) {
        return session_file_get($normalized_key, $default, session_cookie_options());
    }

    if (session_is_array()) {
        return session_array_get($normalized_key, $default);
    }

    return session_cookie_driver_get($normalized_key, $default);
}

function session_has(string $key): bool
{
    $normalized_key = session_normalize_key($key);

    if (session_is_file()) {
        return session_file_has($normalized_key, session_cookie_options());
    }

    if (session_is_array()) {
        return session_array_has($normalized_key);
    }

    return session_cookie_driver_has($normalized_key);
}

function session_forget(string $key): bool
{
    $normalized_key = session_normalize_key($key);

    if (session_is_file()) {
        return session_file_forget($normalized_key, session_cookie_options());
    }

    if (session_is_array()) {
        return session_array_forget($normalized_key);
    }

    return session_cookie_driver_forget($normalized_key);
}

function session_pull(string $key, mixed $default = null): mixed
{
    $value = session_get($key, $default);
    session_forget($key);

    return $value;
}

function session_all(): array
{
    if (session_is_file()) {
        return session_file_all(session_cookie_options());
    }

    if (session_is_array()) {
        return session_array_all();
    }

    return session_cookie_driver_all();
}

function session_clear(): bool
{
    if (session_is_file()) {
        return session_file_clear(session_cookie_options());
    }

    if (session_is_array()) {
        return session_array_clear();
    }

    return session_cookie_driver_clear();
}

function session_config(?string $key = null, mixed $default = null): mixed
{
    if (harbor_is_blank($key)) {
        return [
            'driver' => session_driver(),
            'prefix' => session_cookie_prefix(),
            'ttl_seconds' => session_ttl_seconds(),
            'path' => session_cookie_path(),
            'domain' => session_cookie_domain(),
            'secure' => session_cookie_secure(),
            'http_only' => session_cookie_http_only(),
            'same_site' => session_cookie_same_site(),
            'signed' => session_cookie_signed(),
            'encrypted' => session_cookie_encrypted(),
            'signing_key' => session_cookie_signing_key(),
            'encryption_key' => session_cookie_encryption_key(),
            'file_path' => session_file_root_path(),
            'id_cookie' => session_file_id_cookie_name(),
        ];
    }

    return config_get('session.'.trim($key), $default);
}

/** Private */
function session_cookie_driver_set(string $key, mixed $value, int $ttl_seconds): bool
{
    return cookie_set(
        session_cookie_name($key),
        session_encode_value($value),
        $ttl_seconds,
        session_cookie_options(),
    );
}

function session_cookie_driver_get(string $key, mixed $default = null): mixed
{
    $cookie_key = session_cookie_name($key);

    if (! cookie_has($cookie_key)) {
        return $default;
    }

    $cookie_value = cookie_get($cookie_key, null, session_cookie_options());
    if (null === $cookie_value) {
        return $default;
    }

    return session_decode_value($cookie_value);
}

function session_cookie_driver_has(string $key): bool
{
    return cookie_has(session_cookie_name($key));
}

function session_cookie_driver_forget(string $key): bool
{
    return cookie_forget(session_cookie_name($key), session_cookie_options());
}

function session_cookie_driver_all(): array
{
    $cookie_prefix = session_cookie_prefix().'_';
    $session_values = [];
    $session_cookie_options = session_cookie_options();

    foreach (array_keys(cookie_all()) as $cookie_name) {
        if (! is_string($cookie_name) || ! str_starts_with($cookie_name, $cookie_prefix)) {
            continue;
        }

        $session_key = rawurldecode(substr($cookie_name, strlen($cookie_prefix)));
        if (harbor_is_blank($session_key)) {
            continue;
        }

        $decoded_cookie_value = cookie_get($cookie_name, null, $session_cookie_options);
        if (null === $decoded_cookie_value) {
            continue;
        }

        $session_values[$session_key] = session_decode_value($decoded_cookie_value);
    }

    return $session_values;
}

function session_cookie_driver_clear(): bool
{
    $session_keys = array_keys(session_cookie_driver_all());

    if (empty($session_keys)) {
        return true;
    }

    $is_cleared = true;

    foreach ($session_keys as $session_key) {
        if (! is_string($session_key)) {
            continue;
        }

        if (! session_cookie_driver_forget($session_key)) {
            $is_cleared = false;
        }
    }

    return $is_cleared;
}

function session_cookie_name(string $key): string
{
    return session_cookie_prefix().'_'.rawurlencode($key);
}

function session_cookie_options(): array
{
    return [
        'path' => session_cookie_path(),
        'domain' => session_cookie_domain(),
        'secure' => session_cookie_secure(),
        'http_only' => session_cookie_http_only(),
        'same_site' => session_cookie_same_site(),
        'signed' => session_cookie_signed(),
        'encrypted' => session_cookie_encrypted(),
        'signing_key' => session_cookie_signing_key(),
        'encryption_key' => session_cookie_encryption_key(),
    ];
}

function session_cookie_prefix(): string
{
    $prefix = trim(config_str('session.prefix', 'harbor'));

    if (harbor_is_blank($prefix)) {
        return 'harbor';
    }

    return $prefix;
}

function session_ttl_seconds(): int
{
    $ttl_seconds = config_int('session.ttl_seconds', 7200);

    if ($ttl_seconds < 0) {
        return 0;
    }

    return $ttl_seconds;
}

function session_cookie_path(): string
{
    $path = trim(config_str('session.path', '/'));

    if (harbor_is_blank($path)) {
        return '/';
    }

    return $path;
}

function session_cookie_domain(): ?string
{
    $domain = config_get('session.domain');

    if (! is_string($domain)) {
        return null;
    }

    $normalized_domain = trim($domain);

    if (harbor_is_blank($normalized_domain)) {
        return null;
    }

    return $normalized_domain;
}

function session_cookie_secure(): bool
{
    return config_bool('session.secure', false);
}

function session_cookie_http_only(): bool
{
    return config_bool('session.http_only', true);
}

function session_cookie_same_site(): string
{
    return session_normalize_same_site(config_str('session.same_site', 'Lax'));
}

function session_cookie_signed(): bool
{
    return config_bool('session.signed', false);
}

function session_cookie_encrypted(): bool
{
    return config_bool('session.encrypted', false);
}

function session_cookie_signing_key(): ?string
{
    $signing_key = config_resolve('session.signing_key', 'session.key');

    if (! is_string($signing_key)) {
        return null;
    }

    $normalized_signing_key = trim($signing_key);

    if (harbor_is_blank($normalized_signing_key)) {
        return null;
    }

    return $normalized_signing_key;
}

function session_cookie_encryption_key(): ?string
{
    $encryption_key = config_resolve('session.encryption_key', 'session.key');

    if (! is_string($encryption_key)) {
        return null;
    }

    $normalized_encryption_key = trim($encryption_key);

    if (harbor_is_blank($normalized_encryption_key)) {
        return null;
    }

    return $normalized_encryption_key;
}

function session_normalize_key(string $key): string
{
    $normalized_key = trim($key);
    if (harbor_is_blank($normalized_key)) {
        throw new \InvalidArgumentException('Session key cannot be empty.');
    }

    return $normalized_key;
}

function session_normalize_same_site(string $same_site): string
{
    $normalized_same_site = strtolower(trim($same_site));
    if (harbor_is_blank($normalized_same_site)) {
        return 'Lax';
    }

    return match ($normalized_same_site) {
        'strict' => 'Strict',
        'none' => 'None',
        'lax' => 'Lax',
        default => throw new \InvalidArgumentException(
            sprintf('Invalid session same_site value "%s". Use "Lax", "Strict", or "None".', $same_site)
        ),
    };
}

function session_encode_value(mixed $value): string
{
    try {
        return json_encode(
            [
                '__harbor' => true,
                'value' => $value,
            ],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );
    } catch (\JsonException $exception) {
        throw new \InvalidArgumentException(
            'Session value cannot be encoded as JSON.',
            previous: $exception,
        );
    }
}

function session_decode_value(mixed $value): mixed
{
    if (! is_string($value)) {
        return $value;
    }

    $decoded_value = json_decode(rawurldecode($value), true);

    if (! is_array($decoded_value)) {
        return $value;
    }

    if (true !== ($decoded_value['__harbor'] ?? false)) {
        return $value;
    }

    if (! array_key_exists('value', $decoded_value)) {
        return $value;
    }

    return $decoded_value['value'];
}

function session_resolve_driver(mixed $driver): ?SessionDriver
{
    if ($driver instanceof SessionDriver) {
        return $driver;
    }

    if (! is_string($driver)) {
        return null;
    }

    $normalized_driver = strtolower(trim($driver));

    if (harbor_is_blank($normalized_driver)) {
        return null;
    }

    foreach (SessionDriver::cases() as $driver_case) {
        if ($driver_case->value === $normalized_driver) {
            return $driver_case;
        }
    }

    return null;
}
