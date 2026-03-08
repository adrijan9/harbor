<?php

declare(strict_types=1);

namespace Harbor\Middleware;

use Harbor\Response\ResponseStatus;

require_once __DIR__.'/../Response/response.php';

require_once __DIR__.'/../Support/value.php';

use function Harbor\Response\abort;
use function Harbor\Response\response_header;
use function Harbor\Support\harbor_is_blank;

final class BasicAuthMiddleware
{
    private ?\Closure $credentials_resolver;
    private ?\Closure $failure_handler;

    public function __construct(
        ?callable $credentials_resolver = null,
        private readonly string $realm = 'Restricted Area',
        private readonly int|ResponseStatus $failure_status = ResponseStatus::UNAUTHORIZED,
        ?callable $failure_handler = null,
    ) {
        $this->credentials_resolver = is_callable($credentials_resolver) ? \Closure::fromCallable($credentials_resolver) : null;
        $this->failure_handler = is_callable($failure_handler) ? \Closure::fromCallable($failure_handler) : null;
    }

    public function __invoke(): callable
    {
        return function (array $request, callable $next): mixed {
            if (! $this->request_has_valid_credentials($request)) {
                if ($this->failure_handler instanceof \Closure) {
                    return ($this->failure_handler)($request, $this->failure_status);
                }

                return $this->default_failure();
            }

            return $next($request);
        };
    }

    private function request_has_valid_credentials(array $request): bool
    {
        [$username, $password] = $this->resolve_credentials($request);

        if ($this->credentials_resolver instanceof \Closure) {
            return (bool) ($this->credentials_resolver)($username, $password, $request);
        }

        return ! harbor_is_blank($username) && ! harbor_is_blank($password);
    }

    private function resolve_credentials(array $request): array
    {
        $headers = is_array($request['headers'] ?? null) ? $request['headers'] : [];
        $authorization = $headers['authorization'] ?? null;

        if (is_string($authorization)) {
            $normalized_authorization = trim($authorization);

            if (str_starts_with(strtolower($normalized_authorization), 'basic ')) {
                $encoded_credentials = trim(substr($normalized_authorization, 6));
                $decoded_credentials = base64_decode($encoded_credentials, true);

                if (is_string($decoded_credentials) && str_contains($decoded_credentials, ':')) {
                    [$username, $password] = explode(':', $decoded_credentials, 2);

                    return [
                        trim($username),
                        $password,
                    ];
                }
            }
        }

        $server = is_array($request['server'] ?? null) ? $request['server'] : [];
        $server_user = $server['PHP_AUTH_USER'] ?? '';
        $server_password = $server['PHP_AUTH_PW'] ?? '';

        return [
            is_string($server_user) ? trim($server_user) : '',
            is_string($server_password) ? $server_password : '',
        ];
    }

    private function default_failure(): never
    {
        response_header(
            'WWW-Authenticate',
            sprintf('Basic realm="%s", charset="UTF-8"', $this->normalize_realm($this->realm))
        );

        abort($this->failure_status);
    }

    private function normalize_realm(string $realm): string
    {
        $normalized_realm = trim($realm);

        if (harbor_is_blank($normalized_realm)) {
            return 'Restricted Area';
        }

        return $normalized_realm;
    }
}
