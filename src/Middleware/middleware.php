<?php

declare(strict_types=1);

namespace Harbor\Middleware;

require_once __DIR__.'/../Cookie/cookie.php';

require_once __DIR__.'/../Pipeline/pipeline.php';

require_once __DIR__.'/../Request/request.php';

require_once __DIR__.'/../Support/value.php';

use function Harbor\Cookie\cookie_get;
use function Harbor\Cookie\cookie_set;
use function Harbor\Pipeline\pipeline_clog;
use function Harbor\Pipeline\pipeline_new;
use function Harbor\Pipeline\pipeline_send;
use function Harbor\Pipeline\pipeline_through;
use function Harbor\Request\request;
use function Harbor\Support\harbor_is_blank;

/** Public */
function middleware(callable ...$actions): void
{
    $pipeline = pipeline_new();

    pipeline_send($pipeline, request());
    pipeline_through($pipeline, ...$actions);
    pipeline_clog($pipeline);
}

function csrf_token(
    string $cookie_token_key = 'XSRF-TOKEN',
    int $cookie_ttl_seconds = 0,
    array $cookie_options = ['http_only' => false, 'same_site' => 'Lax'],
): string {
    $normalized_cookie_token_key = trim($cookie_token_key);
    if (harbor_is_blank($normalized_cookie_token_key)) {
        throw new \InvalidArgumentException('CSRF cookie token key cannot be empty.');
    }

    $existing_token = cookie_get($normalized_cookie_token_key, null, $cookie_options);
    if (is_string($existing_token) && ! harbor_is_blank(trim($existing_token))) {
        return trim($existing_token);
    }

    $generated_token = middleware_generate_csrf_token();
    $is_cookie_set = cookie_set(
        $normalized_cookie_token_key,
        $generated_token,
        $cookie_ttl_seconds,
        $cookie_options
    );

    if (! $is_cookie_set) {
        throw new \RuntimeException('Unable to persist CSRF token cookie. Ensure headers are not already sent.');
    }

    return $generated_token;
}

function csrf_field(
    string $field_name = '_token',
    string $cookie_token_key = 'XSRF-TOKEN',
    int $cookie_ttl_seconds = 0,
    array $cookie_options = ['http_only' => false, 'same_site' => 'Lax'],
): string {
    $normalized_field_name = trim($field_name);
    if (harbor_is_blank($normalized_field_name)) {
        throw new \InvalidArgumentException('CSRF field name cannot be empty.');
    }

    $token = csrf_token($cookie_token_key, $cookie_ttl_seconds, $cookie_options);

    return sprintf(
        '<input type="hidden" name="%s" value="%s">',
        middleware_escape_html($normalized_field_name),
        middleware_escape_html($token)
    );
}

/** Private */
function middleware_generate_csrf_token(): string
{
    try {
        return bin2hex(random_bytes(32));
    } catch (\Throwable $exception) {
        throw new \RuntimeException('Failed to generate a CSRF token.', previous: $exception);
    }
}

function middleware_escape_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
