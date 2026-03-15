<?php

declare(strict_types=1);

namespace Harbor\Tests\Middleware;

use Harbor\Helper;
use Harbor\Middleware\ApiAuthMiddleware;
use Harbor\Middleware\BasicAuthMiddleware;
use Harbor\Middleware\CorsMiddleware;
use Harbor\Middleware\CsrfMiddleware;
use Harbor\Middleware\ThrottleMiddleware;
use Harbor\Middleware\WebAuthMiddleware;
use Harbor\Response\ResponseStatus;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

use function Harbor\Cache\cache_array_clear;
use function Harbor\Auth\auth_token_issue;
use function Harbor\Auth\auth_web_login;
use function Harbor\Middleware\middleware;
use function Harbor\Pipeline\pipeline_get;

/**
 * Class FirstClassMiddlewaresTest.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class FirstClassMiddlewaresTest extends TestCase
{
    private array $original_server = [];
    private array $original_cookie = [];
    private array $original_post = [];
    private array $original_env = [];

    #[BeforeClass]
    public static function load_helpers(): void
    {
        Helper::load_many('cache');
    }

    public function test_api_auth_middleware_allows_verified_bearer_token_request_by_default(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/protected';
        Helper::load_many('auth', 'middleware');

        $access_token = auth_token_issue('42');
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer '.$access_token;

        middleware(new ApiAuthMiddleware());

        $result = pipeline_get();

        self::assertIsArray($result);
        self::assertSame('/protected', $result['path']);
        self::assertIsArray($result['headers'] ?? null);
    }

    public function test_api_auth_middleware_rejects_invalid_bearer_token_with_custom_failure_handler(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/protected';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer invalid-token';

        Helper::load_many('auth', 'middleware');

        $middleware_action = new ApiAuthMiddleware(
            failure_handler: static fn (array $request, int|ResponseStatus $status): array => [
                'blocked' => true,
                'status' => $status instanceof ResponseStatus ? $status->value : $status,
            ]
        );

        middleware($middleware_action);

        $result = pipeline_get();

        self::assertSame(
            [
                'blocked' => true,
                'status' => 401,
            ],
            $result
        );
    }

    public function test_web_auth_middleware_allows_authenticated_session_request_by_default(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/protected';
        Helper::load_many('auth', 'middleware');

        auth_web_login([
            'id' => 5,
            'name' => 'Ada',
        ]);

        middleware(new WebAuthMiddleware());

        $result = pipeline_get();

        self::assertIsArray($result);
        self::assertSame('/protected', $result['path']);
    }

    public function test_web_auth_middleware_rejects_guest_request_with_custom_failure_handler(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/protected';

        Helper::load_many('auth', 'middleware');

        $middleware_action = new WebAuthMiddleware(
            login_path: '/signin',
            failure_handler: static fn (array $request, int|ResponseStatus $status): array => [
                'blocked' => true,
                'status' => $status instanceof ResponseStatus ? $status->value : $status,
                'redirect' => '/signin',
            ]
        );

        middleware($middleware_action);

        $result = pipeline_get();

        self::assertSame(
            [
                'blocked' => true,
                'status' => 302,
                'redirect' => '/signin',
            ],
            $result
        );
    }

    public function test_csrf_middleware_allows_matching_tokens(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/forms';
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'csrf-token';
        $_COOKIE['XSRF-TOKEN'] = 'csrf-token';

        Helper::load_many('middleware');

        middleware(new CsrfMiddleware());

        $result = pipeline_get();

        self::assertIsArray($result);
        self::assertSame('/forms', $result['path']);
    }

    public function test_csrf_middleware_does_not_generate_token_for_safe_requests(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/forms';

        Helper::load_many('middleware');

        middleware(new CsrfMiddleware());

        $result = pipeline_get();

        self::assertIsArray($result);
        self::assertSame('/forms', $result['path']);
        self::assertArrayNotHasKey('csrf_token', $result);
        self::assertArrayNotHasKey('XSRF-TOKEN', $_COOKIE);
    }

    public function test_csrf_middleware_allows_matching_body_tokens(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/forms';
        $_COOKIE['XSRF-TOKEN'] = 'csrf-token';
        $_POST['_token'] = 'csrf-token';

        Helper::load_many('middleware');

        middleware(new CsrfMiddleware());

        $result = pipeline_get();

        self::assertIsArray($result);
        self::assertSame('/forms', $result['path']);
    }

    public function test_csrf_middleware_rejects_mismatched_tokens(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/forms';
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'invalid';
        $_COOKIE['XSRF-TOKEN'] = 'expected';

        Helper::load_many('middleware');

        $middleware_action = new CsrfMiddleware(
            failure_handler: static fn (array $request, int|ResponseStatus $status): array => [
                'blocked' => true,
                'status' => $status instanceof ResponseStatus ? $status->value : $status,
            ]
        );

        middleware($middleware_action);

        $result = pipeline_get();

        self::assertSame(
            [
                'blocked' => true,
                'status' => 403,
            ],
            $result
        );
    }

    public function test_basic_auth_middleware_allows_valid_basic_auth_header(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/private';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Basic '.base64_encode('demo:secret');

        Helper::load_many('middleware');

        middleware(new BasicAuthMiddleware());

        $result = pipeline_get();

        self::assertIsArray($result);
        self::assertSame('/api/private', $result['path']);
    }

    public function test_basic_auth_middleware_rejects_invalid_credentials(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/private';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Basic '.base64_encode('demo:wrong');

        Helper::load_many('middleware');

        $middleware_action = new BasicAuthMiddleware(
            credentials_resolver: static fn (string $username, string $password): bool => 'demo' === $username && 'secret' === $password,
            failure_handler: static fn (array $request, int|ResponseStatus $status): array => [
                'blocked' => true,
                'status' => $status instanceof ResponseStatus ? $status->value : $status,
            ]
        );

        middleware($middleware_action);

        $result = pipeline_get();

        self::assertSame(
            [
                'blocked' => true,
                'status' => 401,
            ],
            $result
        );
    }

    public function test_throttle_middleware_blocks_after_max_attempts(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/login';
        $_SERVER['REMOTE_ADDR'] = '198.51.100.20';

        Helper::load_many('middleware');

        $middleware_action = new ThrottleMiddleware(
            max_attempts: 2,
            decay_seconds: 60,
            key_resolver: static fn (array $request): string => 'tests:throttle',
            failure_handler: static fn (array $request, int|ResponseStatus $status, int $retry_after): array => [
                'blocked' => true,
                'status' => $status instanceof ResponseStatus ? $status->value : $status,
                'retry_after' => $retry_after,
            ]
        );

        middleware($middleware_action);
        $first_result = pipeline_get();

        middleware($middleware_action);
        $second_result = pipeline_get();

        middleware($middleware_action);
        $blocked_result = pipeline_get();

        self::assertIsArray($first_result);
        self::assertSame('/login', $first_result['path']);
        self::assertIsArray($second_result);
        self::assertSame('/login', $second_result['path']);
        self::assertTrue($blocked_result['blocked']);
        self::assertSame(429, $blocked_result['status']);
        self::assertGreaterThanOrEqual(1, $blocked_result['retry_after']);
    }

    public function test_cors_middleware_sets_headers_for_allowed_origin(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/users';
        $_SERVER['HTTP_ORIGIN'] = 'https://app.example.com';

        Helper::load_many('middleware');

        middleware(new CorsMiddleware(
            allowed_origins: ['https://app.example.com'],
            allowed_methods: ['GET', 'POST'],
            allowed_headers: ['Content-Type', 'Authorization']
        ));

        $result = pipeline_get();

        self::assertIsArray($result);
        self::assertSame('/api/users', $result['path']);
        self::assertSame('https://app.example.com', $result['headers']['origin']);
    }

    public function test_cors_middleware_rejects_disallowed_origin(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/users';
        $_SERVER['HTTP_ORIGIN'] = 'https://evil.example.com';

        Helper::load_many('middleware');

        $middleware_action = new CorsMiddleware(
            allowed_origins: ['https://app.example.com'],
            forbidden_handler: static fn (array $request, int|ResponseStatus $status): array => [
                'blocked' => true,
                'status' => $status instanceof ResponseStatus ? $status->value : $status,
            ]
        );

        middleware($middleware_action);

        $result = pipeline_get();

        self::assertSame(
            [
                'blocked' => true,
                'status' => 403,
            ],
            $result
        );
    }

    #[Before]
    protected function preserve_environment(): void
    {
        $this->original_server = $_SERVER;
        $this->original_cookie = $_COOKIE;
        $this->original_post = $_POST;
        $this->original_env = is_array($_ENV) ? $_ENV : [];

        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'HTTP_HOST' => 'example.test',
            'SERVER_PORT' => '80',
            'REMOTE_ADDR' => '127.0.0.1',
        ];
        $_COOKIE = [];
        $_POST = [];

        $this->set_auth_config();

        header_remove();
        cache_array_clear();
    }

    #[After]
    protected function restore_environment(): void
    {
        header_remove();
        cache_array_clear();

        $_SERVER = $this->original_server;
        $_COOKIE = $this->original_cookie;
        $_POST = $this->original_post;
        $_ENV = $this->original_env;
        $GLOBALS['_ENV'] = $_ENV;
    }

    private function set_auth_config(): void
    {
        $_ENV['auth'] = [
            'web' => [
                'session_key' => 'auth_web_user',
                'attempt_resolver' => null,
            ],
            'api' => [
                'secret' => 'test-auth-secret-should-be-at-least-32-bytes',
                'issuer' => 'harbor-tests',
                'audience' => 'harbor-tests-api',
                'ttl_seconds' => 3600,
                'leeway_seconds' => 0,
                'revoke_store_path' => sys_get_temp_dir().'/harbor_middleware_auth_revoked.json',
                'attempt_resolver' => null,
                'user_resolver' => null,
            ],
        ];
        $GLOBALS['_ENV'] = $_ENV;
    }
}
