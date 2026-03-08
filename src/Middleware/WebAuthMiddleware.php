<?php

declare(strict_types=1);

namespace Harbor\Middleware;

use Harbor\Response\ResponseStatus;

require_once __DIR__.'/../Auth/auth_web.php';

require_once __DIR__.'/../Response/response.php';

require_once __DIR__.'/../Support/value.php';

use function Harbor\Auth\auth_web_exists;
use function Harbor\Response\response_header;
use function Harbor\Response\response_status;
use function Harbor\Support\harbor_is_blank;

final class WebAuthMiddleware
{
    private ?\Closure $auth_resolver;
    private readonly \Closure $failure_handler;

    public function __construct(
        ?callable $auth_resolver = null,
        private readonly string $login_path = '/login',
        private readonly int|ResponseStatus $failure_status = ResponseStatus::FOUND,
        ?callable $failure_handler = null,
    ) {
        $this->auth_resolver = is_callable($auth_resolver) ? \Closure::fromCallable($auth_resolver) : null;
        $this->failure_handler = is_callable($failure_handler)
            ? \Closure::fromCallable($failure_handler)
            : function (array $request, int|ResponseStatus $status): never {
                response_status($status);
                response_header('Location', $this->normalize_login_path($this->login_path));

                exit;
            };
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

        return auth_web_exists();
    }

    private function normalize_login_path(string $login_path): string
    {
        $normalized_login_path = trim($login_path);

        if (harbor_is_blank($normalized_login_path)) {
            return '/login';
        }

        $lower_login_path = strtolower($normalized_login_path);
        if (str_starts_with($lower_login_path, 'http://') || str_starts_with($lower_login_path, 'https://')) {
            return $normalized_login_path;
        }

        if (str_starts_with($normalized_login_path, '/')) {
            return $normalized_login_path;
        }

        return '/'.$normalized_login_path;
    }
}
