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
    $preprocessed_content = harbor_pre_process_routes_file($normalized_path);
    $routes = harbor_compile_routes_from_content($preprocessed_content);

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

function harbor_pre_process_routes_file(string $router_source_path, array $include_stack = []): string
{
    $resolved_source_path = realpath($router_source_path);
    if (false === $resolved_source_path) {
        $resolved_source_path = $router_source_path;
    }

    if (in_array($resolved_source_path, $include_stack, true)) {
        $full_stack = array_merge($include_stack, [$resolved_source_path]);

        fwrite(STDERR, sprintf('Circular #include detected: %s%s', implode(' -> ', $full_stack), PHP_EOL));

        exit(1);
    }

    $router_content = file_get_contents($resolved_source_path);
    if (false === $router_content) {
        fwrite(STDERR, sprintf('Failed to read route source file: %s%s', $resolved_source_path, PHP_EOL));

        exit(1);
    }

    $line_parts = preg_split('/\R/u', $router_content);
    $lines = is_array($line_parts) ? $line_parts : [];
    $current_stack = array_merge($include_stack, [$resolved_source_path]);
    $processed_parts = [];

    foreach ($lines as $line) {
        $include_path = harbor_parse_include_path($line);
        if (null === $include_path) {
            $processed_parts[] = $line;

            continue;
        }

        $resolved_include_path = $include_path;
        if (! harbor_is_absolute_path($include_path)) {
            $resolved_include_path = dirname($resolved_source_path).'/'.$include_path;
        }

        if (! is_file($resolved_include_path)) {
            fwrite(STDERR, sprintf('Failed to read included file: %s (from %s)%s', $resolved_include_path, $resolved_source_path, PHP_EOL));

            exit(1);
        }

        $processed_parts[] = harbor_pre_process_routes_file($resolved_include_path, $current_stack);
    }

    return implode(PHP_EOL, $processed_parts);
}

function harbor_parse_include_path(string $line): ?string
{
    if (1 !== preg_match('/^\s*#include\s+["\'](.+)["\']\s*$/', trim($line), $matches)) {
        return null;
    }

    $path = trim($matches[1]);

    return '' === $path ? null : $path;
}

function harbor_is_absolute_path(string $path): bool
{
    return 1 === preg_match('#^([a-zA-Z]:[\\\\/]|/)#', $path);
}

function harbor_compile_routes_from_content(string $router_content): array
{
    $routes = [];
    $parsed_route = [];
    $line_parts = preg_split('/\R/u', $router_content);
    $lines = is_array($line_parts) ? $line_parts : [];

    foreach ($lines as $line) {
        $normalized_line = trim($line);
        if ('' === $normalized_line) {
            continue;
        }

        if ('#route' === $normalized_line) {
            $parsed_route = [];

            continue;
        }

        if ('#endroute' === $normalized_line) {
            $routes[] = $parsed_route;

            continue;
        }

        $parts = explode(':', $normalized_line, 2);
        if (2 === count($parts)) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            $parsed_route[$key] = $value;
        }
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
