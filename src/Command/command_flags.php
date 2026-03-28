<?php

declare(strict_types=1);

namespace Harbor\Command;

require_once __DIR__.'/../Support/value.php';

use Harbor\Exceptions\EmptyStringException;
use Harbor\Validation\ValidationRule;

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
 *         default_value: null|array<int, bool|float|int|string>|bool|float|int|string
 *     }>
 * }
 */
function command_init(string $name, int $argc, array $argv): array
{
    $normalized_arguments = [];

    foreach ($argv as $argument) {
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
 * @throws CommandInvalidFlagException
 */
function command_flag(
    array &$command,
    string $flag,
    string $description,
    ?ValidationRule $validator = null,
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
        command_flags_internal_assert_validated_value($normalized_flag, null, $validator);

        return null;
    }

    if (! $flag_payload['has_value']) {
        if (command_flags_internal_validator_is_required($validator)) {
            command_flags_internal_assert_validated_value($normalized_flag, null, $validator);
        }

        if (is_bool($default_value)) {
            $resolved_value = true;
            command_flags_internal_assert_validated_value($normalized_flag, $resolved_value, $validator);

            return $resolved_value;
        }

        $resolved_value = null === $default_value ? true : $default_value;
        command_flags_internal_assert_validated_value($normalized_flag, $resolved_value, $validator);

        return $resolved_value;
    }

    $value = $flag_payload['value'];
    command_flags_internal_assert_validated_value($normalized_flag, $value, $validator);

    return $value;
}

/**
 * @throws EmptyStringException
 * @throws CommandInvalidFlagException
 */
function command_flag_string(
    array &$command,
    string $flag,
    string $description,
    ?ValidationRule $validator = null,
    ?string $default_value = null
): ?string {
    $resolved_default_value = $default_value;
    $raw_value = command_flag(
        $command,
        $flag,
        $description,
        $validator,
        $resolved_default_value
    );

    return command_flags_internal_value_to_string($raw_value, $resolved_default_value);
}

/**
 * @throws EmptyStringException
 * @throws CommandInvalidFlagException
 */
function command_flag_int(
    array &$command,
    string $flag,
    string $description,
    ?ValidationRule $validator = null,
    int $default_value = 0
): int {
    $resolved_default_value = $default_value;
    $raw_value = command_flag(
        $command,
        $flag,
        $description,
        $validator,
        null
    );

    if (null === $raw_value || true === $raw_value) {
        return $resolved_default_value;
    }

    $is_valid = false;
    $typed_value = command_flags_internal_value_to_int($raw_value, $resolved_default_value, $is_valid);

    return $is_valid ? $typed_value : $resolved_default_value;
}

/**
 * @throws EmptyStringException
 * @throws CommandInvalidFlagException
 */
function command_flag_float(
    array &$command,
    string $flag,
    string $description,
    ?ValidationRule $validator = null,
    float $default_value = 0.0
): float {
    $resolved_default_value = $default_value;
    $raw_value = command_flag(
        $command,
        $flag,
        $description,
        $validator,
        null
    );

    if (null === $raw_value || true === $raw_value) {
        return $resolved_default_value;
    }

    $is_valid = false;
    $typed_value = command_flags_internal_value_to_float($raw_value, $resolved_default_value, $is_valid);

    return $is_valid ? $typed_value : $resolved_default_value;
}

/**
 * @throws EmptyStringException
 * @throws CommandInvalidFlagException
 */
function command_flag_bool(
    array &$command,
    string $flag,
    string $description,
    ?ValidationRule $validator = null,
    bool $default_value = false
): bool {
    $resolved_default_value = $default_value;
    $raw_value = command_flag(
        $command,
        $flag,
        $description,
        $validator,
        null
    );

    if (null === $raw_value) {
        return $resolved_default_value;
    }

    if (true === $raw_value) {
        return true;
    }

    $is_valid = false;
    $typed_value = command_flags_internal_value_to_bool($raw_value, $resolved_default_value, $is_valid);

    return $is_valid ? $typed_value : $resolved_default_value;
}

/**
 * Arrays should be passed as comma-separated values: --ids=1,2,3.
 *
 * @param array<int, bool|float|int|string> $default_value
 *
 * @return array<int, bool|float|int|string>
 *
 * @throws EmptyStringException
 * @throws CommandInvalidFlagException
 */
function command_flag_array(
    array &$command,
    string $flag,
    string $description,
    ?ValidationRule $validator = null,
    array $default_value = []
): array {
    $resolved_default_value = command_flags_internal_normalize_array_value(
        $default_value,
        $default_value
    );
    $normalized_flag = command_flags_internal_normalize_flag($flag);
    if (harbor_is_blank($normalized_flag)) {
        throw new EmptyStringException('Flag cannot be empty.');
    }

    command_flags_internal_register_option(
        $command,
        $normalized_flag,
        $description,
        $resolved_default_value
    );

    $raw_value = command_flag(
        $command,
        $normalized_flag,
        $description,
        null,
        null
    );

    if (null === $raw_value || true === $raw_value) {
        if (command_flags_internal_validator_is_required($validator)) {
            command_flags_internal_assert_validated_value($normalized_flag, null, $validator);
        }

        command_flags_internal_assert_validated_value(
            $normalized_flag,
            $resolved_default_value,
            $validator
        );

        return $resolved_default_value;
    }

    $typed_value = command_flags_internal_parse_csv_array_value($raw_value, $resolved_default_value);
    command_flags_internal_assert_validated_value($normalized_flag, $typed_value, $validator);

    return $typed_value;
}

/**
 * @param array{
 *     name?: string,
 *     options?: array<int, array{flag?: string, description?: string, default_value?: null|array<int, bool|float|int|string>|bool|float|int|string}>
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

    foreach ($arguments as $index => $argument) {
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

/**
 * @throws CommandInvalidFlagException
 */
function command_flags_internal_assert_validated_value(string $flag, mixed $value, ?ValidationRule $validator): void
{
    if (! $validator instanceof ValidationRule) {
        return;
    }

    $validation_result = $validator->validate_value($value);
    if (! $validation_result->has_errors()) {
        return;
    }

    $validation_messages = command_flags_internal_validation_error_messages(
        $validation_result->errors()
    );
    $validation_message = implode(PHP_EOL, $validation_messages);

    if (harbor_is_blank($validation_message)) {
        $validation_message = sprintf('%s: value is invalid.', $flag);
    }

    throw new CommandInvalidFlagException($validation_message);
}

function command_flags_internal_validator_is_required(?ValidationRule $validator): bool
{
    if (! $validator instanceof ValidationRule) {
        return false;
    }

    return array_any($validator->constraints(), static fn ($constraint) => 'required' === ($constraint['name'] ?? null));
}

/**
 * @param array<string, array<int, string>> $errors
 *
 * @return array<int, string>
 */
function command_flags_internal_validation_error_messages(array $errors): array
{
    $messages = [];

    foreach ($errors as $field_errors) {
        if (! is_array($field_errors)) {
            continue;
        }

        foreach ($field_errors as $field_error) {
            if (! is_string($field_error) || harbor_is_blank($field_error)) {
                continue;
            }

            $messages[] = trim($field_error);
        }
    }

    return $messages;
}

function command_flags_internal_register_option(
    array &$command,
    string $flag,
    string $description,
    array|bool|float|int|string|null $default_value
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

function command_flags_internal_default_label(array|bool|float|int|string|null $default_value): string
{
    if (is_array($default_value)) {
        if (empty($default_value)) {
            return '';
        }

        $segments = [];

        foreach ($default_value as $segment) {
            if (is_bool($segment)) {
                $segments[] = $segment ? 'true' : 'false';

                continue;
            }

            if (is_float($segment) || is_int($segment) || is_string($segment)) {
                $segments[] = (string) $segment;
            }
        }

        return implode(',', $segments);
    }

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

function command_flags_internal_value_to_string(mixed $value, ?string $default_value = null): ?string
{
    if (is_null($value)) {
        return $default_value;
    }

    return (string) $value;

}

function command_flags_internal_value_to_int(mixed $value, int $default_value, ?bool &$is_valid = null): int
{
    $is_valid = false;

    if (is_int($value)) {
        $is_valid = true;

        return $value;
    }

    if (is_bool($value)) {
        $is_valid = true;

        return $value ? 1 : 0;
    }

    if (is_float($value)) {
        $is_valid = true;

        return (int) $value;
    }

    if (is_string($value) && is_numeric(trim($value))) {
        $is_valid = true;

        return (int) $value;
    }

    return $default_value;
}

function command_flags_internal_value_to_float(mixed $value, float $default_value, ?bool &$is_valid = null): float
{
    $is_valid = false;

    if (is_float($value) || is_int($value)) {
        $is_valid = true;

        return (float) $value;
    }

    if (is_bool($value)) {
        $is_valid = true;

        return $value ? 1.0 : 0.0;
    }

    if (is_string($value) && is_numeric(trim($value))) {
        $is_valid = true;

        return (float) $value;
    }

    return $default_value;
}

function command_flags_internal_value_to_bool(mixed $value, bool $default_value, ?bool &$is_valid = null): bool
{
    $is_valid = false;

    if (is_bool($value)) {
        $is_valid = true;

        return $value;
    }

    if (is_int($value) || is_float($value)) {
        $is_valid = true;

        return 0.0 !== (float) $value;
    }

    if (is_string($value)) {
        $normalized_value = strtolower(trim($value));

        if (in_array($normalized_value, ['1', 'true', 'yes', 'on', 'y'], true)) {
            $is_valid = true;

            return true;
        }

        if (in_array($normalized_value, ['0', 'false', 'no', 'off', 'n'], true)) {
            $is_valid = true;

            return false;
        }
    }

    return $default_value;
}

/**
 * @param array<int, mixed>                 $value
 * @param array<int, bool|float|int|string> $default_value
 *
 * @return array<int, bool|float|int|string>
 */
function command_flags_internal_normalize_array_value(mixed $value, array $default_value = []): array
{
    if (! is_array($value)) {
        return $default_value;
    }

    $normalized_values = [];

    foreach (array_values($value) as $segment) {
        if (is_bool($segment) || is_float($segment) || is_int($segment) || is_string($segment)) {
            $normalized_values[] = $segment;
        }
    }

    return $normalized_values;
}

/**
 * @param array<int, bool|float|int|string> $default_value
 *
 * @return array<int, bool|float|int|string>
 */
function command_flags_internal_parse_csv_array_value(
    mixed $value,
    array $default_value = []
): array {
    if (is_array($value)) {
        return command_flags_internal_normalize_array_value($value, $default_value);
    }

    $string_value = command_flags_internal_value_to_string($value, null);
    if (! is_string($string_value) || harbor_is_blank($string_value)) {
        return $default_value;
    }

    $segments = explode(',', $string_value);
    $parsed_values = [];

    foreach ($segments as $segment) {
        $normalized_segment = trim($segment);
        if (harbor_is_blank($normalized_segment)) {
            continue;
        }

        $parsed_values[] = command_flags_internal_parse_csv_segment($normalized_segment);
    }

    if (empty($parsed_values)) {
        return $default_value;
    }

    return $parsed_values;
}

function command_flags_internal_parse_csv_segment(string $segment): bool|float|int|string
{
    if (1 === preg_match('/^-?[0-9]+$/', $segment)) {
        return (int) $segment;
    }

    if (is_numeric($segment)) {
        return (float) $segment;
    }

    $normalized_segment = strtolower(trim($segment));

    if (in_array($normalized_segment, ['true', 'yes', 'on', 'y'], true)) {
        return true;
    }

    if (in_array($normalized_segment, ['false', 'no', 'off', 'n'], true)) {
        return false;
    }

    return $segment;
}
