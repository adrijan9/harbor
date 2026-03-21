<?php

declare(strict_types=1);

namespace Harbor\Auth;

require_once __DIR__.'/auth.php';

require_once __DIR__.'/../Session/session.php';

use function Harbor\Config\config_get;
use function Harbor\Session\session_forget;
use function Harbor\Session\session_get;
use function Harbor\Session\session_set;

/** Public */
function auth_web_exists(): bool
{
    return is_array(auth_web_get());
}

function auth_web_get(): ?array
{
    $web_user = session_get(auth_web_internal_session_key(), null);

    return auth_internal_normalize_user($web_user);
}

function auth_web_login(array $user): bool
{
    $normalized_user = auth_internal_normalize_user($user);

    if (null === $normalized_user) {
        throw new \InvalidArgumentException('Web auth user payload must be a non-empty array.');
    }

    return session_set(auth_web_internal_session_key(), $normalized_user);
}

function auth_web_logout(): bool
{
    return session_forget(auth_web_internal_session_key());
}

/** Private */
function auth_web_internal_session_key(): string
{
    $web_session_key = auth_internal_value_to_string(config_get('auth.web.session_key', 'auth_web_user'));

    return is_string($web_session_key) ? $web_session_key : 'auth_web_user';
}
