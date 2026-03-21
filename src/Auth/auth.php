<?php

declare(strict_types=1);

namespace Harbor\Auth;

require_once __DIR__.'/../Config/config.php';

require_once __DIR__.'/../Support/value.php';

use function Harbor\Config\config_get;
use function Harbor\Support\harbor_is_blank;

/** Public */
function auth_attempt(array $credentials): ?array
{
    $attempt_resolver = auth_internal_attempt_resolver();

    if (! $attempt_resolver instanceof \Closure) {
        return null;
    }

    $resolved_user = $attempt_resolver($credentials);

    return auth_internal_normalize_user($resolved_user);
}

/** Private */
function auth_internal_attempt_resolver(): ?\Closure
{
    $web_attempt_resolver = auth_internal_value_to_callable(config_get('auth.web.attempt_resolver'));
    if ($web_attempt_resolver instanceof \Closure) {
        return $web_attempt_resolver;
    }

    return auth_internal_value_to_callable(config_get('auth.api.attempt_resolver'));
}

function auth_internal_normalize_user(mixed $user): ?array
{
    if (! is_array($user) || empty($user)) {
        return null;
    }

    $normalized_user = [];

    foreach ($user as $key => $value) {
        if (! is_string($key) || harbor_is_blank(trim($key))) {
            continue;
        }

        $normalized_user[trim($key)] = $value;
    }

    return empty($normalized_user) ? null : $normalized_user;
}

function auth_internal_value_to_string(mixed $value): ?string
{
    if (! is_scalar($value) && ! (is_object($value) && method_exists($value, '__toString'))) {
        return null;
    }

    $normalized_value = trim((string) $value);

    return harbor_is_blank($normalized_value) ? null : $normalized_value;
}

function auth_internal_value_to_int(mixed $value, int $default = 0): int
{
    if (is_int($value)) {
        return $value;
    }

    if (is_numeric($value)) {
        return (int) $value;
    }

    return $default;
}

function auth_internal_value_to_callable(mixed $value): ?\Closure
{
    if (! is_callable($value)) {
        return null;
    }

    return \Closure::fromCallable($value);
}
