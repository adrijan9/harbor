<?php

declare(strict_types=1);

namespace Harbor\Middleware;

use Harbor\Response\ResponseStatus;

require_once __DIR__.'/../Response/response.php';

require_once __DIR__.'/../Support/value.php';

use function Harbor\Response\abort;
use function Harbor\Response\response_header;
use function Harbor\Response\response_status;
use function Harbor\Support\harbor_is_blank;

final class CorsMiddleware
{
    private readonly array $allowed_origins;
    private readonly array $allowed_methods;
    private readonly array $allowed_headers;
    private readonly array $exposed_headers;
    private readonly \Closure $forbidden_handler;

    public function __construct(
        array $allowed_origins = ['*'],
        array $allowed_methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        array $allowed_headers = ['Content-Type', 'Authorization', 'X-Requested-With', 'X-CSRF-Token'],
        array $exposed_headers = [],
        private readonly bool $allow_credentials = false,
        private readonly int $max_age_seconds = 600,
        private readonly int|ResponseStatus $forbidden_status = ResponseStatus::FORBIDDEN,
        ?callable $forbidden_handler = null,
    ) {
        $this->allowed_origins = $this->normalize_values($allowed_origins);
        $this->allowed_methods = $this->normalize_methods($allowed_methods);
        $this->allowed_headers = $this->normalize_values($allowed_headers);
        $this->exposed_headers = $this->normalize_values($exposed_headers);
        $this->forbidden_handler = is_callable($forbidden_handler)
            ? \Closure::fromCallable($forbidden_handler)
            : static fn (array $request, int|ResponseStatus $status): never => abort($status);
    }

    public function __invoke(): callable
    {
        return function (array $request, callable $next): mixed {
            $request_headers = is_array($request['headers'] ?? null) ? $request['headers'] : [];
            $request_origin = $request_headers['origin'] ?? null;

            if (! is_string($request_origin) || harbor_is_blank(trim($request_origin))) {
                return $next($request);
            }

            $normalized_request_origin = trim($request_origin);

            if (! $this->origin_is_allowed($normalized_request_origin)) {
                $forbidden_handler = $this->forbidden_handler;

                return $forbidden_handler($request, $this->forbidden_status);
            }

            $allow_origin = $this->resolve_allow_origin_value($normalized_request_origin);
            response_header('Access-Control-Allow-Origin', $allow_origin);

            if ('*' !== $allow_origin) {
                response_header('Vary', 'Origin');
            }

            if ($this->allow_credentials) {
                response_header('Access-Control-Allow-Credentials', 'true');
            }

            if (! empty($this->exposed_headers)) {
                response_header('Access-Control-Expose-Headers', implode(', ', $this->exposed_headers));
            }

            $request_method = strtoupper((string) ($request['method'] ?? 'GET'));
            if ('OPTIONS' === $request_method) {
                if (! empty($this->allowed_methods)) {
                    response_header('Access-Control-Allow-Methods', implode(', ', $this->allowed_methods));
                }

                if (! empty($this->allowed_headers)) {
                    response_header('Access-Control-Allow-Headers', implode(', ', $this->allowed_headers));
                }

                if ($this->max_age_seconds > 0) {
                    response_header('Access-Control-Max-Age', (string) $this->max_age_seconds);
                }

                response_status(ResponseStatus::NO_CONTENT);

                exit;
            }

            return $next($request);
        };
    }

    private function normalize_values(array $values): array
    {
        $normalized_values = [];

        foreach ($values as $value) {
            if (! is_string($value)) {
                continue;
            }

            $normalized_value = trim($value);
            if (harbor_is_blank($normalized_value)) {
                continue;
            }

            $normalized_values[$normalized_value] = $normalized_value;
        }

        return array_values($normalized_values);
    }

    private function normalize_methods(array $methods): array
    {
        $normalized_methods = [];

        foreach ($methods as $method) {
            if (! is_string($method)) {
                continue;
            }

            $normalized_method = strtoupper(trim($method));
            if (harbor_is_blank($normalized_method)) {
                continue;
            }

            $normalized_methods[$normalized_method] = $normalized_method;
        }

        return array_values($normalized_methods);
    }

    private function origin_is_allowed(string $request_origin): bool
    {
        foreach ($this->allowed_origins as $allowed_origin) {
            if ('*' === $allowed_origin || $allowed_origin === $request_origin) {
                return true;
            }

            if (! str_contains($allowed_origin, '*')) {
                continue;
            }

            if ($this->origin_matches_wildcard($request_origin, $allowed_origin)) {
                return true;
            }
        }

        return false;
    }

    private function origin_matches_wildcard(string $request_origin, string $allowed_origin_pattern): bool
    {
        if (function_exists('fnmatch')) {
            return fnmatch($allowed_origin_pattern, $request_origin);
        }

        $escaped_pattern = preg_quote($allowed_origin_pattern, '#');
        $regex_pattern = '#^'.str_replace('\*', '.*', $escaped_pattern).'$#';

        return 1 === preg_match($regex_pattern, $request_origin);
    }

    private function resolve_allow_origin_value(string $request_origin): string
    {
        if (in_array('*', $this->allowed_origins, true) && ! $this->allow_credentials) {
            return '*';
        }

        return $request_origin;
    }
}
