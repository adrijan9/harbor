<?php

declare(strict_types=1);

namespace PhpFramework\Request;

/**
 * Full request snapshot.
 */
function request(): array
{
    return [
        'method' => request_method(),
        'uri' => request_uri(),
        'path' => request_path(),
        'query_string' => request_query_string(),
        'scheme' => request_scheme(),
        'host' => request_host(),
        'port' => request_port(),
        'url' => request_url(),
        'full_url' => request_full_url(),
        'ip' => request_ip(),
        'user_agent' => request_user_agent(),
        'referer' => request_referer(),
        'is_secure' => request_is_secure(),
        'is_ajax' => request_is_ajax(),
        'is_json' => request_is_json(),
        'headers' => request_headers(),
        'body' => request_body(),
        'cookies' => request_cookies(),
        'files' => request_files(),
        'server' => request_server(),
        'route' => $GLOBALS['route'] ?? null,
    ];
}

/**
 * Basic request metadata.
 */
function request_method(): string
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    return strtoupper(is_string($method) ? $method : 'GET');
}

function request_is_get(): bool
{
    return request_matches_method('GET');
}

function request_is_post(): bool
{
    return request_matches_method('POST');
}

function request_is_put(): bool
{
    return request_matches_method('PUT');
}

function request_is_patch(): bool
{
    return request_matches_method('PATCH');
}

function request_is_delete(): bool
{
    return request_matches_method('DELETE');
}

function request_is_options(): bool
{
    return request_matches_method('OPTIONS');
}

function request_is_head(): bool
{
    return request_matches_method('HEAD');
}

function request_is_trace(): bool
{
    return request_matches_method('TRACE');
}

function request_is_connect(): bool
{
    return request_matches_method('CONNECT');
}

function request_uri(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';

    return is_string($uri) && '' !== $uri ? $uri : '/';
}

function request_path(): string
{
    $path = parse_url(request_uri(), PHP_URL_PATH);

    return is_string($path) && '' !== $path ? $path : '/';
}

function request_query_string(): string
{
    $query = parse_url(request_uri(), PHP_URL_QUERY);

    if (is_string($query)) {
        return $query;
    }

    $server_query = $_SERVER['QUERY_STRING'] ?? '';

    return is_string($server_query) ? $server_query : '';
}

function request_scheme(): string
{
    if (request_is_secure()) {
        return 'https';
    }

    return 'http';
}

function request_host(): string
{
    $header_host = request_header_str('host');
    if ('' !== $header_host) {
        $parts = explode(':', $header_host);

        return $parts[0];
    }

    $server_host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';

    return is_string($server_host) && '' !== $server_host ? $server_host : 'localhost';
}

function request_port(): int
{
    $header_host = request_header_str('host');
    if (str_contains($header_host, ':')) {
        $parts = explode(':', $header_host);
        $port = end($parts);

        if (is_string($port) && is_numeric($port)) {
            return (int) $port;
        }
    }

    $server_port = $_SERVER['SERVER_PORT'] ?? null;
    if (is_numeric($server_port)) {
        return (int) $server_port;
    }

    return 'https' === request_scheme() ? 443 : 80;
}

function request_url(): string
{
    $host = request_host();
    $scheme = request_scheme();
    $port = request_port();
    $path = request_path();
    $default_port = 'https' === $scheme ? 443 : 80;

    if ($port === $default_port) {
        return sprintf('%s://%s%s', $scheme, $host, $path);
    }

    return sprintf('%s://%s:%d%s', $scheme, $host, $port, $path);
}

function request_full_url(): string
{
    $query_string = request_query_string();

    if ('' === $query_string) {
        return request_url();
    }

    return request_url().'?'.$query_string;
}

function request_ip(): ?string
{
    $forwarded = request_header_str('x-forwarded-for');
    if ('' !== $forwarded) {
        $parts = explode(',', $forwarded);

        return trim($parts[0]);
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    return is_string($ip) && '' !== $ip ? $ip : null;
}

function request_user_agent(string $default = ''): string
{
    return request_header_str('user-agent', $default);
}

function request_referer(?string $default = null): ?string
{
    $referer = request_header('referer', $default);

    return is_string($referer) ? $referer : $default;
}

function request_is_secure(): bool
{
    $https = $_SERVER['HTTPS'] ?? null;
    if (is_string($https) && '' !== $https && 'off' !== strtolower($https)) {
        return true;
    }

    $server_port = $_SERVER['SERVER_PORT'] ?? null;
    if (is_numeric($server_port) && 443 === (int) $server_port) {
        return true;
    }

    $forwarded_proto = strtolower(request_header_str('x-forwarded-proto'));

    return 'https' === $forwarded_proto || request_header_bool('x-forwarded-ssl');
}

function request_is_ajax(): bool
{
    return 'xmlhttprequest' === strtolower(request_header_str('x-requested-with'));
}

function request_is_json(): bool
{
    $content_type = request_header_str('content-type');

    return str_contains(strtolower($content_type), 'application/json');
}

/**
 * Header helpers.
 */
function request_headers(): array
{
    static $headers = null;

    if (is_array($headers)) {
        return $headers;
    }

    $headers = [];

    if (function_exists('getallheaders')) {
        $all_headers = getallheaders();
        if (is_array($all_headers)) {
            foreach ($all_headers as $name => $value) {
                $headers[request_normalize_header_name((string) $name)] = $value;
            }
        }
    }

    foreach ($_SERVER as $name => $value) {
        if (! is_string($name)) {
            continue;
        }

        if (str_starts_with($name, 'HTTP_')) {
            $header_name = str_replace('_', '-', strtolower(substr($name, 5)));
            $headers[$header_name] = $value;

            continue;
        }

        if (in_array($name, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
            $header_name = str_replace('_', '-', strtolower($name));
            $headers[$header_name] = $value;
        }
    }

    return $headers;
}

function request_header(string $key, mixed $default = null): mixed
{
    $headers = request_headers();
    $normalized = request_normalize_header_name($key);

    return $headers[$normalized] ?? $default;
}

function request_header_exists(string $key): bool
{
    $headers = request_headers();
    $normalized = request_normalize_header_name($key);

    return array_key_exists($normalized, $headers);
}

function request_header_int(string $key, int $default = 0): int
{
    return request_value_to_int(request_header($key), $default);
}

function request_header_float(string $key, float $default = 0.0): float
{
    return request_value_to_float(request_header($key), $default);
}

function request_header_str(string $key, string $default = ''): string
{
    return request_value_to_str(request_header($key), $default);
}

function request_header_bool(string $key, bool $default = false): bool
{
    return request_value_to_bool(request_header($key), $default);
}

function request_header_arr(string $key, array $default = []): array
{
    return request_value_to_arr(request_header($key), $default);
}

function request_header_obj(string $key, ?object $default = null): ?object
{
    return request_value_to_obj(request_header($key), $default);
}

function request_header_json(string $key, mixed $default = null): mixed
{
    return request_value_to_json(request_header($key), $default);
}

/**
 * Body helpers.
 */
function request_raw_body(): string
{
    static $raw_body = null;

    if (is_string($raw_body)) {
        return $raw_body;
    }

    $raw = file_get_contents('php://input');
    if (! is_string($raw)) {
        $raw = '';
    }

    $raw_body = $raw;

    return $raw_body;
}

function request_body(?string $key = null, mixed $default = null): mixed
{
    $body = request_body_data();

    if (null === $key || '' === $key) {
        return $body;
    }

    if (is_array($body)) {
        return request_array_get($body, $key, $default);
    }

    if (is_object($body)) {
        return request_array_get((array) $body, $key, $default);
    }

    return $default;
}

function request_body_all(): array
{
    $body = request_body_data();

    if (is_array($body)) {
        return $body;
    }

    if (is_object($body)) {
        return (array) $body;
    }

    return [];
}

function request_body_count(): int
{
    return count(request_body_all());
}

function request_body_exists(string $key): bool
{
    return request_array_has(request_body_all(), $key);
}

function request_body_int(string $key, int $default = 0): int
{
    return request_value_to_int(request_body($key), $default);
}

function request_body_float(string $key, float $default = 0.0): float
{
    return request_value_to_float(request_body($key), $default);
}

function request_body_str(string $key, string $default = ''): string
{
    return request_value_to_str(request_body($key), $default);
}

function request_body_bool(string $key, bool $default = false): bool
{
    return request_value_to_bool(request_body($key), $default);
}

function request_body_arr(string $key, array $default = []): array
{
    return request_value_to_arr(request_body($key), $default);
}

function request_body_obj(string $key, ?object $default = null): ?object
{
    return request_value_to_obj(request_body($key), $default);
}

function request_body_json(string $key, mixed $default = null): mixed
{
    return request_value_to_json(request_body($key), $default);
}

/**
 * Input helpers (body only).
 */
function request_input(?string $key = null, mixed $default = null): mixed
{
    return request_body($key, $default);
}

function request_input_int(string $key, int $default = 0): int
{
    return request_value_to_int(request_input($key), $default);
}

function request_input_float(string $key, float $default = 0.0): float
{
    return request_value_to_float(request_input($key), $default);
}

function request_input_str(string $key, string $default = ''): string
{
    return request_value_to_str(request_input($key), $default);
}

function request_input_bool(string $key, bool $default = false): bool
{
    return request_value_to_bool(request_input($key), $default);
}

function request_input_arr(string $key, array $default = []): array
{
    return request_value_to_arr(request_input($key), $default);
}

function request_input_obj(string $key, ?object $default = null): ?object
{
    return request_value_to_obj(request_input($key), $default);
}

function request_input_json(string $key, mixed $default = null): mixed
{
    return request_value_to_json(request_input($key), $default);
}

/**
 * Cookie and file helpers.
 */
function request_cookie(?string $key = null, mixed $default = null): mixed
{
    if (null === $key || '' === $key) {
        return request_cookies();
    }

    return request_array_get(request_cookies(), $key, $default);
}

function request_cookies(): array
{
    return is_array($_COOKIE) ? $_COOKIE : [];
}

function request_cookie_exists(string $key): bool
{
    return request_array_has(request_cookies(), $key);
}

function request_files(?string $key = null, mixed $default = null): mixed
{
    $files = is_array($_FILES) ? $_FILES : [];

    if (null === $key || '' === $key) {
        return $files;
    }

    return request_array_get($files, $key, $default);
}

function request_file(string $key, mixed $default = null): mixed
{
    return request_files($key, $default);
}

function request_has_file(string $key): bool
{
    return request_array_has(is_array($_FILES) ? $_FILES : [], $key);
}

/**
 * Raw server helper.
 */
function request_server(?string $key = null, mixed $default = null): mixed
{
    $server = is_array($_SERVER) ? $_SERVER : [];

    if (null === $key || '' === $key) {
        return $server;
    }

    return request_array_get($server, $key, $default);
}

/**
 * Internal helpers.
 */
function request_body_data(): mixed
{
    static $parsed_body = null;
    static $has_parsed_body = false;

    if ($has_parsed_body) {
        return $parsed_body;
    }

    $has_parsed_body = true;

    if (is_array($_POST) && [] !== $_POST) {
        $parsed_body = $_POST;

        return $parsed_body;
    }

    $raw_body = request_raw_body();
    if ('' === $raw_body) {
        $parsed_body = [];

        return $parsed_body;
    }

    if (request_is_json()) {
        $decoded_json = request_decode_json($raw_body, true);
        if (null !== $decoded_json) {
            $parsed_body = $decoded_json;

            return $parsed_body;
        }
    }

    if (str_contains(strtolower(request_header_str('content-type')), 'application/x-www-form-urlencoded')) {
        parse_str($raw_body, $form_data);
        $parsed_body = is_array($form_data) ? $form_data : [];

        return $parsed_body;
    }

    $decoded = request_decode_json($raw_body, true);
    if (null !== $decoded) {
        $parsed_body = $decoded;

        return $parsed_body;
    }

    $parsed_body = [];

    return $parsed_body;
}

function request_normalize_header_name(string $key): string
{
    $normalized = strtolower(trim($key));
    $normalized = str_replace('_', '-', $normalized);

    return preg_replace('/\s+/', '', $normalized) ?? $normalized;
}

function request_matches_method(string $method): bool
{
    return request_method() === strtoupper($method);
}

function request_array_get(array $array, string $key, mixed $default = null): mixed
{
    if (array_key_exists($key, $array)) {
        return $array[$key];
    }

    $keys = explode('.', $key);
    $current = $array;

    foreach ($keys as $segment_key) {
        if (! is_array($current) || ! array_key_exists($segment_key, $current)) {
            return $default;
        }

        $current = $current[$segment_key];
    }

    return $current;
}

function request_array_has(array $array, string $key): bool
{
    if (array_key_exists($key, $array)) {
        return true;
    }

    $keys = explode('.', $key);
    $current = $array;

    foreach ($keys as $segment_key) {
        if (! is_array($current) || ! array_key_exists($segment_key, $current)) {
            return false;
        }

        $current = $current[$segment_key];
    }

    return true;
}

function request_decode_json(string $value, bool $assoc): mixed
{
    $decoded = json_decode(rawurldecode($value), $assoc);

    if (JSON_ERROR_NONE !== json_last_error()) {
        return null;
    }

    return $decoded;
}

function request_value_to_int(mixed $value, int $default = 0): int
{
    if (is_int($value)) {
        return $value;
    }

    if (is_numeric($value)) {
        return (int) $value;
    }

    return $default;
}

function request_value_to_float(mixed $value, float $default = 0.0): float
{
    if (is_float($value) || is_int($value)) {
        return (float) $value;
    }

    if (is_string($value) && is_numeric($value)) {
        return (float) $value;
    }

    return $default;
}

function request_value_to_str(mixed $value, string $default = ''): string
{
    if (is_string($value)) {
        return $value;
    }

    if (is_scalar($value)) {
        return (string) $value;
    }

    if (is_object($value) && method_exists($value, '__toString')) {
        return (string) $value;
    }

    return $default;
}

function request_value_to_bool(mixed $value, bool $default = false): bool
{
    if (is_bool($value)) {
        return $value;
    }

    if (is_int($value) || is_float($value)) {
        return 0.0 !== (float) $value;
    }

    if (is_string($value)) {
        $parsed = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        if (null !== $parsed) {
            return $parsed;
        }
    }

    return $default;
}

function request_value_to_arr(mixed $value, array $default = []): array
{
    if (is_array($value)) {
        return $value;
    }

    if ($value instanceof \Traversable) {
        return iterator_to_array($value);
    }

    if (is_object($value)) {
        return (array) $value;
    }

    if (is_string($value)) {
        $decoded = request_decode_json($value, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        $decoded_value = rawurldecode($value);
        if (str_contains($decoded_value, ',')) {
            return array_values(array_filter(
                array_map('trim', explode(',', $decoded_value)),
                static fn (string $item): bool => '' !== $item
            ));
        }
    }

    return $default;
}

function request_value_to_obj(mixed $value, ?object $default = null): ?object
{
    if (is_object($value)) {
        return $value;
    }

    if (is_array($value)) {
        return (object) $value;
    }

    if (is_string($value)) {
        $decoded = request_decode_json($value, false);

        if (is_object($decoded)) {
            return $decoded;
        }

        if (is_array($decoded)) {
            return (object) $decoded;
        }
    }

    return $default;
}

function request_value_to_json(mixed $value, mixed $default = null): mixed
{
    if (! is_string($value)) {
        return $default;
    }

    $decoded = request_decode_json($value, true);

    return $decoded ?? $default;
}

if (! isset($GLOBALS['request']) || ! is_array($GLOBALS['request'])) {
    $GLOBALS['request'] = request();
}
