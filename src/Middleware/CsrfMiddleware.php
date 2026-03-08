<?php

declare(strict_types=1);

namespace Harbor\Middleware;

use Harbor\Response\ResponseStatus;

require_once __DIR__.'/../Response/response.php';

require_once __DIR__.'/../Support/value.php';

use function Harbor\Response\abort;
use function Harbor\Support\harbor_is_blank;

final class CsrfMiddleware
{
    private ?\Closure $token_resolver;
    private readonly \Closure $failure_handler;
    private readonly array $safe_methods;

    public function __construct(
        ?callable $token_resolver = null,
        array $safe_methods = ['GET', 'HEAD', 'OPTIONS', 'TRACE'],
        private readonly string $body_token_key = '_token',
        private readonly string $header_token_key = 'x-csrf-token',
        private readonly string $cookie_token_key = 'XSRF-TOKEN',
        private readonly int|ResponseStatus $failure_status = ResponseStatus::FORBIDDEN,
        ?callable $failure_handler = null,
    ) {
        $this->token_resolver = is_callable($token_resolver) ? \Closure::fromCallable($token_resolver) : null;
        $this->safe_methods = $this->normalize_methods($safe_methods);
        $this->failure_handler = is_callable($failure_handler)
            ? \Closure::fromCallable($failure_handler)
            : static fn (array $request, int|ResponseStatus $status): never => abort($status);
    }

    public function __invoke(): callable
    {
        return function (array $request, callable $next): mixed {
            $request_method = strtoupper((string) ($request['method'] ?? 'GET'));
            if (in_array($request_method, $this->safe_methods, true)) {
                return $next($request);
            }

            $expected_token = $this->resolve_expected_token($request);
            $submitted_token = $this->resolve_submitted_token($request);

            if (harbor_is_blank($expected_token) || harbor_is_blank($submitted_token) || ! hash_equals($expected_token, $submitted_token)) {
                $failure_handler = $this->failure_handler;

                return $failure_handler($request, $this->failure_status);
            }

            return $next($request);
        };
    }

    private function resolve_expected_token(array $request): string
    {
        if ($this->token_resolver instanceof \Closure) {
            $resolved_token = ($this->token_resolver)($request);
            $normalized_token = is_string($resolved_token) ? trim($resolved_token) : '';

            if (! harbor_is_blank($normalized_token)) {
                return $normalized_token;
            }
        }

        $cookies = is_array($request['cookies'] ?? null) ? $request['cookies'] : [];
        $token = $cookies[$this->cookie_token_key]
            ?? $cookies[strtolower($this->cookie_token_key)]
            ?? '';

        return is_string($token) ? trim($token) : '';
    }

    private function resolve_submitted_token(array $request): string
    {
        $headers = is_array($request['headers'] ?? null) ? $request['headers'] : [];
        $header_key = strtolower(trim($this->header_token_key));
        $header_token = $headers[$header_key] ?? '';

        if (is_string($header_token) && ! harbor_is_blank(trim($header_token))) {
            return trim($header_token);
        }

        $body = is_array($request['body'] ?? null) ? $request['body'] : [];
        $body_token = $this->array_get($body, $this->body_token_key, '');

        return is_string($body_token) ? trim($body_token) : '';
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

    private function array_get(array $array, string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        $keys = explode('.', $key);
        $current = $array;

        foreach ($keys as $segment_key) {
            if (! is_array($current) || ! array_key_exists($segment_key, $current)) {
                return $default;
            }

            $current = $current[$segment_key];
        }

        return $current;
    }
}
