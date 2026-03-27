<?php

declare(strict_types=1);

use Harbor\Command\CommandValueRequiredException;
use Harbor\Exceptions\EmptyStringException;

function command_init(string $name, int $argc, array $argv): array
{
    return [
        'name' => $name,
        'argc' => $argc,
        'argv' => $argv,
        'options' => [],
    ];
}

/**
 * @throws EmptyStringException
 * @throws CommandValueRequiredException
 */
function command_flag(array &$commands, string $flag, string $description, bool|Closure $required = false, bool|float|int|string|null $defaultValue = null): bool|float|int|string|null
{
    $flagOptionExists = command_flags_internal_check_argv_flag_present($commands, $flag);

    $value = command_flags_internal_get_value($commands, $flag, $defaultValue);
    if (command_flags_internal_get_options_command_by_flag($commands, $flag)) {
        if (! $flagOptionExists) {
            return null;
        }

        return $value ?? true;
    }

    $commands['options'][] = [
        'flag' => $flag,
        'description' => $description,
        'defaultValue' => $defaultValue,
    ];

    if (! $flagOptionExists) {
        return null;
    }

    if (is_bool($required) && $required && empty($value)) {
        throw new CommandValueRequiredException("{$flag}: value is required required.");
    }

    if (is_callable($required) && ! $required($value)) {
        throw new CommandValueRequiredException("{$flag}: value is required required.");
    }

    return $value ?? true;
}

function command_print_usage($command): void
{
    fwrite(STDOUT, "Usage: {$command['name']} --option=value\n");
    if (count($command['options']) <= 0) {
        return;
    }
    fwrite(STDOUT, "Options:\n");
    foreach ($command['options'] as $option) {
        $defaultValue = $option['defaultValue']
            ? '(Default: '.$option['defaultValue'].') '
            : '';
        fwrite(STDOUT, "    -{$option['flag']}: {$defaultValue}{$option['description']}\n");
    }
}

function command_flags_internal_get_options_command_by_flag(array $commands, string $flag): ?array
{
    return array_find($commands['options'], fn ($command) => $command['flag'] === $flag);
}

function command_flags_internal_check_argv_flag_present(array $commands, string $flag): bool
{
    return (bool) array_find($commands['argv'], fn (string $command) => str_contains($command, $flag));
}

function command_flags_internal_get_argv_command_by_flag(array $commands, string $flag): ?string
{
    return ($commands['argv']
        |> (static fn ($argvCommands) => array_filter($argvCommands, static fn (string $command) => str_contains($command, $flag)))
        |> array_values(...))[0] ?? null;
}

/**
 * @throws EmptyStringException
 * @throws RuntimeException
 */
function command_flags_internal_get_value(array $commands, string $flag, bool|float|int|string|null $defaultValue = null): bool|float|int|string|null
{
    $value = $defaultValue;
    $command = command_flags_internal_check_argv_flag_present($commands, $flag);

    if (! $command) {
        return $value;
    }

    $commandValue = command_flags_internal_get_argv_command_by_flag($commands, $flag)
        |> command_flags_internal_normalize_flag(...);

    if (empty($commandValue)) {
        throw new EmptyStringException('Flag cannot be empty.');
    }

    if (str_contains($commandValue, '=')) {
        $flagData = explode('=', $commandValue);
        $value = $flagData[1];
    }

    return $value;
}

function command_flags_internal_normalize_flag(string $flag): string
{
    return trim($flag);
}
