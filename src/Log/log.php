<?php

declare(strict_types=1);

namespace Harbor\Log;

require_once __DIR__.'/LogLevel.php';

require_once __DIR__.'/../Filesystem/filesystem.php';
require_once __DIR__.'/../Support/value.php';

use function Harbor\Filesystem\fs_append;
use function Harbor\Filesystem\fs_dir_create;
use function Harbor\Filesystem\fs_dir_exists;
use function Harbor\Filesystem\fs_exists;
use function Harbor\Filesystem\fs_write;
use function Harbor\Support\harbor_is_blank;
use function Harbor\Support\harbor_is_null;

$log_file_path = null;
$log_is_initialized = false;
$log_default_channel = 'app';

function log_init(string $file_path, string $channel = 'app'): void
{
    global $log_file_path, $log_is_initialized, $log_default_channel;

    $normalized_file_path = trim($file_path);
    if (harbor_is_blank($normalized_file_path)) {
        throw new \InvalidArgumentException('Log file path cannot be empty.');
    }

    $directory_path = dirname($normalized_file_path);
    if (! fs_dir_exists($directory_path)) {
        fs_dir_create($directory_path);
    }

    if (! fs_exists($normalized_file_path)) {
        fs_write($normalized_file_path, '');
    }

    $log_file_path = $normalized_file_path;
    $log_is_initialized = true;
    $log_default_channel = log_validate_channel($channel);
}

function log_reset(): void
{
    global $log_file_path, $log_is_initialized, $log_default_channel;

    $log_file_path = null;
    $log_is_initialized = false;
    $log_default_channel = 'app';
}

function log_is_initialized(): bool
{
    global $log_is_initialized;

    return $log_is_initialized;
}

function log_file_path(): ?string
{
    global $log_file_path;

    return is_string($log_file_path) ? $log_file_path : null;
}

function log_set_channel(string $channel): void
{
    global $log_default_channel;

    $log_default_channel = log_validate_channel($channel);
}

function log_write(LogLevel|string $level, string $message, array $context = [], ?string $channel = null): void
{
    $log_content = log_create_content($level, $message, $context, $channel);

    log_write_content($log_content);
}

function log_create_content(LogLevel|string $level, string $message, array $context = [], ?string $channel = null): string
{
    global $log_default_channel;

    $normalized_level = log_validate_level($level);
    $channel_candidate = harbor_is_null($channel) ? $log_default_channel : $channel;
    $normalized_channel = log_validate_channel($channel_candidate);
    $interpolated_message = log_interpolate_message($message, $context);
    $normalized_context = log_normalize_context($context);
    $context_json = log_encode_context($normalized_context);

    $timestamp = date('Y-m-d H:i:s');
    $log_content = sprintf('[%s] [%s] [%s] %s', $timestamp, strtoupper($normalized_level), $normalized_channel, $interpolated_message);

    if ('' !== $context_json) {
        $log_content .= ' | '.$context_json;
    }

    return $log_content;
}

function log_write_content(string $log_content): void
{
    global $log_file_path;

    log_bail_if_not_initialized();

    $normalized_content = str_ends_with($log_content, PHP_EOL) ? $log_content : $log_content.PHP_EOL;

    fs_append($log_file_path, $normalized_content);
}

function log_debug(string $message, array $context = [], ?string $channel = null): void
{
    log_write(LogLevel::DEBUG, $message, $context, $channel);
}

function log_info(string $message, array $context = [], ?string $channel = null): void
{
    log_write(LogLevel::INFO, $message, $context, $channel);
}

function log_notice(string $message, array $context = [], ?string $channel = null): void
{
    log_write(LogLevel::NOTICE, $message, $context, $channel);
}

function log_warning(string $message, array $context = [], ?string $channel = null): void
{
    log_write(LogLevel::WARNING, $message, $context, $channel);
}

function log_error(string $message, array $context = [], ?string $channel = null): void
{
    log_write(LogLevel::ERROR, $message, $context, $channel);
}

function log_critical(string $message, array $context = [], ?string $channel = null): void
{
    log_write(LogLevel::CRITICAL, $message, $context, $channel);
}

function log_alert(string $message, array $context = [], ?string $channel = null): void
{
    log_write(LogLevel::ALERT, $message, $context, $channel);
}

function log_emergency(string $message, array $context = [], ?string $channel = null): void
{
    log_write(LogLevel::EMERGENCY, $message, $context, $channel);
}

function log_exception(\Throwable $exception, array $context = [], LogLevel|string $level = LogLevel::ERROR, string $message = 'Unhandled exception', ?string $channel = null): void
{
    $exception_context = [
        'exception' => [
            'class' => $exception::class,
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ],
    ];

    log_write($level, $message, array_merge($context, $exception_context), $channel);
}

function log_bail_if_not_initialized(): void
{
    global $log_is_initialized, $log_file_path;

    if (! $log_is_initialized || ! is_string($log_file_path) || harbor_is_blank($log_file_path)) {
        throw new \RuntimeException('Log file not initialized. Call log_init() first.');
    }
}

function log_validate_level(LogLevel|string $level): string
{
    if ($level instanceof LogLevel) {
        return $level->value;
    }

    $normalized_level = strtolower(trim($level));
    $log_level = LogLevel::tryFrom($normalized_level);

    if (harbor_is_null($log_level)) {
        throw new \InvalidArgumentException(sprintf('Invalid log level "%s".', $level));
    }

    return $log_level->value;
}

function log_validate_channel(string $channel): string
{
    $normalized_channel = trim($channel);

    if (harbor_is_blank($normalized_channel)) {
        throw new \InvalidArgumentException('Log channel cannot be empty.');
    }

    if (1 !== preg_match('/^[a-zA-Z0-9._-]+$/', $normalized_channel)) {
        throw new \InvalidArgumentException(
            sprintf('Invalid log channel "%s". Use only letters, numbers, ".", "_" and "-".', $channel)
        );
    }

    return $normalized_channel;
}

function log_interpolate_message(string $message, array $context): string
{
    if (harbor_is_blank($context)) {
        return $message;
    }

    $replace = [];
    foreach ($context as $key => $value) {
        if (! is_string($key) && ! is_int($key)) {
            continue;
        }

        $replace['{'.$key.'}'] = log_context_value_to_string($value);
    }

    return strtr($message, $replace);
}

function log_context_value_to_string(mixed $value): string
{
    if (is_string($value)) {
        return $value;
    }

    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }

    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }

    if (harbor_is_null($value)) {
        return 'null';
    }

    if (is_object($value) && method_exists($value, '__toString')) {
        return (string) $value;
    }

    return log_encode_context(log_normalize_context(['value' => $value]));
}

function log_normalize_context(array $context): array
{
    $normalized_context = [];

    foreach ($context as $key => $value) {
        $normalized_key = is_string($key) || is_int($key) ? $key : (string) $key;
        $normalized_context[$normalized_key] = log_normalize_context_value($value);
    }

    return $normalized_context;
}

function log_normalize_context_value(mixed $value): mixed
{
    if (is_scalar($value) || harbor_is_null($value)) {
        return $value;
    }

    if ($value instanceof \Throwable) {
        return [
            'class' => $value::class,
            'message' => $value->getMessage(),
            'code' => $value->getCode(),
            'file' => $value->getFile(),
            'line' => $value->getLine(),
        ];
    }

    if ($value instanceof \DateTimeInterface) {
        return $value->format(DATE_ATOM);
    }

    if (is_array($value)) {
        $normalized_array = [];

        foreach ($value as $array_key => $array_value) {
            $normalized_key = is_string($array_key) || is_int($array_key) ? $array_key : (string) $array_key;
            $normalized_array[$normalized_key] = log_normalize_context_value($array_value);
        }

        return $normalized_array;
    }

    if (is_object($value)) {
        return [
            'class' => $value::class,
            'properties' => log_normalize_context(get_object_vars($value)),
        ];
    }

    if (is_resource($value)) {
        return sprintf('[resource:%s]', get_resource_type($value));
    }

    return '[unserializable]';
}

function log_encode_context(array $context): string
{
    if (harbor_is_blank($context)) {
        return '';
    }

    try {
        return json_encode($context, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (\JsonException) {
        return '{"_log_context_error":"Failed to encode log context."}';
    }
}
