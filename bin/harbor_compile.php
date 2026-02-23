<?php

declare(strict_types=1);

require_once __DIR__.'/../src/Support/value.php';

use function Harbor\Support\harbor_is_blank;
use function Harbor\Support\harbor_is_null;

function harbor_run_compile(string $router_source_path): void
{
    $normalized_path = harbor_resolve_router_source_path($router_source_path);

    if (harbor_is_blank($normalized_path)) {
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
    $compiled_router = harbor_compile_router_from_content($preprocessed_content);

    $routes_directory = dirname($routes_output_path);
    if (! is_dir($routes_directory) && ! mkdir($routes_directory, 0o777, true) && ! is_dir($routes_directory)) {
        fwrite(STDERR, sprintf('Failed to create routes directory: %s%s', $routes_directory, PHP_EOL));

        exit(1);
    }

    $written = file_put_contents($routes_output_path, '<?php return '.var_export($compiled_router, true).';');
    if (false === $written) {
        fwrite(STDERR, sprintf('Failed to write routes file: %s%s', $routes_output_path, PHP_EOL));

        exit(1);
    }

    harbor_format_generated_routes_file($routes_output_path);

    fwrite(STDOUT, sprintf('Routes file generated: %s%s', $routes_output_path, PHP_EOL));
}

function harbor_format_generated_routes_file(string $routes_output_path): void
{
    $php_cs_fixer_binary_path = harbor_resolve_php_cs_fixer_binary_path();
    if (harbor_is_null($php_cs_fixer_binary_path)) {
        return;
    }

    $php_binary_path = harbor_is_blank(PHP_BINARY) ? 'php' : PHP_BINARY;
    $command_parts = [
        escapeshellarg($php_binary_path),
        escapeshellarg($php_cs_fixer_binary_path),
        'fix',
        '--path-mode=override',
        '--using-cache=no',
        '--quiet',
    ];

    $php_cs_fixer_config_path = harbor_resolve_php_cs_fixer_config_path();
    if (! harbor_is_null($php_cs_fixer_config_path)) {
        $command_parts[] = '--config='.escapeshellarg($php_cs_fixer_config_path);
    }

    $command_parts[] = escapeshellarg($routes_output_path);
    $command = implode(' ', $command_parts).' 2>&1';

    $project_root_path = harbor_resolve_project_root_path();
    if (! harbor_is_null($project_root_path)) {
        $command = 'cd '.escapeshellarg($project_root_path).' && '.$command;
    }

    $output_lines = [];
    $exit_code = 0;
    exec($command, $output_lines, $exit_code);

    if (0 === $exit_code) {
        return;
    }

    fwrite(STDERR, sprintf('Failed to run php-cs-fixer for routes file: %s%s', $routes_output_path, PHP_EOL));

    if (! empty($output_lines)) {
        fwrite(STDERR, implode(PHP_EOL, $output_lines).PHP_EOL);
    }

    exit(1);
}

function harbor_resolve_php_cs_fixer_binary_path(): ?string
{
    $project_root_path = harbor_resolve_project_root_path();
    if (harbor_is_null($project_root_path)) {
        return null;
    }

    $candidate_paths = [
        $project_root_path.'/vendor/bin/php-cs-fixer',
        $project_root_path.'/vendor/bin/php-cs-fixer.phar',
    ];

    if ('\\' === DIRECTORY_SEPARATOR) {
        $candidate_paths[] = $project_root_path.'/vendor/bin/php-cs-fixer.bat';
    }

    foreach ($candidate_paths as $candidate_path) {
        if (is_file($candidate_path)) {
            return $candidate_path;
        }
    }

    return null;
}

function harbor_resolve_php_cs_fixer_config_path(): ?string
{
    $project_root_path = harbor_resolve_project_root_path();
    if (harbor_is_null($project_root_path)) {
        return null;
    }

    $candidate_paths = [
        $project_root_path.'/.php-cs-fixer.php',
        $project_root_path.'/.php-cs-fixer.dist.php',
    ];

    foreach ($candidate_paths as $candidate_path) {
        if (is_file($candidate_path)) {
            return $candidate_path;
        }
    }

    return null;
}

function harbor_resolve_project_root_path(): ?string
{
    $project_root_path = realpath(__DIR__.'/..');

    return false === $project_root_path ? null : $project_root_path;
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
        if (harbor_is_null($include_path)) {
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

    return harbor_is_blank($path) ? null : $path;
}

function harbor_is_absolute_path(string $path): bool
{
    return 1 === preg_match('#^([a-zA-Z]:[\\\\/]|/)#', $path);
}

function harbor_compile_router_from_content(string $router_content): array
{
    $routes = [];
    $parsed_route = [];
    $assets_path = null;
    $line_parts = preg_split('/\R/u', $router_content);
    $lines = is_array($line_parts) ? $line_parts : [];
    $saw_non_assets_definition = false;

    foreach ($lines as $line) {
        $normalized_line = trim($line);
        if (harbor_is_blank($normalized_line)) {
            continue;
        }

        $assets_candidate = harbor_parse_assets_path($normalized_line);
        if (! harbor_is_null($assets_candidate)) {
            if ($saw_non_assets_definition) {
                fwrite(STDERR, 'The <assets> directive must be defined at the top of the .router file.'.PHP_EOL);

                exit(1);
            }

            if (! harbor_is_null($assets_path)) {
                fwrite(STDERR, 'Duplicate <assets> directive found. Define it only once.'.PHP_EOL);

                exit(1);
            }

            $assets_path = harbor_normalize_assets_path($assets_candidate);

            continue;
        }

        if (harbor_is_assets_tag($normalized_line)) {
            fwrite(STDERR, sprintf('Invalid <assets> directive: %s%s', $normalized_line, PHP_EOL));

            exit(1);
        }

        $saw_non_assets_definition = true;

        if (harbor_is_route_open_tag($normalized_line)) {
            $parsed_route = [];

            continue;
        }

        if (harbor_is_route_close_tag($normalized_line)) {
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

    return [
        'assets' => $assets_path,
        'routes' => $routes,
    ];
}

function harbor_compile_routes_from_content(string $router_content): array
{
    $compiled_router = harbor_compile_router_from_content($router_content);
    $routes = $compiled_router['routes'] ?? [];

    return is_array($routes) ? $routes : [];
}

function harbor_is_route_open_tag(string $line): bool
{
    return in_array($line, ['<route>', '#route'], true);
}

function harbor_is_route_close_tag(string $line): bool
{
    return in_array($line, ['</route>', '#endroute'], true);
}

function harbor_is_assets_tag(string $line): bool
{
    return 1 === preg_match('/^<assets>.*<\/assets>$/', $line);
}

function harbor_parse_assets_path(string $line): ?string
{
    if (1 !== preg_match('/^<assets>(.*)<\/assets>$/', $line, $matches)) {
        return null;
    }

    $assets_path = trim($matches[1]);

    return harbor_is_blank($assets_path) ? null : $assets_path;
}

function harbor_normalize_assets_path(string $assets_path): string
{
    $normalized_assets_path = str_replace('\\', '/', trim($assets_path));
    $normalized_assets_path = '/'.ltrim($normalized_assets_path, '/');

    if ('/' === $normalized_assets_path) {
        fwrite(STDERR, 'The <assets> directive path cannot be "/".'.PHP_EOL);

        exit(1);
    }

    return rtrim($normalized_assets_path, '/');
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

    if (harbor_is_blank($normalized_input)) {
        return '';
    }

    if (is_dir($normalized_input)) {
        return rtrim($normalized_input, '/\\').'/.router';
    }

    return $normalized_input;
}
