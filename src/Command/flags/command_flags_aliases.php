<?php

declare(strict_types=1);

namespace Harbor\Command\Flags;

use Harbor\Validation\ValidationRule;

/**
 * Backward-compatible namespace aliases for command flag helpers.
 */
function command_init(string $name, int $argc, array $argv): array
{
    return \Harbor\Command\command_init($name, $argc, $argv);
}

/**
 * @throws \Harbor\Exceptions\EmptyStringException
 * @throws \Harbor\Command\CommandInvalidFlagException
 */
function command_flag(
    array &$command,
    string $flag,
    string $description,
    ?ValidationRule $validator = null,
    bool|float|int|string|null $default_value = null
): bool|float|int|string|null {
    return \Harbor\Command\command_flag(
        $command,
        $flag,
        $description,
        $validator,
        $default_value
    );
}

/**
 * @throws \Harbor\Exceptions\EmptyStringException
 * @throws \Harbor\Command\CommandInvalidFlagException
 */
function command_flag_string(
    array &$command,
    string $flag,
    string $description,
    ?ValidationRule $validator = null,
    ?string $default_value = null
): ?string {
    return \Harbor\Command\command_flag_string(
        $command,
        $flag,
        $description,
        $validator,
        $default_value
    );
}

/**
 * @throws \Harbor\Exceptions\EmptyStringException
 * @throws \Harbor\Command\CommandInvalidFlagException
 */
function command_flag_int(
    array &$command,
    string $flag,
    string $description,
    ?ValidationRule $validator = null,
    int $default_value = 0
): int {
    return \Harbor\Command\command_flag_int(
        $command,
        $flag,
        $description,
        $validator,
        $default_value
    );
}

/**
 * @throws \Harbor\Exceptions\EmptyStringException
 * @throws \Harbor\Command\CommandInvalidFlagException
 */
function command_flag_float(
    array &$command,
    string $flag,
    string $description,
    ?ValidationRule $validator = null,
    float $default_value = 0.0
): float {
    return \Harbor\Command\command_flag_float(
        $command,
        $flag,
        $description,
        $validator,
        $default_value
    );
}

/**
 * @throws \Harbor\Exceptions\EmptyStringException
 * @throws \Harbor\Command\CommandInvalidFlagException
 */
function command_flag_bool(
    array &$command,
    string $flag,
    string $description,
    ?ValidationRule $validator = null,
    bool $default_value = false
): bool {
    return \Harbor\Command\command_flag_bool(
        $command,
        $flag,
        $description,
        $validator,
        $default_value
    );
}

/**
 * @param array<int, bool|float|int|string> $default_value
 *
 * @return array<int, bool|float|int|string>
 *
 * @throws \Harbor\Exceptions\EmptyStringException
 * @throws \Harbor\Command\CommandInvalidFlagException
 */
function command_flag_array(
    array &$command,
    string $flag,
    string $description,
    ?ValidationRule $validator = null,
    array $default_value = []
): array {
    return \Harbor\Command\command_flag_array(
        $command,
        $flag,
        $description,
        $validator,
        $default_value
    );
}

/**
 * @param array{
 *     name?: string,
 *     options?: array<int, array{flag?: string, description?: string, default_value?: null|array<int, bool|float|int|string>|bool|float|int|string}>
 * } $command
 */
function command_flags_print_usage(array $command): void
{
    \Harbor\Command\command_flags_print_usage($command);
}
