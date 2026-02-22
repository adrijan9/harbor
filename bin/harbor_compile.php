<?php

declare(strict_types=1);

function harbor_run_compile(string $router_source_path): void
{
    $normalized_path = harbor_resolve_router_source_path($router_source_path);

    if ('' === $normalized_path) {
        fwrite(STDERR, 'Missing .router path. Usage: ./bin/harbor . | ./bin/harbor /path/to/.router'.PHP_EOL);

        exit(1);
    }

    if (! str_ends_with($normalized_path, '.router')) {
        fwrite(STDERR, sprintf('Expected a .router file path, got: %s%s', $normalized_path, PHP_EOL));

        exit(1);
    }

    if (! is_file($normalized_path)) {
        fwrite(STDERR, sprintf('Route source file not found: %s%s', $normalized_path, PHP_EOL));

        exit(1);
    }

    $routes_output_path = harbor_resolve_routes_output_path($normalized_path);
    $router_content = file_get_contents($normalized_path);

    if (false === $router_content) {
        fwrite(STDERR, sprintf('Failed to read route source file: %s%s', $normalized_path, PHP_EOL));

        exit(1);
    }

    $routes = harbor_compile_routes_from_content($router_content);

    $routes_directory = dirname($routes_output_path);
    if (! is_dir($routes_directory) && ! mkdir($routes_directory, 0o777, true) && ! is_dir($routes_directory)) {
        fwrite(STDERR, sprintf('Failed to create routes directory: %s%s', $routes_directory, PHP_EOL));

        exit(1);
    }

    $written = file_put_contents($routes_output_path, '<?php return '.var_export($routes, true).';');
    if (false === $written) {
        fwrite(STDERR, sprintf('Failed to write routes file: %s%s', $routes_output_path, PHP_EOL));

        exit(1);
    }

    fwrite(STDOUT, sprintf('Routes file generated: %s%s', $routes_output_path, PHP_EOL));
}

function harbor_compile_routes_from_content(string $router_content): array
{
    $routes = [];
    $parsed_route = [];
    $line = strtok($router_content, PHP_EOL);

    while (false !== $line) {
        if ('#route' === $line) {
            $parsed_route = [];
        } elseif ('#endroute' === $line) {
            $routes[] = $parsed_route;
        } else {
            $parts = explode(':', $line, 2);
            if (2 === count($parts)) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                $parsed_route[$key] = $value;
            }
        }

        $line = strtok(PHP_EOL);
    }

    $routes[] = [
        'method' => 'GET',
        'path' => '/404',
        'entry' => 'not_found.php',
    ];

    return $routes;
}

function harbor_resolve_routes_output_path(string $router_source_path): string
{
    $resolved_path = realpath($router_source_path);

    if (false !== $resolved_path) {
        return dirname($resolved_path).'/routes.php';
    }

    return dirname($router_source_path).'/routes.php';
}

function harbor_resolve_router_source_path(string $input_path): string
{
    $normalized_input = trim($input_path);

    if ('' === $normalized_input) {
        return '';
    }

    if (is_dir($normalized_input)) {
        return rtrim($normalized_input, '/\\').'/.router';
    }

    return $normalized_input;
}
