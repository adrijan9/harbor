<?php

declare(strict_types=1);

namespace Harbor\Response;

require_once __DIR__.'/../Support/value.php';
require_once __DIR__.'/../Validation/ValidationResult.php';

use function Harbor\Support\harbor_is_blank;
use function Harbor\Support\harbor_is_null;

function response_status(int $status): void
{
    if (headers_sent()) {
        return;
    }

    http_response_code($status);
}

function response_header(string $name, string $value, bool $replace = true): void
{
    $normalized_name = trim($name);
    if (harbor_is_blank($normalized_name)) {
        throw new \InvalidArgumentException('Response header name must be a non-empty string.');
    }

    if (headers_sent()) {
        return;
    }

    header($normalized_name.': '.$value, $replace);
}

function response_json(array $payload, int $status = 200, array $headers = []): void
{
    response_status($status);

    if (! response_headers_has_key($headers, 'content-type')) {
        response_header('Content-Type', 'application/json; charset=UTF-8');
    }

    response_apply_headers($headers);

    $json_content = json_encode(
        $payload,
        JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
    );

    echo is_string($json_content) ? $json_content : '{}';
}

function response_text(string $content, int $status = 200, array $headers = []): void
{
    response_status($status);

    if (! response_headers_has_key($headers, 'content-type')) {
        response_header('Content-Type', 'text/plain; charset=UTF-8');
    }

    response_apply_headers($headers);

    echo $content;
}

function response_file(string $file_path, ?string $download_name = null, array $headers = []): void
{
    if (! is_file($file_path)) {
        throw new \RuntimeException(sprintf('Response file "%s" not found.', $file_path));
    }

    $resolved_download_name = null;
    if (! harbor_is_null($download_name)) {
        $resolved_download_name = response_normalize_download_name($download_name);
    }

    response_status(200);

    if (! response_headers_has_key($headers, 'content-type')) {
        response_header('Content-Type', response_resolve_file_mime_type($file_path));
    }

    $file_size = filesize($file_path);
    if (false !== $file_size && ! response_headers_has_key($headers, 'content-length')) {
        response_header('Content-Length', (string) $file_size);
    }

    if (! harbor_is_null($resolved_download_name) && ! response_headers_has_key($headers, 'content-disposition')) {
        response_header('Content-Disposition', 'attachment; filename="'.$resolved_download_name.'"');
    }

    response_apply_headers($headers);

    if (false === readfile($file_path)) {
        throw new \RuntimeException(sprintf('Failed to read response file "%s".', $file_path));
    }
}

function response_download(string $file_path, ?string $download_name = null, array $headers = []): void
{
    $resolved_download_name = $download_name;

    if (harbor_is_null($resolved_download_name)) {
        $resolved_download_name = basename($file_path);
    }

    response_file($file_path, $resolved_download_name, $headers);
}

function response_validation(\Harbor\Validation\ValidationResult $result, int $status = 422, array $headers = []): void
{
    $payload = [
        'message' => 'Validation failed.',
        'errors' => $result->errors(),
    ];

    if (response_request_prefers_json()) {
        response_json($payload, $status, $headers);

        return;
    }

    response_status($status);

    if (! response_headers_has_key($headers, 'content-type')) {
        response_header('Content-Type', 'text/plain; charset=UTF-8');
    }

    response_apply_headers($headers);

    echo $payload['message'];
}

function response_apply_headers(array $headers): void
{
    foreach ($headers as $name => $value) {
        if (! is_string($name)) {
            continue;
        }

        $normalized_name = trim($name);
        if (harbor_is_blank($normalized_name)) {
            continue;
        }

        response_header($normalized_name, response_header_value_to_string($value));
    }
}

function response_headers_has_key(array $headers, string $key): bool
{
    $normalized_key = strtolower(trim($key));
    if (harbor_is_blank($normalized_key)) {
        return false;
    }

    foreach ($headers as $header_name => $value) {
        if (! is_string($header_name)) {
            continue;
        }

        if ($normalized_key === strtolower(trim($header_name))) {
            return true;
        }
    }

    return false;
}

function response_header_value_to_string(mixed $value): string
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

    if (harbor_is_null($value)) {
        return '';
    }

    try {
        $encoded_value = json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );
    } catch (\JsonException) {
        return '';
    }

    return is_string($encoded_value) ? $encoded_value : '';
}

function response_resolve_file_mime_type(string $file_path): string
{
    $mime_type = function_exists('mime_content_type') ? mime_content_type($file_path) : null;
    if (is_string($mime_type) && ! harbor_is_blank($mime_type)) {
        return $mime_type;
    }

    return 'application/octet-stream';
}

function response_normalize_download_name(string $download_name): string
{
    $normalized_download_name = trim($download_name);
    if (harbor_is_blank($normalized_download_name)) {
        throw new \InvalidArgumentException('Response download name must be a non-empty string.');
    }

    $sanitized_download_name = str_replace(["\r", "\n", '"'], '', $normalized_download_name);
    if (harbor_is_blank($sanitized_download_name)) {
        throw new \InvalidArgumentException('Response download name must contain valid characters.');
    }

    return $sanitized_download_name;
}

function response_request_prefers_json(): bool
{
    $accept_header = $_SERVER['HTTP_ACCEPT'] ?? null;
    if (is_string($accept_header) && ! harbor_is_blank($accept_header)) {
        $normalized_accept = strtolower($accept_header);
        if (str_contains($normalized_accept, 'application/json') || str_contains($normalized_accept, '+json')) {
            return true;
        }
    }

    $requested_with = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? null;
    if (is_string($requested_with) && strtolower(trim($requested_with)) === 'xmlhttprequest') {
        return true;
    }

    return false;
}
