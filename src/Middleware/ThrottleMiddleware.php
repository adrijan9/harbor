<?php

declare(strict_types=1);

namespace Harbor\Middleware;

use Harbor\Response\ResponseStatus;

require_once __DIR__.'/../RateLimiter/rate_limiter.php';

require_once __DIR__.'/../Response/response.php';

require_once __DIR__.'/../Support/value.php';

use function Harbor\RateLimiter\rate_limiter_available_in;
use function Harbor\RateLimiter\rate_limiter_hit;
use function Harbor\RateLimiter\rate_limiter_too_many_attempts;
use function Harbor\Response\abort;
use function Harbor\Response\response_header;
use function Harbor\Support\harbor_is_blank;

final class ThrottleMiddleware
{
    private ?\Closure $key_resolver;
    private readonly \Closure $failure_handler;

    private readonly int $max_attempts;
    private readonly int $decay_seconds;

    public function __construct(
        int $max_attempts = 60,
        int $decay_seconds = 60,
        ?callable $key_resolver = null,
        private readonly int|ResponseStatus $failure_status = ResponseStatus::TOO_MANY_REQUESTS,
        ?callable $failure_handler = null,
    ) {
        $this->max_attempts = max(1, $max_attempts);
        $this->decay_seconds = max(1, $decay_seconds);
        $this->key_resolver = is_callable($key_resolver) ? \Closure::fromCallable($key_resolver) : null;
        $this->failure_handler = is_callable($failure_handler)
            ? \Closure::fromCallable($failure_handler)
            : static fn (array $request, int|ResponseStatus $status, int $retry_after): never => abort($status);
    }

    public function __invoke(): callable
    {
        return function (array $request, callable $next): mixed {
            $rate_limit_key = $this->resolve_rate_limit_key($request);

            if (rate_limiter_too_many_attempts($rate_limit_key, $this->max_attempts)) {
                $retry_after_seconds = max(1, rate_limiter_available_in($rate_limit_key));
                response_header('Retry-After', (string) $retry_after_seconds);

                $failure_handler = $this->failure_handler;

                return $failure_handler($request, $this->failure_status, $retry_after_seconds);
            }

            rate_limiter_hit($rate_limit_key, $this->decay_seconds);

            return $next($request);
        };
    }

    private function resolve_rate_limit_key(array $request): string
    {
        if ($this->key_resolver instanceof \Closure) {
            $resolved_key = ($this->key_resolver)($request);
            if (is_string($resolved_key) && ! harbor_is_blank(trim($resolved_key))) {
                return trim($resolved_key);
            }
        }

        $request_ip = is_string($request['ip'] ?? null) ? trim($request['ip']) : '';
        $request_method = is_string($request['method'] ?? null) ? strtoupper(trim($request['method'])) : 'GET';
        $request_path = is_string($request['path'] ?? null) ? trim($request['path']) : '/';

        if (harbor_is_blank($request_ip)) {
            $request_ip = 'unknown';
        }

        if (harbor_is_blank($request_path)) {
            $request_path = '/';
        }

        return sprintf('%s|%s|%s', $request_method, $request_path, $request_ip);
    }
}
