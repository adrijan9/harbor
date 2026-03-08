<?php

declare(strict_types=1);

namespace Harbor\Middleware;

use Harbor\Response\ResponseStatus;

require_once __DIR__.'/../Auth/auth_api.php';

require_once __DIR__.'/../Response/response.php';

use function Harbor\Auth\auth_api_exists;
use function Harbor\Response\response_json;

final class ApiAuthMiddleware
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
            : static function (array $request, int|ResponseStatus $status): never {
                $resolved_status = $status instanceof ResponseStatus ? $status->value : $status;

                response_json([
                    'message' => ResponseStatus::message_for($status, 'Unauthorized'),
                    'status' => $resolved_status,
                ], $status);

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

        return auth_api_exists($request);
    }
}
