<?php

declare(strict_types=1);

namespace Harbor\Command;

require_once __DIR__.'/../Support/value.php';

use Harbor\Exceptions\EmptyStringException;

use function Harbor\Support\harbor_is_blank;

/** Public */
/**
 * @param array<int, string> $argv
 *
 * @return array{
 *     name: string,
 *     argc: int,
 *     argv: array<int, string>,
 *     options: array<int, array{
 *         flag: string,
 *         description: string,
 *         default_value: null|bool|float|int|string
 *     }>
 * }
 */
function command_flags_init(string $name, int $argc, array $argv): array
{
    $normalized_arguments = [];

    foreach (array_values($argv) as $argument) {
        if (! is_string($argument)) {
            continue;
        }

        $normalized_arguments[] = trim($argument);
    }

    return [
        'name' => $name,
        'argc' => $argc,
        'argv' => $normalized_arguments,
        'options' => [],
    ];
}

/**
 * @throws EmptyStringException
 * @throws CommandValueRequiredException
 */
function command_flag(
    array &$command,
    string $flag,
    string $description,
    bool|\Closure $required = false,
    bool|float|int|string|null $default_value = null
): bool|float|int|string|null {
    $normalized_flag = command_flags_internal_normalize_flag($flag);
    if (harbor_is_blank($normalized_flag)) {
        throw new EmptyStringException('Flag cannot be empty.');
    }

    command_flags_internal_register_option(
        $command,
        $normalized_flag,
        $description,
        $default_value
    );

    $flag_payload = command_flags_internal_find_flag_payload(
        $command['argv'] ?? [],
        $normalized_flag
    );

    if (! $flag_payload['present']) {
        return $default_value;
    }

    if (! $flag_payload['has_value']) {
        if (true === $required || is_callable($required)) {
            throw new CommandValueRequiredException(sprintf('%s: value is required.', $normalized_flag));
        }

        if (is_bool($default_value)) {
            return true;
        }

        return null === $default_value ? true : $default_value;
    }

    $value = $flag_payload['value'];
    command_flags_internal_assert_required_value($normalized_flag, $value, $required);

    return $value;
}

/**
 * @param array{
 *     name?: string,
 *     options?: array<int, array{flag?: string, description?: string, default_value?: null|bool|float|int|string}>
 * } $command
 */
function command_flags_print_usage(array $command): void
{
    $command_name = is_string($command['name'] ?? null) ? $command['name'] : 'command';
    $registered_options = is_array($command['options'] ?? null) ? $command['options'] : [];

    fwrite(STDOUT, sprintf('Usage: %s [flags]%s', $command_name, PHP_EOL));

    if (empty($registered_options)) {
        return;
    }

    fwrite(STDOUT, 'Flags:'.PHP_EOL);

    foreach ($registered_options as $option) {
        $option_flag = (string) ($option['flag'] ?? '');
        $option_description = (string) ($option['description'] ?? '');
        $default_label = command_flags_internal_default_label($option['default_value'] ?? null);
        $default_segment = harbor_is_blank($default_label) ? '' : sprintf(' (default: %s)', $default_label);

        fwrite(STDOUT, sprintf('    %s: %s%s%s', $option_flag, $option_description, $default_segment, PHP_EOL));
    }
}

/** Private */
/**
 * @param array<int, string> $argv
 *
 * @return array{present: bool, has_value: bool, value: null|bool|float|int|string}
 *
 * @throws EmptyStringException
 */
function command_flags_internal_find_flag_payload(array $argv, string $flag): array
{
    $arguments = array_values($argv);
    $arguments_count = count($arguments);

    for ($index = 0; $index < $arguments_count; ++$index) {
        $argument = $arguments[$index];
        if (! is_string($argument)) {
            continue;
        }

        $normalized_argument = trim($argument);
        if (! command_flags_internal_is_same_flag($normalized_argument, $flag)) {
            continue;
        }

        $inline_value = command_flags_internal_inline_value($normalized_argument, $flag);
        if (is_string($inline_value)) {
            $normalized_value = command_flags_internal_normalize_flag_value($inline_value);

            return [
                'present' => true,
                'has_value' => true,
                'value' => $normalized_value,
            ];
        }

        if (command_flags_internal_next_token_represents_value($arguments, $index)) {
            $next_value = (string) $arguments[$index + 1];
            $normalized_value = command_flags_internal_normalize_flag_value($next_value);

            return [
                'present' => true,
                'has_value' => true,
                'value' => $normalized_value,
            ];
        }

        return [
            'present' => true,
            'has_value' => false,
            'value' => null,
        ];
    }

    return [
        'present' => false,
        'has_value' => false,
        'value' => null,
    ];
}

function command_flags_internal_is_same_flag(string $token, string $flag): bool
{
    if ($token === $flag) {
        return true;
    }

    return str_starts_with($token, $flag.'=');
}

/**
 * @throws EmptyStringException
 */
function command_flags_internal_normalize_flag_value(string $value): string
{
    $normalized_value = trim($value);
    if (harbor_is_blank($normalized_value)) {
        throw new EmptyStringException('Flag cannot be empty.');
    }

    return $normalized_value;
}

function command_flags_internal_inline_value(string $token, string $flag): ?string
{
    if (! str_starts_with($token, $flag.'=')) {
        return null;
    }

    $value = substr($token, strlen($flag) + 1);
    if (false === $value) {
        return null;
    }

    return $value;
}

/**
 * @param array<int, string> $argv
 */
function command_flags_internal_next_token_represents_value(array $argv, int $index): bool
{
    $next_token = $argv[$index + 1] ?? null;
    if (! is_string($next_token)) {
        return false;
    }

    $normalized_next_token = trim($next_token);
    if ('--' === $normalized_next_token) {
        return false;
    }

    return ! command_flags_internal_is_option_token($normalized_next_token);
}

function command_flags_internal_is_option_token(string $token): bool
{
    if ('-' === $token || harbor_is_blank($token)) {
        return false;
    }

    if (1 === preg_match('/^-[0-9]+(?:\.[0-9]+)?$/', $token)) {
        return false;
    }

    return str_starts_with($token, '-');
}

function command_flags_internal_normalize_flag(string $flag): string
{
    $normalized_flag = trim($flag);
    if (harbor_is_blank($normalized_flag)) {
        return '';
    }

    if (1 !== preg_match('/^--?[a-zA-Z0-9][a-zA-Z0-9._-]*$/', $normalized_flag)) {
        return '';
    }

    return $normalized_flag;
}

function command_flags_internal_assert_required_value(string $flag, mixed $value, bool|\Closure $required): void
{
    if (is_bool($required) && $required && harbor_is_blank($value)) {
        throw new CommandValueRequiredException(sprintf('%s: value is required.', $flag));
    }

    if (is_callable($required) && ! $required($value)) {
        throw new CommandValueRequiredException(sprintf('%s: value is required.', $flag));
    }
}

function command_flags_internal_register_option(
    array &$command,
    string $flag,
    string $description,
    bool|float|int|string|null $default_value
): void {
    if (! is_array($command['options'] ?? null)) {
        $command['options'] = [];
    }

    foreach ($command['options'] as $registered_option) {
        if (! is_array($registered_option)) {
            continue;
        }

        if (($registered_option['flag'] ?? null) === $flag) {
            return;
        }
    }

    $command['options'][] = [
        'flag' => $flag,
        'description' => $description,
        'default_value' => $default_value,
    ];
}

function command_flags_internal_default_label(bool|float|int|string|null $default_value): string
{
    if (null === $default_value) {
        return '';
    }

    if (is_bool($default_value)) {
        return $default_value ? 'true' : 'false';
    }

    if (is_scalar($default_value)) {
        return (string) $default_value;
    }

    return '';
}
