<?php

declare(strict_types=1);

namespace Harbor\Tests\Middleware;

use Harbor\HelperLoader;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\TestCase;

use function Harbor\Middleware\middleware;
use function Harbor\Pipeline\pipeline_get;

/**
 * Class MiddlewareHelpersTest.
 */
final class MiddlewareHelpersTest extends TestCase
{
    private array $original_server = [];

    #[BeforeClass]
    public static function load_middleware_helpers(): void
    {
        HelperLoader::load('middleware');
    }

    public function test_middleware_uses_request_snapshot_as_default_payload(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/users?active=1',
            'HTTP_HOST' => 'example.test',
            'SERVER_PORT' => '80',
        ];

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

    #[Before]
    protected function preserve_server_state(): void
    {
        $this->original_server = $_SERVER;
    }

    #[After]
    protected function restore_server_state(): void
    {
        $_SERVER = $this->original_server;
    }
}
