<?php

declare(strict_types=1);

namespace Harbor\Middleware;

use Harbor\Response\ResponseStatus;

require_once __DIR__.'/../Response/response.php';

require_once __DIR__.'/../Support/value.php';

use function Harbor\Response\abort;
use function Harbor\Support\harbor_is_blank;

final class AuthMiddleware
{
    private ?\Closure $auth_resolver;
    private readonly \Closure $failure_handler;

    public function __construct(
        ?callable $auth_resolver = null,
        private readonly int|ResponseStatus $failure_status = ResponseStatus::UNAUTHORIZED,
        ?callable $failure_handler = null,
    ) {
        $this->auth_resolver = is_callable($auth_resolver) ? \Closure::fromCallable($auth_resolver) : null;
        $this->failure_handler = is_callable($failure_handler)
            ? \Closure::fromCallable($failure_handler)
            : static fn (array $request, int|ResponseStatus $status): never => abort($status);
    }

    public function __invoke(): callable
    {
        return function (array $request, callable $next): mixed {
            if (! $this->request_is_authenticated($request)) {
                $failure_handler = $this->failure_handler;

                return $failure_handler($request, $this->failure_status);
            }

            return $next($request);
        };
    }

    private function request_is_authenticated(array $request): bool
    {
        if ($this->auth_resolver instanceof \Closure) {
            return (bool) ($this->auth_resolver)($request);
        }

        return $this->default_auth_check($request);
    }

    private function default_auth_check(array $request): bool
    {
        $headers = is_array($request['headers'] ?? null) ? $request['headers'] : [];

        $authorization = $headers['authorization'] ?? null;
        if (is_string($authorization)) {
            $normalized_authorization = trim($authorization);
            if (! harbor_is_blank($normalized_authorization)) {
                if (! str_starts_with(strtolower($normalized_authorization), 'bearer ')) {
                    return true;
                }

                $token = trim(substr($normalized_authorization, 7));
                if (! harbor_is_blank($token)) {
                    return true;
                }
            }
        }

        $x_auth_token = $headers['x-auth-token'] ?? null;

        return is_string($x_auth_token) && ! harbor_is_blank(trim($x_auth_token));
    }
}
