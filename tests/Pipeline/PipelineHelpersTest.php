<?php

declare(strict_types=1);

namespace Harbor\Tests\Pipeline;

use Harbor\Helper;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\TestCase;

use function Harbor\Pipeline\pipeline_clog;
use function Harbor\Pipeline\pipeline_get;
use function Harbor\Pipeline\pipeline_new;
use function Harbor\Pipeline\pipeline_send;
use function Harbor\Pipeline\pipeline_through;

/**
 * Class PipelineHelpersTest.
 */
final class PipelineHelpersTest extends TestCase
{
    #[BeforeClass]
    public static function load_pipeline_helpers(): void
    {
        Helper::load_many('pipeline');
    }

    public function test_pipeline_returns_default_structure(): void
    {
        $pipeline = pipeline_new();

        self::assertSame([], $pipeline['passable']);
        self::assertSame([], $pipeline['actions']);
        self::assertNull($pipeline['result']);
        self::assertFalse($pipeline['closed']);
    }

    public function test_pipeline_executes_actions_with_before_and_after_behavior(): void
    {
        $pipeline = pipeline_new();
        $calls = [];

        pipeline_send($pipeline, 'request');
        pipeline_through(
            $pipeline,
            static function (string $payload, callable $next) use (&$calls): string {
                $calls[] = 'first.before';

                $response = $next($payload.'-first');

                $calls[] = 'first.after';

                return $response.'-after-first';
            },
            static function (string $payload, callable $next) use (&$calls): string {
                $calls[] = 'second.before';

                $response = $next($payload.'-second');

                $calls[] = 'second.after';

                return $response.'-after-second';
            }
        );

        pipeline_clog($pipeline);

        self::assertSame(
            ['first.before', 'second.before', 'second.after', 'first.after'],
            $calls
        );
        self::assertSame('request-first-second-after-second-after-first', $pipeline['result']);
        self::assertSame($pipeline['result'], pipeline_get());
        self::assertTrue($pipeline['closed']);
    }

    public function test_pipeline_supports_multiple_passable_arguments(): void
    {
        $pipeline = pipeline_new();

        pipeline_send($pipeline, 'GET', '/users');
        pipeline_through(
            $pipeline,
            static fn (string $method, string $uri, callable $next): mixed => $next(strtolower($method), strtoupper($uri)),
            static fn (string $method, string $uri, callable $next): mixed => $next($method.' '.$uri)
        );

        pipeline_clog($pipeline);

        self::assertSame('get /USERS', $pipeline['result']);
        self::assertSame($pipeline['result'], pipeline_get());
    }

    public function test_pipeline_without_actions_returns_passable_value(): void
    {
        $pipeline = pipeline_new();

        pipeline_send($pipeline, 'done');
        pipeline_clog($pipeline);

        self::assertSame('done', $pipeline['result']);
        self::assertSame('done', pipeline_get());
    }

    public function test_pipeline_accepts_invokable_class_actions(): void
    {
        $pipeline = pipeline_new();
        $action = new class {
            public function __invoke(string $payload, callable $next): string
            {
                return $next($payload.'-class');
            }
        };

        pipeline_send($pipeline, 'request');
        pipeline_through($pipeline, $action);
        pipeline_clog($pipeline);

        self::assertSame('request-class', $pipeline['result']);
        self::assertSame('request-class', pipeline_get());
    }

    public function test_pipeline_accepts_invokable_class_factories_returning_callback(): void
    {
        $pipeline = pipeline_new();
        $factory_action = new class {
            public function __invoke(): callable
            {
                return static fn (string $payload, callable $next): string => $next($payload.'-factory');
            }
        };

        pipeline_send($pipeline, 'request');
        pipeline_through($pipeline, $factory_action);
        pipeline_clog($pipeline);

        self::assertSame('request-factory', $pipeline['result']);
        self::assertSame('request-factory', pipeline_get());
    }

    public function test_pipeline_throws_for_invalid_invokable_class_factory_result(): void
    {
        $pipeline = pipeline_new();
        $invalid_factory_action = new class {
            public function __invoke(): string
            {
                return 'invalid';
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invokable pipeline action factory must return a callable.');

        pipeline_through($pipeline, $invalid_factory_action);
    }
}
