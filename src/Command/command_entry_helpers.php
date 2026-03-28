<?php

declare(strict_types=1);

namespace Harbor\Command;

require_once __DIR__.'/../Support/value.php';

use function Harbor\Support\harbor_is_blank;
use function Harbor\Support\harbor_is_null;

/** Public */
function command_info(string $message): void
{
    fwrite(STDOUT, $message.PHP_EOL);
}

function command_error(string $message): void
{
    fwrite(STDERR, $message.PHP_EOL);
}

function command_debug(string $message): void
{
    if (! command_debug_enabled()) {
        return;
    }

    fwrite(STDERR, sprintf('[debug] %s%s', $message, PHP_EOL));
}

function command_debug_enabled(): bool
{
    $debug_environment = getenv('HARBOR_COMMAND_DEBUG');

    if (is_string($debug_environment) && ! harbor_is_blank($debug_environment)) {
        return command_entry_internal_value_to_bool($debug_environment, false);
    }

    return command_entry_internal_debug_enabled_from_arguments();
}

/**
 * @return array<int, string>
 */
function command_raw_arguments(): array
{
    $argv = $_SERVER['argv'] ?? $GLOBALS['argv'] ?? [];
    if (! is_array($argv) || harbor_is_blank($argv)) {
        return [];
    }

    $raw_arguments = [];

    foreach (array_values($argv) as $index => $value) {
        if (0 === $index || ! is_string($value)) {
            continue;
        }

        $raw_arguments[] = $value;
    }

    return $raw_arguments;
}

/**
 * Positional command arguments (options are excluded).
 *
 * @return array<int, string>
 */
function command_arguments(): array
{
    $parsed_payload = command_entry_internal_parsed_payload();

    return $parsed_payload['arguments'];
}

function command_arg(int $index, mixed $default = null): mixed
{
    if ($index < 0) {
        return $default;
    }

    $arguments = command_arguments();

    return $arguments[$index] ?? $default;
}

function command_arg_string(int $index, ?string $default = null): ?string
{
    $value = command_arg($index, $default);
    $normalized_value = command_entry_internal_value_to_string($value);

    return is_string($normalized_value) ? $normalized_value : $default;
}

function command_arg_int(int $index, int $default = 0): int
{
    return command_entry_internal_value_to_int(command_arg($index, $default), $default);
}

function command_arg_float(int $index, float $default = 0.0): float
{
    return command_entry_internal_value_to_float(command_arg($index, $default), $default);
}

function command_arg_bool(int $index, bool $default = false): bool
{
    return command_entry_internal_value_to_bool(command_arg($index, $default), $default);
}

/** Private */
/**
 * @return array{arguments: array<int, string>, options: array<string, bool|string>}
 */
function command_entry_internal_parsed_payload(): array
{
    static $parsed_payload = null;

    if (is_array($parsed_payload)) {
        return $parsed_payload;
    }

    $parsed_payload = command_entry_internal_parse_raw_arguments(command_raw_arguments());

    return $parsed_payload;
}

/**
 * @param array<int, string> $raw_arguments
 *
 * @return array{arguments: array<int, string>, options: array<string, bool|string>}
 */
function command_entry_internal_parse_raw_arguments(array $raw_arguments): array
{
    $arguments = [];
    $options = [];
    $should_treat_as_positional = false;
    $raw_arguments_count = count($raw_arguments);

    for ($index = 0; $index < $raw_arguments_count; ++$index) {
        $token = $raw_arguments[$index];
        if (! is_string($token)) {
            continue;
        }

        if ($should_treat_as_positional) {
            $arguments[] = $token;

            continue;
        }

        if ('--' === $token) {
            $should_treat_as_positional = true;

            continue;
        }

        if (str_starts_with($token, '--') && strlen($token) > 2) {
            $without_dashes = substr($token, 2);
            $option_segments = explode('=', $without_dashes, 2);
            $option_name = command_entry_internal_normalize_option_name($option_segments[0] ?? '');

            if (harbor_is_blank($option_name)) {
                $arguments[] = $token;

                continue;
            }

            if (2 === count($option_segments)) {
                $options[$option_name] = $option_segments[1];

                continue;
            }

            if (command_entry_internal_next_token_represents_value($raw_arguments, $index)) {
                $options[$option_name] = (string) $raw_arguments[$index + 1];
                ++$index;
            } else {
                $options[$option_name] = true;
            }

            continue;
        }

        if (1 === preg_match('/^-[a-zA-Z]$/', $token)) {
            $option_name = command_entry_internal_normalize_option_name(substr($token, 1));

            if (harbor_is_blank($option_name)) {
                $arguments[] = $token;

                continue;
            }

            if (command_entry_internal_next_token_represents_value($raw_arguments, $index)) {
                $options[$option_name] = (string) $raw_arguments[$index + 1];
                ++$index;
            } else {
                $options[$option_name] = true;
            }

            continue;
        }

        if (1 === preg_match('/^-[a-zA-Z]{2,}$/', $token)) {
            $option_flags = str_split(substr($token, 1));

            foreach ($option_flags as $option_flag) {
                $option_name = command_entry_internal_normalize_option_name($option_flag);

                if (harbor_is_blank($option_name)) {
                    continue;
                }

                $options[$option_name] = true;
            }

            continue;
        }

        $arguments[] = $token;
    }

    return [
        'arguments' => $arguments,
        'options' => $options,
    ];
}

function command_entry_internal_debug_enabled_from_arguments(): bool
{
    $parsed_payload = command_entry_internal_parsed_payload();
    $options = $parsed_payload['options'];
    $is_verbose_mode = array_key_exists('v', $options) || array_key_exists('verbose', $options);

    if (! array_key_exists('debug', $options)) {
        return $is_verbose_mode;
    }

    return command_entry_internal_value_to_bool($options['debug'], $is_verbose_mode);
}

/**
 * @param array<int, string> $raw_arguments
 */
function command_entry_internal_next_token_represents_value(array $raw_arguments, int $index): bool
{
    $next_token = $raw_arguments[$index + 1] ?? null;

    if (! is_string($next_token) || '--' === $next_token) {
        return false;
    }

    return ! command_entry_internal_is_option_token($next_token);
}

function command_entry_internal_is_option_token(string $token): bool
{
    if ('-' === $token || harbor_is_blank($token)) {
        return false;
    }

    if (1 === preg_match('/^-[0-9]+(?:\.[0-9]+)?$/', $token)) {
        return false;
    }

    return str_starts_with($token, '-');
}

function command_entry_internal_normalize_option_name(string $name): string
{
    $normalized_name = strtolower(trim(ltrim($name, '-')));
    if (harbor_is_blank($normalized_name)) {
        return '';
    }

    if (1 !== preg_match('/^[a-z0-9][a-z0-9._-]*$/', $normalized_name)) {
        return '';
    }

    return $normalized_name;
}

function command_entry_internal_value_to_string(mixed $value): ?string
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

    return null;
}

function command_entry_internal_value_to_int(mixed $value, int $default): int
{
    if (is_int($value)) {
        return $value;
    }

    if (is_bool($value)) {
        return $value ? 1 : 0;
    }

    if (is_float($value)) {
        return (int) $value;
    }

    if (is_string($value) && is_numeric(trim($value))) {
        return (int) $value;
    }

    return $default;
}

function command_entry_internal_value_to_float(mixed $value, float $default): float
{
    if (is_float($value) || is_int($value)) {
        return (float) $value;
    }

    if (is_bool($value)) {
        return $value ? 1.0 : 0.0;
    }

    if (is_string($value) && is_numeric(trim($value))) {
        return (float) $value;
    }

    return $default;
}

function command_entry_internal_value_to_bool(mixed $value, bool $default): bool
{
    if (is_bool($value)) {
        return $value;
    }

    if (is_int($value) || is_float($value)) {
        return 0.0 !== (float) $value;
    }

    if (harbor_is_null($value)) {
        return $default;
    }

    if (is_string($value)) {
        $normalized_value = strtolower(trim($value));

        if (in_array($normalized_value, ['1', 'true', 'yes', 'on', 'y'], true)) {
            return true;
        }

        if (in_array($normalized_value, ['0', 'false', 'no', 'off', 'n'], true)) {
            return false;
        }

        return $default;
    }

    return $default;
}
