<?php

declare(strict_types=1);

namespace Harbor\Router;

require_once __DIR__.'/../../Support/value.php';

use function Harbor\Support\harbor_is_blank;
use function Harbor\Support\harbor_is_null;

/**
 * Named route functions.
 */
function route_exists(string $name): bool
{
    if (harbor_is_blank($name)) {
        return false;
    }

    return ! harbor_is_null(route_find_by_name($name));
}

function route_name_is(string $name): bool
{
    if (harbor_is_blank($name)) {
        return false;
    }

    $current_name = route_current_name();

    return ! harbor_is_null($current_name) && $current_name === $name;
}

function route(string $name, array $parameters = []): string
{
    $definition = route_find_by_name($name);
    if (harbor_is_null($definition)) {
        throw new \InvalidArgumentException(sprintf('Route "%s" is not defined.', $name));
    }

    $path = $definition['path'] ?? null;
    if (! is_string($path) || harbor_is_blank($path)) {
        throw new \RuntimeException(sprintf('Route "%s" has invalid path.', $name));
    }

    return route_compile_named_path($name, $path, $parameters);
}

function route_current_name(): ?string
{
    global $route;

    if (! isset($route) || ! is_array($route)) {
        return null;
    }

    $current_name = $route['name'] ?? null;

    return is_string($current_name) ? $current_name : null;
}

/**
 * @return array<int, array<string, mixed>>
 */
function route_all_definitions(): array
{
    global $routes, $route;

    if (is_array($routes)) {
        return $routes;
    }

    if (isset($route) && is_array($route)) {
        return [$route];
    }

    return [];
}

function route_find_by_name(string $name): ?array
{
    foreach (route_all_definitions() as $definition) {
        $route_name = $definition['name'] ?? null;
        if (! is_string($route_name)) {
            continue;
        }

        if ($route_name === $name) {
            return $definition;
        }
    }

    return null;
}

function route_compile_named_path(string $name, string $path, array $parameters): string
{
    $trimmed_path = trim($path, '/');
    if (harbor_is_blank($trimmed_path)) {
        if (! empty($parameters)) {
            throw new \InvalidArgumentException(sprintf('Route "%s" does not accept parameters.', $name));
        }

        return '/';
    }

    $segments = explode('/', $trimmed_path);
    $parameter_values = array_values($parameters);
    $parameter_index = 0;

    foreach ($segments as $index => $segment) {
        if ('$' !== $segment) {
            continue;
        }

        if (! array_key_exists($parameter_index, $parameter_values)) {
            throw new \InvalidArgumentException(
                sprintf('Missing parameter at index %d for route "%s".', $parameter_index, $name)
            );
        }

        $segments[$index] = rawurlencode(route_parameter_to_string($parameter_values[$parameter_index], $name, $parameter_index));
        $parameter_index++;
    }

    if ($parameter_index < count($parameter_values)) {
        throw new \InvalidArgumentException(
            sprintf('Too many parameters for route "%s". Expected %d, got %d.', $name, $parameter_index, count($parameter_values))
        );
    }

    return '/'.implode('/', $segments);
}

function route_parameter_to_string(mixed $value, string $name, int $index): string
{
    if (harbor_is_null($value)) {
        throw new \InvalidArgumentException(
            sprintf('Route "%s" parameter at index %d cannot be null.', $name, $index)
        );
    }

    if (is_bool($value)) {
        return $value ? '1' : '0';
    }

    if (is_scalar($value)) {
        return (string) $value;
    }

    if (is_object($value) && method_exists($value, '__toString')) {
        return (string) $value;
    }

    throw new \InvalidArgumentException(
        sprintf('Route "%s" parameter at index %d must be scalar or stringable.', $name, $index)
    );
}
