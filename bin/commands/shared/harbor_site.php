<?php

declare(strict_types=1);

require_once __DIR__.'/../../../src/Support/value.php';

use function Harbor\Support\harbor_is_blank;

function harbor_site_is_selected(?string $working_directory = null): bool
{
    $resolved_working_directory = harbor_site_resolve_working_directory($working_directory);

    return is_file($resolved_working_directory.'/.router');
}

function harbor_site_assert_selected(?string $working_directory = null): void
{
    $resolved_working_directory = harbor_site_resolve_working_directory($working_directory);
    if (harbor_site_is_selected($resolved_working_directory)) {
        return;
    }

    throw new RuntimeException('No selected site.');
}

function harbor_site_resolve_working_directory(?string $working_directory = null): string
{
    if (is_string($working_directory) && ! harbor_is_blank($working_directory)) {
        return rtrim($working_directory, '/\\');
    }

    $resolved_working_directory = getcwd();
    if (false === $resolved_working_directory || harbor_is_blank($resolved_working_directory)) {
        throw new RuntimeException('Unable to resolve current working directory.');
    }

    return $resolved_working_directory;
}
