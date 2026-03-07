<?php

declare(strict_types=1);

namespace Harbor\Tests\Pipeline;

use Harbor\HelperLoader;
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
        HelperLoader::load('pipeline');
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
}
