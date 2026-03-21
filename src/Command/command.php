<?php

declare(strict_types=1);

namespace Harbor\Command;

require_once __DIR__.'/../Support/value.php';

require_once __DIR__.'/../../bin/commands/harbor-command/CommandException.php';

require_once __DIR__.'/../../bin/commands/harbor-command/BaseCommand.php';

require_once __DIR__.'/../../bin/commands/harbor-command/CommandCompiler.php';

require_once __DIR__.'/../../bin/commands/harbor-command/RunCommand.php';

use Harbor\CommandSystem\CommandCompiler;
use Harbor\CommandSystem\RunCommand;

use function Harbor\Support\harbor_is_blank;

/** Public */
/**
 * @param array<int, mixed> $forwarded_arguments
 */
function command_run(
    string $key,
    array $forwarded_arguments = [],
    ?string $working_directory = null,
    bool $debug_mode = false
): int {
    $resolved_working_directory = command_internal_resolve_working_directory($working_directory);
    $normalized_arguments = command_internal_normalize_forwarded_arguments($forwarded_arguments);

    $compiler = new CommandCompiler();
    $run_command = new RunCommand($debug_mode, $compiler);

    return $run_command->execute($key, $normalized_arguments, $resolved_working_directory);
}

/** Private */
function command_internal_resolve_working_directory(?string $working_directory = null): string
{
    if (is_string($working_directory) && ! harbor_is_blank($working_directory)) {
        return rtrim($working_directory, '/\\');
    }

    $resolved_working_directory = getcwd();
    if (false === $resolved_working_directory || harbor_is_blank($resolved_working_directory)) {
        throw new \RuntimeException('Unable to resolve current working directory.');
    }

    return $resolved_working_directory;
}

/**
 * @param array<int, mixed> $forwarded_arguments
 *
 * @return array<int, string>
 */
function command_internal_normalize_forwarded_arguments(array $forwarded_arguments): array
{
    $normalized_arguments = [];

    foreach ($forwarded_arguments as $forwarded_argument) {
        if (! is_string($forwarded_argument)) {
            continue;
        }

        $normalized_arguments[] = $forwarded_argument;
    }

    return $normalized_arguments;
}
