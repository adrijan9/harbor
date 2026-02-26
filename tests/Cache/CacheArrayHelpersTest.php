<?php

declare(strict_types=1);

namespace Harbor\Tests\Cache;

use Harbor\HelperLoader;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\TestCase;

use function Harbor\Cache\cache_array_all;
use function Harbor\Cache\cache_array_clear;
use function Harbor\Cache\cache_array_count;
use function Harbor\Cache\cache_array_delete;
use function Harbor\Cache\cache_array_get;
use function Harbor\Cache\cache_array_has;
use function Harbor\Cache\cache_array_set;

final class CacheArrayHelpersTest extends TestCase
{
    #[BeforeClass]
    public static function load_cache_array_helpers(): void
    {
        HelperLoader::load('cache_array');
    }

    public function test_cache_array_helpers_manage_cache_lifecycle(): void
    {
        self::assertTrue(cache_array_set('user:1', ['id' => 1, 'name' => 'Ada']));
        self::assertTrue(cache_array_has('user:1'));
        self::assertSame(['id' => 1, 'name' => 'Ada'], cache_array_get('user:1'));
        self::assertSame(
            ['user:1' => ['id' => 1, 'name' => 'Ada']],
            cache_array_all()
        );
        self::assertSame(1, cache_array_count());

        self::assertTrue(cache_array_delete('user:1'));
        self::assertFalse(cache_array_has('user:1'));
        self::assertFalse(cache_array_delete('user:1'));
    }

    public function test_cache_array_get_returns_default_when_key_is_missing(): void
    {
        self::assertSame('fallback', cache_array_get('missing', 'fallback'));
    }

    public function test_cache_array_item_expires_after_ttl(): void
    {
        cache_array_set('expiring', 'value', 1);

        usleep(1_200_000);

        self::assertFalse(cache_array_has('expiring'));
        self::assertSame('fallback', cache_array_get('expiring', 'fallback'));
    }

    public function test_cache_array_clear_removes_all_values(): void
    {
        cache_array_set('a', 1);
        cache_array_set('b', 2);

        self::assertSame(2, cache_array_count());
        self::assertTrue(cache_array_clear());
        self::assertSame([], cache_array_all());
        self::assertSame(0, cache_array_count());
    }

    public function test_cache_array_set_throws_for_blank_key(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache key cannot be empty.');

        cache_array_set('   ', 'value');
    }

    #[After]
    protected function clear_cache_array_values(): void
    {
        cache_array_clear();
    }
}
