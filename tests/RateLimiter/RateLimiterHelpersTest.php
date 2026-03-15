<?php

declare(strict_types=1);

namespace Harbor\Tests\RateLimiter;

use Harbor\Helper;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

use function Harbor\Cache\cache_array_clear;
use function Harbor\RateLimiter\rate_limiter_attempts;
use function Harbor\RateLimiter\rate_limiter_available_in;
use function Harbor\RateLimiter\rate_limiter_clear;
use function Harbor\RateLimiter\rate_limiter_hit;
use function Harbor\RateLimiter\rate_limiter_remaining;
use function Harbor\RateLimiter\rate_limiter_too_many_attempts;

/**
 * Class RateLimiterHelpersTest.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class RateLimiterHelpersTest extends TestCase
{
    private array $original_env = [];

    #[BeforeClass]
    public static function load_helpers(): void
    {
        Helper::load_many('rate_limiter');
    }

    public function test_rate_limiter_hit_tracks_attempts_and_remaining(): void
    {
        $key = 'tests:rate_limiter:login';

        self::assertSame(1, rate_limiter_hit($key, 60));
        self::assertSame(2, rate_limiter_hit($key, 60));
        self::assertSame(2, rate_limiter_attempts($key));
        self::assertFalse(rate_limiter_too_many_attempts($key, 3));
        self::assertTrue(rate_limiter_too_many_attempts($key, 2));
        self::assertSame(0, rate_limiter_remaining($key, 2));

        $available_in = rate_limiter_available_in($key);

        self::assertGreaterThan(0, $available_in);
        self::assertLessThanOrEqual(60, $available_in);
    }

    public function test_rate_limiter_clear_resets_bucket_state(): void
    {
        $key = 'tests:rate_limiter:clear';

        rate_limiter_hit($key, 60);

        self::assertTrue(rate_limiter_clear($key));
        self::assertSame(0, rate_limiter_attempts($key));
        self::assertSame(0, rate_limiter_available_in($key));
        self::assertFalse(rate_limiter_too_many_attempts($key, 1));
        self::assertFalse(rate_limiter_clear($key));
    }

    public function test_rate_limiter_hit_throws_for_blank_key(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rate limiter key cannot be empty.');

        rate_limiter_hit('  ');
    }

    #[Before]
    protected function prepare_environment(): void
    {
        $this->original_env = is_array($_ENV) ? $_ENV : [];

        $_ENV = $this->original_env;
        $_ENV['cache'] = [
            'driver' => 'array',
        ];
        $GLOBALS['_ENV'] = $_ENV;

        cache_array_clear();
    }

    #[After]
    protected function cleanup_environment(): void
    {
        cache_array_clear();

        $_ENV = $this->original_env;
        $GLOBALS['_ENV'] = $_ENV;
    }
}
