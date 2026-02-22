<?php

declare(strict_types=1);

namespace Harbor\Router;

/**
 * Class Router.
 */
class Router
{
    private array $routes;

    public function __construct(private readonly string $router_path)
    {
        $this->routes = require $router_path;
    }

    public function get_uri(): string
    {
        $url = $_SERVER['REQUEST_URI'] ?? '/';

        return parse_url($url, PHP_URL_PATH) ?? '/';
    }

    public function current()
    {
        $current_uri = $this->get_uri();
        $query_params = $this->get_query_params();

        foreach ($this->routes as $route) {
            $segments = $this->extract_route_segments($route['path'], $current_uri);

            if (null !== $segments) {
                $route['segments'] = $segments;
                $route['query'] = $query_params;

                return $route;
            }
        }

        $route = $this->routes[count($this->routes) - 1];
        $route['segments'] = [];
        $route['query'] = $query_params;

        return $route;
    }

    public function render(array $variables = []): void
    {
        $current_route = $this->current();
        $entry = $current_route['entry'] ?? null;

        if (! is_string($entry) || '' === trim($entry)) {
            throw new \RuntimeException('Current route entry is invalid.');
        }

        $entry_path = $this->resolve_entry_path($entry);

        $GLOBALS['route'] = $current_route;

        $this->include_entry($entry_path, $variables);
    }

    private function extract_route_segments(string $route_path, string $current_uri): ?array
    {
        $route_segments = $this->get_path_segments($route_path);
        $current_segments = $this->get_path_segments($current_uri);
        $dynamic_segments = [];

        if (count($route_segments) !== count($current_segments)) {
            return null;
        }

        foreach ($route_segments as $index => $route_segment) {
            if ('$' === $route_segment) {
                $dynamic_segments[] = $current_segments[$index];

                continue;
            }

            if ($route_segment !== $current_segments[$index]) {
                return null;
            }
        }

        return $dynamic_segments;
    }

    private function get_path_segments(string $path): array
    {
        $trimmed_path = trim($path, '/');

        if ('' === $trimmed_path) {
            return [];
        }

        return explode('/', $trimmed_path);
    }

    private function get_query_params(): array
    {
        $url = $_SERVER['REQUEST_URI'] ?? '/';
        $query = parse_url($url, PHP_URL_QUERY);

        if (! is_string($query) || '' === $query) {
            return [];
        }

        parse_str($query, $params);

        return is_array($params) ? $params : [];
    }

    private function resolve_entry_path(string $entry): string
    {
        if ($this->is_absolute_path($entry) && is_file($entry)) {
            return $entry;
        }

        $normalized_entry = ltrim($entry, '/');
        $candidates = [
            dirname($this->router_path).'/'.$normalized_entry,
            __DIR__.'/../../public/'.$normalized_entry,
            $entry,
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        throw new \RuntimeException(sprintf('Route entry "%s" not found.', $entry));
    }

    private function include_entry(string $entry_path, array $variables): void
    {
        extract($variables, EXTR_SKIP);

        require $entry_path;
    }

    private function is_absolute_path(string $path): bool
    {
        return 1 === preg_match('#^([a-zA-Z]:[\\\/]|/)#', $path);
    }
}
