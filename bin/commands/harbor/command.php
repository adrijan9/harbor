#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__.'/harbor_compile.php';
require_once __DIR__.'/../shared/harbor_site.php';

/**
 * @param array<int, string> $arguments
 */
function harbor_command_run(array $arguments): int
{
    $first_argument = $arguments[1] ?? null;

    if (null === $first_argument || in_array($first_argument, ['-h', '--help'], true)) {
        harbor_print_usage();

        return null === $first_argument ? 1 : 0;
    }

    if (in_array($first_argument, ['init', '/init'], true)) {
        fwrite(STDERR, 'The site scaffold command moved to "./bin/harbor-init [site-name]".'.PHP_EOL);

        return 1;
    }

    try {
        harbor_site_assert_selected();
    } catch (Throwable $throwable) {
        fwrite(STDERR, $throwable->getMessage().PHP_EOL);

        return 1;
    }

    if (! is_string($first_argument)) {
        fwrite(STDERR, 'Invalid route source path argument.'.PHP_EOL);

        return 1;
    }

    harbor_run_compile($first_argument);

    return 0;
}

function harbor_print_usage(): void
{
    fwrite(STDOUT, 'Usage: harbor <path-to-.router|directory>'.PHP_EOL);
    fwrite(STDOUT, PHP_EOL);
    fwrite(STDOUT, 'Default mode (route compile):'.PHP_EOL);
    fwrite(STDOUT, '  harbor .                 Compile ./.router into ./public/routes.php when ./public exists, otherwise ./routes.php'.PHP_EOL);
    fwrite(STDOUT, '  harbor my-site/.router   Compile into my-site/public/routes.php when my-site/public exists'.PHP_EOL);
}

if ('cli' === PHP_SAPI) {
    $script_file = $_SERVER['SCRIPT_FILENAME'] ?? null;
    $resolved_script_file = is_string($script_file) ? realpath($script_file) : false;

    if (is_string($resolved_script_file) && __FILE__ === $resolved_script_file) {
        exit(harbor_command_run($_SERVER['argv'] ?? []));
    }
}
