#!/usr/bin/env php
<?php

declare(strict_types=1);

$argc = $_SERVER['argc'] ?? 0;
echo $argc.' argument(s) provided.'.PHP_EOL;
if ($argc < 2) {
    fwrite(STDERR, 'No arguments provided. Usage: ./vendor/bin/router /path/to/.router'.PHP_EOL);

    exit(1);
}

$input_path = $_SERVER['argv'][1] ?? null;
if (null === $input_path || '' === trim($input_path)) {
    fwrite(STDERR, 'Invalid path argument provided. Usage: ./vendor/bin/router /path/to/.router'.PHP_EOL);

    exit(1);
}

echo 'Generating routes'.PHP_EOL;

if (! is_file($input_path)) {
    fwrite(STDERR, sprintf('Route source file not found: %s%s', $input_path, PHP_EOL));

    exit(1);
}

$routes_output_path = resolve_routes_output_path($input_path);

$router = file_get_contents($input_path);
if (false === $router) {
    fwrite(STDERR, sprintf('Failed to read route source file: %s%s', $input_path, PHP_EOL));

    exit(1);
}

$routes = [];

$parsed_route = [];
$route = strtok($router, PHP_EOL);
while (false !== $route) {
    if ('#route' === $route) {
        $parsed_route = [];
    } elseif ('#endroute' === $route) {
        $routes[] = $parsed_route;
    } else {
        $parts = explode(':', $route, 2);
        if (2 === count($parts)) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            $parsed_route[$key] = $value;
        }
    }
    $route = strtok(PHP_EOL);
}

$routes[] = [
    'method' => 'GET',
    'path' => '/404',
    'entry' => 'not_found.php',
];

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

echo sprintf('Routes file generated: %s%s', $routes_output_path, PHP_EOL);

function resolve_routes_output_path(string $router_source_path): string
{
    $resolved_path = realpath($router_source_path);
    if (false !== $resolved_path) {
        return dirname($resolved_path).'/routes.php';
    }

    return dirname($router_source_path).'/routes.php';
}
