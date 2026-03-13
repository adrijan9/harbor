#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__.'/harbor_init.php';

/**
 * @param array<int, string> $arguments
 */
function harbor_init_run(array $arguments): int
{
    $input_arguments = array_values(array_slice($arguments, 1));
    $first_argument = $input_arguments[0] ?? null;

    if (is_string($first_argument) && in_array($first_argument, ['-h', '--help'], true)) {
        harbor_init_print_usage();

        return 0;
    }

    if (count($input_arguments) > 1) {
        fwrite(STDERR, 'Too many arguments.'.PHP_EOL);
        harbor_init_print_usage();

        return 1;
    }

    harbor_run_init(is_string($first_argument) ? $first_argument : null);

    return 0;
}

function harbor_init_print_usage(): void
{
    fwrite(STDOUT, 'Usage: harbor-init [site-name]'.PHP_EOL);
    fwrite(STDOUT, PHP_EOL);
    fwrite(STDOUT, 'Examples:'.PHP_EOL);
    fwrite(STDOUT, '  ./bin/harbor-init'.PHP_EOL);
    fwrite(STDOUT, '  ./bin/harbor-init my-site'.PHP_EOL);
}

if ('cli' === PHP_SAPI) {
    $script_file = $_SERVER['SCRIPT_FILENAME'] ?? null;
    $resolved_script_file = is_string($script_file) ? realpath($script_file) : false;

    if (is_string($resolved_script_file) && __FILE__ === $resolved_script_file) {
        exit(harbor_init_run($_SERVER['argv'] ?? []));
    }
}
