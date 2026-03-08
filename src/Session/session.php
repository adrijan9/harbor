<?php

declare(strict_types=1);

namespace Harbor\Session;

require_once __DIR__.'/../Config/config.php';

require_once __DIR__.'/../Cookie/cookie.php';

require_once __DIR__.'/../Support/value.php';

use function Harbor\Config\config_bool;
use function Harbor\Config\config_get;
use function Harbor\Config\config_int;
use function Harbor\Config\config_str;
use function Harbor\Cookie\cookie_all;
use function Harbor\Cookie\cookie_forget;
use function Harbor\Cookie\cookie_get;
use function Harbor\Cookie\cookie_has;
use function Harbor\Cookie\cookie_set;
use function Harbor\Support\harbor_is_blank;

/** Public */
function session_set(string $key, mixed $value, ?int $ttl_seconds = null): bool
{
    $normalized_key = session_normalize_key($key);
    $resolved_ttl_seconds = is_int($ttl_seconds) ? $ttl_seconds : session_ttl_seconds();

    return cookie_set(
        session_cookie_name($normalized_key),
        session_encode_value($value),
        $resolved_ttl_seconds,
        session_cookie_options(),
    );
}

function session_get(?string $key = null, mixed $default = null): mixed
{
    if (harbor_is_blank($key)) {
        return session_all();
    }

    $normalized_key = session_normalize_key($key);
    $cookie_key = session_cookie_name($normalized_key);

    if (! cookie_has($cookie_key)) {
        return $default;
    }

    return session_decode_value(cookie_get($cookie_key));
}

function session_has(string $key): bool
{
    $normalized_key = session_normalize_key($key);

    return cookie_has(session_cookie_name($normalized_key));
}

function session_forget(string $key): bool
{
    $normalized_key = session_normalize_key($key);

    return cookie_forget(session_cookie_name($normalized_key), session_cookie_options());
}

function session_pull(string $key, mixed $default = null): mixed
{
    $value = session_get($key, $default);
    session_forget($key);

    return $value;
}

function session_all(): array
{
    $cookie_prefix = session_cookie_prefix().'_';
    $session_values = [];

    foreach (cookie_all() as $cookie_name => $cookie_value) {
        if (! is_string($cookie_name) || ! str_starts_with($cookie_name, $cookie_prefix)) {
            continue;
        }

        $session_key = rawurldecode(substr($cookie_name, strlen($cookie_prefix)));
        if (harbor_is_blank($session_key)) {
            continue;
        }

        $session_values[$session_key] = session_decode_value($cookie_value);
    }

    return $session_values;
}

function session_clear(): bool
{
    $session_keys = array_keys(session_all());

    if (empty($session_keys)) {
        return true;
    }

    $is_cleared = true;

    foreach ($session_keys as $session_key) {
        if (! is_string($session_key)) {
            continue;
        }

        if (! session_forget($session_key)) {
            $is_cleared = false;
        }
    }

    return $is_cleared;
}

function session_config(?string $key = null, mixed $default = null): mixed
{
    if (harbor_is_blank($key)) {
        return [
            'prefix' => session_cookie_prefix(),
            'ttl_seconds' => session_ttl_seconds(),
            'path' => session_cookie_path(),
            'domain' => session_cookie_domain(),
            'secure' => session_cookie_secure(),
            'http_only' => session_cookie_http_only(),
            'same_site' => session_cookie_same_site(),
        ];
    }

    return config_get('session.'.trim($key), $default);
}

/** Private */
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
