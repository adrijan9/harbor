#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__.'/harbor_migrator.php';

/**
 * @param array<int, string> $arguments
 */
function harbor_migration_run(array $arguments): int
{
    return harbor_migrator_run('migration', $arguments);
}

if ('cli' === PHP_SAPI) {
    $script_file = $_SERVER['SCRIPT_FILENAME'] ?? null;
    $resolved_script_file = is_string($script_file) ? realpath($script_file) : false;

    if (is_string($resolved_script_file) && __FILE__ === $resolved_script_file) {
        exit(harbor_migration_run($_SERVER['argv'] ?? []));
    }
}
