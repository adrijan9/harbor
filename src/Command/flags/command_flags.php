<?php

declare(strict_types=1);

namespace Harbor\Command\Flags;

require_once __DIR__.'/../../Support/value.php';

require_once __DIR__.'/../../Support/string.php';

require_once __DIR__.'/command_flag.php';

require_once __DIR__.'/command_flag_casts.php';

require_once __DIR__.'/command_flag_validation.php';

require_once __DIR__.'/command_flag_value.php';

use Harbor\Command\CommandInvalidFlagException;
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
function command_flags_init(string $name, int $argc, array $argv): array
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
        if (is_null($default_value)) {
            return $default_value;
        }

        command_flags_internal_assert_validated_value(
            $normalized_flag,
            $default_value,
            $validator
        );

        return $default_value;
    }

    $value = $flag_payload['value'];
    command_flags_internal_assert_validated_value($normalized_flag, $value, $validator);

    return $value;
}

/**
 * @throws EmptyStringException
 */
function command_flag_no_value(
    array &$command,
    string $flag,
    string $description,
): bool {
    $normalized_flag = command_flags_internal_normalize_flag($flag);
    if (harbor_is_blank($normalized_flag)) {
        throw new EmptyStringException('Flag cannot be empty.');
    }

    command_flags_internal_register_option(
        $command,
        $normalized_flag,
        $description,
        null
    );

    $flag_payload = command_flags_internal_find_flag_payload(
        $command['argv'] ?? [],
        $normalized_flag
    );

    if (! $flag_payload['present']) {
        return false;
    }

    return ! $flag_payload['has_value'];
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
    $raw_value = command_flag(
        $command,
        $flag,
        $description,
        $validator
    );

    return command_flags_internal_value_to_int($raw_value, $default_value);
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
    $raw_value = command_flag(
        $command,
        $flag,
        $description,
        $validator
    );

    return command_flags_internal_value_to_float($raw_value, $default_value);
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
    $raw_value = command_flag(
        $command,
        $flag,
        $description,
        $validator
    );

    return command_flags_internal_value_to_bool($raw_value, $default_value);
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
    command_flags_internal_register_option(
        $command,
        $flag,
        $description,
        $default_value
    );

    $raw_value = command_flag(
        $command,
        $flag,
        $description
    );

    $typed_value = command_flags_internal_parse_csv_array_value($raw_value, $default_value);
    command_flags_internal_assert_validated_value($flag, $typed_value, $validator);

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
