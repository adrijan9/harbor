<?php

declare(strict_types=1);

namespace Harbor\Log;

require_once __DIR__.'/LogLevel.php';

require_once __DIR__.'/../Config/config.php';

require_once __DIR__.'/../Filesystem/filesystem.php';

require_once __DIR__.'/../Support/value.php';

use function Harbor\Config\config_get;
use function Harbor\Filesystem\fs_append;
use function Harbor\Filesystem\fs_delete;
use function Harbor\Filesystem\fs_dir_create;
use function Harbor\Filesystem\fs_dir_exists;
use function Harbor\Filesystem\fs_exists;
use function Harbor\Filesystem\fs_write;
use function Harbor\Support\harbor_is_blank;
use function Harbor\Support\harbor_is_null;

// Ensure log state always exists in true global scope, even when this file is
// required from inside a method (for example via Helper::load_many()).
if (! array_key_exists('log_file_path', $GLOBALS)) {
    $GLOBALS['log_file_path'] = null;
}

if (! array_key_exists('log_is_initialized', $GLOBALS) || ! is_bool($GLOBALS['log_is_initialized'])) {
    $GLOBALS['log_is_initialized'] = false;
}

if (
    ! array_key_exists('log_default_channel', $GLOBALS)
    || ! is_string($GLOBALS['log_default_channel'])
    || harbor_is_blank($GLOBALS['log_default_channel'])
) {
    $GLOBALS['log_default_channel'] = 'app';
}

/** Public */
function log_init(string $file_path, string $channel = 'app'): void
{
    global $log_file_path, $log_is_initialized, $log_default_channel;

    $log_file_path = log_prepare_file_path($file_path);
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

    return true === $log_is_initialized;
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
    $normalized_level = log_validate_level($level);
    $timestamp = date('Y-m-d H:i:s');
    $write_targets = log_resolve_write_targets($channel);

    foreach ($write_targets as $write_target) {
        $log_content = log_create_content_with_timestamp(
            $normalized_level,
            $message,
            $context,
            $write_target['channel'],
            $timestamp
        );

        log_append_to_path($write_target['file_path'], $log_content.PHP_EOL);

        if ('daily' === $write_target['driver']) {
            log_prune_daily_channel_files($write_target['base_path'], $write_target['days']);
        }
    }
}

function log_create_content(LogLevel|string $level, string $message, array $context = [], ?string $channel = null): string
{
    global $log_default_channel;

    $normalized_level = log_validate_level($level);
    $channel_candidate = harbor_is_null($channel) ? $log_default_channel : $channel;
    $normalized_channel = log_validate_channel($channel_candidate);
    return log_create_content_with_timestamp(
        $normalized_level,
        $message,
        $context,
        $normalized_channel,
        date('Y-m-d H:i:s')
    );
}

function log_write_content(string $log_content): void
{
    $write_targets = log_resolve_content_write_targets();
    $normalized_content = str_ends_with($log_content, PHP_EOL) ? $log_content : $log_content.PHP_EOL;

    foreach ($write_targets as $write_target) {
        log_append_to_path($write_target['file_path'], $normalized_content);

        if ('daily' === $write_target['driver']) {
            log_prune_daily_channel_files($write_target['base_path'], $write_target['days']);
        }
    }
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

/** Private */
function log_bail_if_not_initialized(): void
{
    global $log_is_initialized, $log_file_path;

    if (! $log_is_initialized || ! is_string($log_file_path) || harbor_is_blank($log_file_path)) {
        throw new \RuntimeException('Log file not initialized. Call log_init() first.');
    }
}

/**
 * @return array<int, array{driver: string, file_path: string, base_path: string, days: int, channel: string}>
 */
function log_resolve_write_targets(?string $requested_channel): array
{
    $configured_channels = log_configured_channels();

    if (harbor_is_blank($configured_channels)) {
        return [log_resolve_legacy_write_target($requested_channel)];
    }

    $resolved_channel = log_resolve_configured_channel_name($requested_channel, $configured_channels);

    return log_resolve_channel_targets($resolved_channel, $configured_channels);
}

/**
 * @return array<int, array{driver: string, file_path: string, base_path: string, days: int, channel: string}>
 */
function log_resolve_content_write_targets(): array
{
    $configured_channels = log_configured_channels();

    if (harbor_is_blank($configured_channels)) {
        return [log_resolve_legacy_write_target(null)];
    }

    $resolved_channel = log_resolve_configured_channel_name(null, $configured_channels);

    return log_resolve_channel_targets($resolved_channel, $configured_channels);
}

/**
 * @param array<string, mixed> $configured_channels
 * @return array<int, array{driver: string, file_path: string, base_path: string, days: int, channel: string}>
 */
function log_resolve_channel_targets(string $channel, array $configured_channels, array $visited_channels = []): array
{
    $normalized_channel = log_validate_channel($channel);

    if (in_array($normalized_channel, $visited_channels, true)) {
        throw new \RuntimeException(sprintf('Circular log stack detected for channel "%s".', $normalized_channel));
    }

    $channel_definition = $configured_channels[$normalized_channel] ?? null;
    if (! is_array($channel_definition)) {
        throw new \InvalidArgumentException(sprintf('Log channel "%s" is not defined in logging.channels.', $normalized_channel));
    }

    $driver = log_validate_driver($channel_definition['driver'] ?? 'single', $normalized_channel);

    if ('stack' === $driver) {
        $stack_channels = $channel_definition['channels'] ?? null;
        if (! is_array($stack_channels) || harbor_is_blank($stack_channels)) {
            throw new \InvalidArgumentException(
                sprintf('Log stack channel "%s" requires a non-empty channels array.', $normalized_channel)
            );
        }

        $next_visited_channels = array_merge($visited_channels, [$normalized_channel]);
        $targets = [];

        foreach ($stack_channels as $stack_channel) {
            if (! is_string($stack_channel) || harbor_is_blank(trim($stack_channel))) {
                continue;
            }

            $targets = array_merge(
                $targets,
                log_resolve_channel_targets($stack_channel, $configured_channels, $next_visited_channels)
            );
        }

        if (harbor_is_blank($targets)) {
            throw new \InvalidArgumentException(
                sprintf('Log stack channel "%s" resolved zero writable channels.', $normalized_channel)
            );
        }

        return log_unique_targets($targets);
    }

    if ('daily' === $driver) {
        $base_path = log_extract_config_path($channel_definition, $normalized_channel);

        return [[
            'driver' => 'daily',
            'file_path' => log_daily_file_path($base_path),
            'base_path' => $base_path,
            'days' => log_extract_daily_days($channel_definition),
            'channel' => log_extract_output_channel_name($channel_definition, $normalized_channel),
        ]];
    }

    $single_path = log_extract_config_path($channel_definition, $normalized_channel);

    return [[
        'driver' => 'single',
        'file_path' => $single_path,
        'base_path' => $single_path,
        'days' => 0,
        'channel' => log_extract_output_channel_name($channel_definition, $normalized_channel),
    ]];
}

/**
 * @return array{driver: string, file_path: string, base_path: string, days: int, channel: string}
 */
function log_resolve_legacy_write_target(?string $requested_channel): array
{
    global $log_default_channel;

    log_bail_if_not_initialized();

    $resolved_file_path = log_file_path();
    if (! is_string($resolved_file_path) || harbor_is_blank(trim($resolved_file_path))) {
        throw new \RuntimeException('Log file not initialized. Call log_init() first.');
    }

    $resolved_channel = harbor_is_null($requested_channel) ? $log_default_channel : $requested_channel;

    return [
        'driver' => 'single',
        'file_path' => $resolved_file_path,
        'base_path' => $resolved_file_path,
        'days' => 0,
        'channel' => log_validate_channel($resolved_channel),
    ];
}

/**
 * @return array<string, mixed>
 */
function log_configured_channels(): array
{
    $channels = config_get('logging.channels', []);

    return is_array($channels) ? $channels : [];
}

/**
 * @param array<string, mixed> $configured_channels
 */
function log_resolve_configured_channel_name(?string $requested_channel, array $configured_channels): string
{
    if (is_string($requested_channel) && ! harbor_is_blank(trim($requested_channel))) {
        return log_validate_channel($requested_channel);
    }

    $configured_default_channel = config_get('logging.default');
    if (is_string($configured_default_channel) && ! harbor_is_blank(trim($configured_default_channel))) {
        return log_validate_channel($configured_default_channel);
    }

    if (array_key_exists('single', $configured_channels)) {
        return 'single';
    }

    foreach ($configured_channels as $channel_name => $channel_definition) {
        if (is_string($channel_name) && is_array($channel_definition)) {
            return log_validate_channel($channel_name);
        }
    }

    throw new \RuntimeException('No log channels are configured. Define logging.channels in your config.');
}

/**
 * @param array<string, mixed> $channel_definition
 */
function log_extract_config_path(array $channel_definition, string $channel_name): string
{
    $path = $channel_definition['path'] ?? null;
    if (! is_string($path) || harbor_is_blank(trim($path))) {
        throw new \InvalidArgumentException(
            sprintf('Log channel "%s" requires a non-empty path value.', $channel_name)
        );
    }

    return trim($path);
}

/**
 * @param array<string, mixed> $channel_definition
 */
function log_extract_output_channel_name(array $channel_definition, string $channel_name): string
{
    $configured_channel = $channel_definition['channel'] ?? $channel_name;
    if (! is_string($configured_channel)) {
        throw new \InvalidArgumentException(
            sprintf('Log channel "%s" has an invalid channel label value.', $channel_name)
        );
    }

    return log_validate_channel($configured_channel);
}

/**
 * @param array<string, mixed> $channel_definition
 */
function log_extract_daily_days(array $channel_definition): int
{
    $days = $channel_definition['days'] ?? 14;

    if (is_int($days)) {
        return max(1, $days);
    }

    if (is_numeric($days)) {
        return max(1, (int) $days);
    }

    return 14;
}

function log_validate_driver(mixed $driver, string $channel_name): string
{
    if (! is_string($driver)) {
        throw new \InvalidArgumentException(
            sprintf('Log channel "%s" has an invalid driver value.', $channel_name)
        );
    }

    $normalized_driver = strtolower(trim($driver));
    if (! in_array($normalized_driver, ['single', 'daily', 'stack'], true)) {
        throw new \InvalidArgumentException(
            sprintf(
                'Invalid log driver "%s" for channel "%s". Supported drivers: single, daily, stack.',
                $driver,
                $channel_name
            )
        );
    }

    return $normalized_driver;
}

/**
 * @param array<int, array{driver: string, file_path: string, base_path: string, days: int, channel: string}> $targets
 * @return array<int, array{driver: string, file_path: string, base_path: string, days: int, channel: string}>
 */
function log_unique_targets(array $targets): array
{
    $unique_targets = [];
    $seen_keys = [];

    foreach ($targets as $target) {
        $target_key = $target['driver'].'|'.$target['file_path'].'|'.$target['channel'].'|'.$target['days'];
        if (array_key_exists($target_key, $seen_keys)) {
            continue;
        }

        $seen_keys[$target_key] = true;
        $unique_targets[] = $target;
    }

    return $unique_targets;
}

function log_daily_file_path(string $base_path): string
{
    $normalized_path = trim($base_path);
    if (harbor_is_blank($normalized_path)) {
        throw new \InvalidArgumentException('Log file path cannot be empty.');
    }

    $directory_path = dirname($normalized_path);
    $file_name = basename($normalized_path);
    $extension = pathinfo($file_name, PATHINFO_EXTENSION);
    $name_without_extension = pathinfo($file_name, PATHINFO_FILENAME);

    if (! is_string($name_without_extension) || harbor_is_blank($name_without_extension)) {
        $name_without_extension = $file_name;
    }

    $date_suffix = date('Y-m-d');

    if (is_string($extension) && ! harbor_is_blank($extension)) {
        return sprintf('%s/%s-%s.%s', $directory_path, $name_without_extension, $date_suffix, $extension);
    }

    return sprintf('%s/%s-%s.log', $directory_path, $name_without_extension, $date_suffix);
}

function log_prune_daily_channel_files(string $base_path, int $days): void
{
    $retained_days = max(1, $days);
    $daily_files = log_match_daily_files($base_path);

    if (harbor_is_blank($daily_files) || count($daily_files) <= $retained_days) {
        return;
    }

    rsort($daily_files, SORT_STRING);

    $files_to_delete = array_slice($daily_files, $retained_days);

    foreach ($files_to_delete as $file_to_delete) {
        if (is_string($file_to_delete) && is_file($file_to_delete)) {
            fs_delete($file_to_delete);
        }
    }
}

/**
 * @return array<int, string>
 */
function log_match_daily_files(string $base_path): array
{
    $normalized_path = trim($base_path);
    if (harbor_is_blank($normalized_path)) {
        return [];
    }

    $directory_path = dirname($normalized_path);
    $file_name = basename($normalized_path);
    $extension = pathinfo($file_name, PATHINFO_EXTENSION);
    $name_without_extension = pathinfo($file_name, PATHINFO_FILENAME);

    if (! is_string($name_without_extension) || harbor_is_blank($name_without_extension)) {
        $name_without_extension = $file_name;
    }

    $pattern = is_string($extension) && ! harbor_is_blank($extension)
        ? sprintf('%s/%s-????-??-??.%s', $directory_path, $name_without_extension, $extension)
        : sprintf('%s/%s-????-??-??.log', $directory_path, $name_without_extension);
    $matched_files = glob($pattern);

    if (! is_array($matched_files)) {
        return [];
    }

    return array_values(array_filter(
        $matched_files,
        static fn (mixed $matched_file): bool => is_string($matched_file)
    ));
}

function log_prepare_file_path(string $file_path): string
{
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

    return $normalized_file_path;
}

function log_append_to_path(string $file_path, string $content): void
{
    $resolved_file_path = log_prepare_file_path($file_path);

    fs_append($resolved_file_path, $content);
}

function log_create_content_with_timestamp(
    string $normalized_level,
    string $message,
    array $context,
    string $channel,
    string $timestamp,
): string {
    $interpolated_message = log_interpolate_message($message, $context);
    $normalized_context = log_normalize_context($context);
    $context_json = log_encode_context($normalized_context);

    $log_content = sprintf('[%s] [%s] [%s] %s', $timestamp, strtoupper($normalized_level), $channel, $interpolated_message);

    if ('' !== $context_json) {
        $log_content .= ' | '.$context_json;
    }

    return $log_content;
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
