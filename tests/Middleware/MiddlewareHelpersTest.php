<?php

declare(strict_types=1);

namespace Harbor\Tests\Middleware;

use Harbor\Helper;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

use function Harbor\Middleware\csrf_field;
use function Harbor\Middleware\csrf_token;
use function Harbor\Middleware\middleware;
use function Harbor\Pipeline\pipeline_get;

/**
 * Class MiddlewareHelpersTest.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class MiddlewareHelpersTest extends TestCase
{
    private array $original_server = [];
    private array $original_cookie = [];

    public function test_middleware_uses_request_snapshot_as_default_payload(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/users?active=1',
            'HTTP_HOST' => 'example.test',
            'SERVER_PORT' => '80',
        ];

        Helper::load_many('middleware');

        middleware(
            static function (array $request, callable $next): mixed {
                $request['middleware'][] = 'first';

                return $next($request);
            },
            static function (array $request, callable $next): mixed {
                $request['middleware'][] = 'second';

                return $next($request);
            }
        );

        $result = pipeline_get();

        self::assertIsArray($result);
        self::assertSame('POST', $result['method']);
        self::assertSame('/users', $result['path']);
        self::assertSame(['first', 'second'], $result['middleware']);
    }

    public function test_middleware_accepts_invokable_class_factory_actions(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/factory',
            'HTTP_HOST' => 'example.test',
            'SERVER_PORT' => '80',
        ];

        Helper::load_many('middleware');

        $factory_action = new class {
            public function __invoke(): callable
            {
                return static function (array $request, callable $next): mixed {
                    $request['middleware'][] = 'factory';

                    return $next($request);
                };
            }
        };

        middleware($factory_action);

        $result = pipeline_get();

        self::assertIsArray($result);
        self::assertSame('/factory', $result['path']);
        self::assertSame(['factory'], $result['middleware']);
    }

    public function test_csrf_token_generates_and_reuses_cookie_token(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/forms',
            'HTTP_HOST' => 'example.test',
            'SERVER_PORT' => '80',
        ];
        $_COOKIE = [];

        Helper::load_many('middleware');

        $first_token = csrf_token();
        $second_token = csrf_token();

        self::assertIsString($first_token);
        self::assertNotSame('', trim($first_token));
        self::assertSame($first_token, $second_token);
        self::assertSame($first_token, $_COOKIE['XSRF-TOKEN'] ?? null);
    }

    public function test_csrf_field_returns_hidden_input_with_token(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/forms',
            'HTTP_HOST' => 'example.test',
            'SERVER_PORT' => '80',
        ];
        $_COOKIE = [];

        Helper::load_many('middleware');

        $field = csrf_field();
        $token = $_COOKIE['XSRF-TOKEN'] ?? null;

        self::assertIsString($token);
        self::assertNotSame('', trim((string) $token));
        self::assertStringContainsString('type="hidden"', $field);
        self::assertStringContainsString('name="_token"', $field);
        self::assertStringContainsString('value="'.$token.'"', $field);
    }

    #[Before]
    protected function preserve_server_state(): void
    {
        $this->original_server = $_SERVER;
        $this->original_cookie = $_COOKIE;
    }

    #[After]
    protected function restore_server_state(): void
    {
        $_SERVER = $this->original_server;
        $_COOKIE = $this->original_cookie;
    }
}
