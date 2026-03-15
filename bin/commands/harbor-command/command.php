#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__.'/../../../src/Support/value.php';

require_once __DIR__.'/CommandException.php';

require_once __DIR__.'/BaseCommand.php';

require_once __DIR__.'/CommandCompiler.php';

require_once __DIR__.'/CreateCommand.php';

require_once __DIR__.'/RunCommand.php';

require_once __DIR__.'/CompileCommand.php';

use Harbor\CommandSystem\CommandCompiler;
use Harbor\CommandSystem\CommandException;
use Harbor\CommandSystem\CompileCommand;
use Harbor\CommandSystem\CreateCommand;
use Harbor\CommandSystem\RunCommand;

use function Harbor\Support\harbor_is_blank;

/**
 * @param array<int, string> $arguments
 */
function harbor_command_manager_run(array $arguments): int
{
    $input_arguments = array_values(array_slice($arguments, 1));

    $debug_mode = false;
    $first_argument = $input_arguments[0] ?? null;

    if (is_string($first_argument) && in_array($first_argument, ['--debug', '-v'], true)) {
        $debug_mode = true;
        array_shift($input_arguments);
        $first_argument = $input_arguments[0] ?? null;
    }

    if (null === $first_argument) {
        harbor_command_manager_print_usage();

        return 2;
    }

    if (is_string($first_argument) && in_array($first_argument, ['-h', '--help', 'help'], true)) {
        harbor_command_manager_print_usage();

        return 0;
    }

    if (! is_string($first_argument)) {
        fwrite(STDERR, 'Invalid subcommand.'.PHP_EOL);

        return 2;
    }

    $subcommand = $first_argument;
    array_shift($input_arguments);

    $compiler = new CommandCompiler();

    try {
        $working_directory = harbor_command_manager_resolve_working_directory();

        if ('create' === $subcommand) {
            [$key, $options] = harbor_command_manager_parse_create_arguments($input_arguments);

            return new CreateCommand($debug_mode, $compiler)->execute($key, $options, $working_directory);
        }

        if ('run' === $subcommand) {
            [$key, $forwarded_arguments] = harbor_command_manager_parse_run_arguments($input_arguments);

            return new RunCommand($debug_mode, $compiler)->execute($key, $forwarded_arguments, $working_directory);
        }

        if ('compile' === $subcommand) {
            $path_argument = harbor_command_manager_parse_compile_arguments($input_arguments);

            return new CompileCommand($debug_mode, $compiler)->execute($path_argument, $working_directory);
        }

        fwrite(STDERR, sprintf('Unknown subcommand: %s%s', $subcommand, PHP_EOL));
        harbor_command_manager_print_usage();

        return 2;
    } catch (CommandException $command_exception) {
        fwrite(STDERR, $command_exception->getMessage().PHP_EOL);

        return $command_exception->exit_code();
    } catch (Throwable $throwable) {
        fwrite(STDERR, $throwable->getMessage().PHP_EOL);

        return 1;
    }
}

function harbor_command_manager_resolve_working_directory(): string
{
    $working_directory = getcwd();
    if (false === $working_directory || harbor_is_blank($working_directory)) {
        throw new CommandException('Unable to resolve current working directory.', 1);
    }

    return $working_directory;
}

/**
 * @param array<int, string> $arguments
 *
 * @return array{0: string, 1: array{entry: ?string, name: ?string, description: ?string, timeout_seconds: ?int, enabled: bool}}
 */
function harbor_command_manager_parse_create_arguments(array $arguments): array
{
    $key = $arguments[0] ?? null;

    if (! is_string($key) || harbor_is_blank($key)) {
        throw new CommandException('Missing required command key for create.', 2);
    }

    $options = [
        'entry' => null,
        'name' => null,
        'description' => null,
        'timeout_seconds' => null,
        'enabled' => true,
    ];

    $option_arguments = array_values(array_slice($arguments, 1));

    foreach ($option_arguments as $option_argument) {
        if (! is_string($option_argument) || harbor_is_blank($option_argument)) {
            continue;
        }

        if ('--disabled' === $option_argument) {
            $options['enabled'] = false;

            continue;
        }

        if ('--enabled' === $option_argument) {
            $options['enabled'] = true;

            continue;
        }

        if (str_starts_with($option_argument, '--entry=')) {
            $options['entry'] = harbor_command_manager_extract_option_value($option_argument, '--entry=');

            continue;
        }

        if (str_starts_with($option_argument, '--name=')) {
            $options['name'] = harbor_command_manager_extract_option_value($option_argument, '--name=');

            continue;
        }

        if (str_starts_with($option_argument, '--description=')) {
            $options['description'] = harbor_command_manager_extract_option_value($option_argument, '--description=');

            continue;
        }

        if (str_starts_with($option_argument, '--timeout=')) {
            $timeout_value = harbor_command_manager_extract_option_value($option_argument, '--timeout=');
            if (! is_string($timeout_value) || harbor_is_blank($timeout_value) || 1 !== preg_match('/^[0-9]+$/', $timeout_value)) {
                throw new CommandException('Invalid --timeout option. Use a positive integer value.', 2);
            }

            $timeout_seconds = (int) $timeout_value;
            if ($timeout_seconds <= 0) {
                throw new CommandException('Invalid --timeout option. Use a positive integer value.', 2);
            }

            $options['timeout_seconds'] = $timeout_seconds;

            continue;
        }

        throw new CommandException(sprintf('Unknown create option: %s', $option_argument), 2);
    }

    return [$key, $options];
}

/**
 * @param array<int, string> $arguments
 *
 * @return array{0: string, 1: array<int, string>}
 */
function harbor_command_manager_parse_run_arguments(array $arguments): array
{
    $key = $arguments[0] ?? null;

    if (! is_string($key) || harbor_is_blank($key)) {
        throw new CommandException('Missing required command key for run.', 2);
    }

    $forwarded_arguments = array_values(array_slice($arguments, 1));

    if ('--' === ($forwarded_arguments[0] ?? null)) {
        array_shift($forwarded_arguments);
    }

    $normalized_forwarded_arguments = [];

    foreach ($forwarded_arguments as $forwarded_argument) {
        if (! is_string($forwarded_argument)) {
            continue;
        }

        $normalized_forwarded_arguments[] = $forwarded_argument;
    }

    return [$key, $normalized_forwarded_arguments];
}

/**
 * @param array<int, string> $arguments
 */
function harbor_command_manager_parse_compile_arguments(array $arguments): ?string
{
    if (empty($arguments)) {
        return null;
    }

    if (count($arguments) > 1) {
        throw new CommandException('Compile accepts at most one argument: [path-to-.commands|directory].', 2);
    }

    $path_argument = $arguments[0] ?? null;

    if (! is_string($path_argument) || harbor_is_blank($path_argument)) {
        return null;
    }

    return $path_argument;
}

function harbor_command_manager_extract_option_value(string $option_argument, string $prefix): ?string
{
    $value = substr($option_argument, strlen($prefix));

    if (false === $value) {
        return null;
    }

    $trimmed_value = trim($value);

    return harbor_is_blank($trimmed_value) ? null : $trimmed_value;
}

function harbor_command_manager_print_usage(): void
{
    fwrite(STDOUT, 'Usage: harbor-command [--debug|-v] <subcommand> [options]'.PHP_EOL);
    fwrite(STDOUT, PHP_EOL);
    fwrite(STDOUT, 'Subcommands:'.PHP_EOL);
    fwrite(STDOUT, '  create <key> [--entry=path] [--name=value] [--description=value] [--timeout=seconds] [--disabled]'.PHP_EOL);
    fwrite(STDOUT, '  run <key> [-- <args...>]'.PHP_EOL);
    fwrite(STDOUT, '  compile [path-to-.commands|directory]'.PHP_EOL);
}

if ('cli' === PHP_SAPI) {
    $script_file = $_SERVER['SCRIPT_FILENAME'] ?? null;
    $resolved_script_file = is_string($script_file) ? realpath($script_file) : false;

    if (is_string($resolved_script_file) && __FILE__ === $resolved_script_file) {
        exit(harbor_command_manager_run($_SERVER['argv'] ?? []));
    }
}
