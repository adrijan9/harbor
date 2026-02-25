<?php

declare(strict_types=1);

namespace Harbor\Router;

require_once __DIR__.'/../Config/config.php';
require_once __DIR__.'/../Support/value.php';

use function Harbor\Config\config_init_global;
use function Harbor\Support\harbor_is_blank;
use function Harbor\Support\harbor_is_null;

/**
 * Class Router.
 */
class Router
{
    private array $routes;
    private ?string $assets_path = null;

    public function __construct(
        private readonly string $router_path,
        private readonly string $config_path,
    ) {
        $compiled_router = require $router_path;
        [$this->routes, $this->assets_path] = $this->normalize_compiled_router($compiled_router);
        $GLOBALS['routes'] = $this->routes;
        $GLOBALS['route_assets_path'] = $this->assets_path;

        config_init_global($this->config_path);
    }

    public function get_uri(): string
    {
        $url = $_SERVER['REQUEST_URI'] ?? '/';

        return parse_url($url, PHP_URL_PATH) ?? '/';
    }

    public function current()
    {
        $current_uri = $this->get_uri();
        $request_method = $this->get_request_method();
        $query_params = $this->get_query_params();
        $allowed_methods_for_matched_path = [];

        foreach ($this->routes as $route) {
            if (! is_array($route)) {
                continue;
            }

            $route_path = $route['path'] ?? null;
            if (! is_string($route_path)) {
                continue;
            }

            $segments = $this->extract_route_segments($route_path, $current_uri);
            if (null === $segments) {
                continue;
            }

            if (! $this->route_allows_request_method($route, $request_method)) {
                $route_method = $this->resolve_route_method($route);
                if (is_string($route_method)) {
                    $allowed_methods_for_matched_path[$route_method] = $route_method;
                }

                continue;
            }

            $route['segments'] = $segments;
            $route['query'] = $query_params;

            return $route;
        }

        if (! empty($allowed_methods_for_matched_path)) {
            return $this->resolve_method_not_allowed_route($query_params, array_values($allowed_methods_for_matched_path));
        }

        $route = $this->resolve_not_found_route();
        $route['segments'] = [];
        $route['query'] = $query_params;

        return $route;
    }

    public function render(array $variables = []): void
    {
        if ($this->render_asset_if_configured()) {
            return;
        }

        $current_route = $this->current();
        $GLOBALS['route'] = $current_route;

        if ($this->is_method_not_allowed_route($current_route)) {
            $this->render_method_not_allowed_response($current_route);

            return;
        }

        $entry = $current_route['entry'] ?? null;
        $normalized_entry = is_string($entry) ? trim($entry) : '';

        if (! is_string($entry) || harbor_is_blank($normalized_entry)) {
            throw new \RuntimeException('Current route entry is invalid.');
        }

        $entry_path = $this->resolve_entry_path($entry);

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

        if (harbor_is_blank($trimmed_path)) {
            return [];
        }

        return explode('/', $trimmed_path);
    }

    private function get_query_params(): array
    {
        $url = $_SERVER['REQUEST_URI'] ?? '/';
        $query = parse_url($url, PHP_URL_QUERY);

        if (! is_string($query) || harbor_is_blank($query)) {
            return [];
        }

        parse_str($query, $params);

        return is_array($params) ? $params : [];
    }

    private function get_request_method(): string
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        return strtoupper(is_string($method) ? $method : 'GET');
    }

    private function route_allows_request_method(array $route, string $request_method): bool
    {
        $normalized_route_method = $this->resolve_route_method($route);
        if (harbor_is_null($normalized_route_method)) {
            return true;
        }

        return $normalized_route_method === $request_method;
    }

    private function resolve_route_method(array $route): ?string
    {
        $route_method = $route['method'] ?? null;
        if (! is_string($route_method)) {
            return null;
        }

        $normalized_route_method = strtoupper(trim($route_method));
        if (harbor_is_blank($normalized_route_method)) {
            return null;
        }

        return $normalized_route_method;
    }

    private function resolve_method_not_allowed_route(array $query_params, array $allowed_methods): array
    {
        $route = $this->resolve_not_found_route();
        $route['segments'] = [];
        $route['query'] = $query_params;
        $route['status'] = 405;
        $route['allowed_methods'] = $allowed_methods;

        return $route;
    }

    private function is_method_not_allowed_route(array $route): bool
    {
        $status = $route['status'] ?? null;

        return is_int($status) && 405 === $status;
    }

    private function render_method_not_allowed_response(array $route): void
    {
        $allowed_methods = $this->normalize_allowed_methods($route['allowed_methods'] ?? []);
        $prefers_json_response = $this->request_prefers_json_response();

        if (! headers_sent()) {
            http_response_code(405);
            if (! empty($allowed_methods)) {
                header('Allow: '.implode(', ', $allowed_methods));
            }

            if ($prefers_json_response) {
                header('Content-Type: application/json; charset=UTF-8');
            }
        }

        if ($prefers_json_response) {
            $json_response = json_encode([
                'message' => 'Method Not Allowed',
                'status' => 405,
                'allowed_methods' => $allowed_methods,
            ]);

            echo is_string($json_response) ? $json_response : '{"message":"Method Not Allowed","status":405}';

            return;
        }

        echo 'Method Not Allowed';
    }

    private function normalize_allowed_methods(mixed $allowed_methods): array
    {
        if (! is_array($allowed_methods)) {
            return [];
        }

        $normalized_allowed_methods = [];

        foreach ($allowed_methods as $allowed_method) {
            if (! is_string($allowed_method)) {
                continue;
            }

            $normalized_allowed_method = strtoupper(trim($allowed_method));
            if (harbor_is_blank($normalized_allowed_method)) {
                continue;
            }

            $normalized_allowed_methods[$normalized_allowed_method] = $normalized_allowed_method;
        }

        return array_values($normalized_allowed_methods);
    }

    private function request_prefers_json_response(): bool
    {
        $accept_header = $_SERVER['HTTP_ACCEPT'] ?? null;
        if (! is_string($accept_header) || harbor_is_blank($accept_header)) {
            return false;
        }

        $accepted_media_types = array_map('trim', explode(',', strtolower($accept_header)));

        foreach ($accepted_media_types as $accepted_media_type) {
            if (harbor_is_blank($accepted_media_type)) {
                continue;
            }

            $media_type = trim(explode(';', $accepted_media_type, 2)[0]);
            if ('application/json' === $media_type || str_ends_with($media_type, '+json')) {
                return true;
            }
        }

        return false;
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

    private function normalize_compiled_router(mixed $compiled_router): array
    {
        if (! is_array($compiled_router)) {
            throw new \RuntimeException('Compiled routes payload is invalid.');
        }

        if (array_key_exists('routes', $compiled_router)) {
            $compiled_routes = $compiled_router['routes'];
            if (! is_array($compiled_routes)) {
                throw new \RuntimeException('Compiled routes payload is invalid.');
            }

            $compiled_assets_path = $compiled_router['assets'] ?? null;
            if (! harbor_is_null($compiled_assets_path) && ! is_string($compiled_assets_path)) {
                throw new \RuntimeException('Compiled assets path is invalid.');
            }

            return [$compiled_routes, is_string($compiled_assets_path) ? $compiled_assets_path : null];
        }

        return [$compiled_router, null];
    }

    private function resolve_not_found_route(): array
    {
        for ($index = count($this->routes) - 1; $index >= 0; --$index) {
            $route = $this->routes[$index];
            if (is_array($route)) {
                return $route;
            }
        }

        throw new \RuntimeException('No routes are defined.');
    }

    private function render_asset_if_configured(): bool
    {
        if (harbor_is_blank($this->assets_path)) {
            return false;
        }

        $request_uri_path = $this->get_uri();
        $normalized_assets_uri_path = $this->normalize_assets_uri_path($this->assets_path);

        if ($request_uri_path !== $normalized_assets_uri_path && ! str_starts_with($request_uri_path, $normalized_assets_uri_path.'/')) {
            return false;
        }

        $requested_asset_relative_path = trim(substr($request_uri_path, strlen($normalized_assets_uri_path)), '/');
        if (harbor_is_blank($requested_asset_relative_path)) {
            return false;
        }

        $asset_file_path = $this->resolve_asset_file_path($requested_asset_relative_path);
        if (harbor_is_null($asset_file_path)) {
            return false;
        }

        $this->render_asset_file($asset_file_path);

        return true;
    }

    private function normalize_assets_uri_path(string $assets_path): string
    {
        $normalized_path = '/'.ltrim($assets_path, '/');

        return rtrim($normalized_path, '/');
    }

    private function resolve_asset_file_path(string $asset_relative_path): ?string
    {
        $normalized_assets_path = is_string($this->assets_path) ? trim($this->assets_path) : '';
        if (harbor_is_blank($normalized_assets_path)) {
            return null;
        }

        $site_root_path = dirname($this->router_path);
        $assets_directory_path = $site_root_path.'/'.ltrim(str_replace('\\', '/', $normalized_assets_path), '/');

        if (! is_dir($assets_directory_path)) {
            return null;
        }

        $resolved_assets_directory_path = realpath($assets_directory_path);
        if (false === $resolved_assets_directory_path) {
            return null;
        }

        $decoded_relative_path = rawurldecode($asset_relative_path);
        $normalized_relative_path = ltrim(str_replace('\\', '/', $decoded_relative_path), '/');
        if (harbor_is_blank($normalized_relative_path)) {
            return null;
        }

        $resolved_asset_file_path = realpath($resolved_assets_directory_path.'/'.$normalized_relative_path);
        if (false === $resolved_asset_file_path || ! is_file($resolved_asset_file_path)) {
            return null;
        }

        if (! str_starts_with($resolved_asset_file_path, $resolved_assets_directory_path.'/')) {
            return null;
        }

        return $resolved_asset_file_path;
    }

    private function render_asset_file(string $asset_file_path): void
    {
        if (! headers_sent()) {
            header('Content-Type: '.$this->resolve_asset_mime_type($asset_file_path));

            $asset_file_size = filesize($asset_file_path);
            if (false !== $asset_file_size) {
                header('Content-Length: '.(string) $asset_file_size);
            }
        }

        readfile($asset_file_path);
    }

    private function resolve_asset_mime_type(string $asset_file_path): string
    {
        $extension = strtolower(pathinfo($asset_file_path, PATHINFO_EXTENSION));
        $known_mime_types = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'mjs' => 'application/javascript',
            'json' => 'application/json',
            'map' => 'application/json',
            'html' => 'text/html; charset=UTF-8',
            'txt' => 'text/plain; charset=UTF-8',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'otf' => 'font/otf',
            'eot' => 'application/vnd.ms-fontobject',
            'pdf' => 'application/pdf',
            'xml' => 'application/xml',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
        ];

        if (array_key_exists($extension, $known_mime_types)) {
            return $known_mime_types[$extension];
        }

        $detected_mime_type = function_exists('mime_content_type') ? mime_content_type($asset_file_path) : null;
        if (is_string($detected_mime_type) && ! harbor_is_blank($detected_mime_type)) {
            return $detected_mime_type;
        }

        return 'application/octet-stream';
    }

    private function is_absolute_path(string $path): bool
    {
        return 1 === preg_match('#^([a-zA-Z]:[\\\/]|/)#', $path);
    }
}
