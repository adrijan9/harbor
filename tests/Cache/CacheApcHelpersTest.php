<?php

declare(strict_types=1);

namespace Harbor\Tests\Cache;

use Harbor\HelperLoader;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\TestCase;

use function Harbor\Cache\cache_apc_all;
use function Harbor\Cache\cache_apc_available;
use function Harbor\Cache\cache_apc_clear;
use function Harbor\Cache\cache_apc_count;
use function Harbor\Cache\cache_apc_delete;
use function Harbor\Cache\cache_apc_get;
use function Harbor\Cache\cache_apc_has;
use function Harbor\Cache\cache_apc_set;

final class CacheApcHelpersTest extends TestCase
{
    #[BeforeClass]
    public static function load_cache_apc_helpers(): void
    {
        HelperLoader::load('cache_apc');
    }

    public function test_cache_apc_helpers_manage_cache_lifecycle(): void
    {
        if (! cache_apc_available()) {
            $this->markTestSkipped('APCu is not available or enabled.');
        }

        self::assertTrue(cache_apc_set('session:1', ['id' => 1, 'role' => 'admin']));
        self::assertTrue(cache_apc_has('session:1'));
        self::assertSame(['id' => 1, 'role' => 'admin'], cache_apc_get('session:1'));
        self::assertSame(
            ['session:1' => ['id' => 1, 'role' => 'admin']],
            cache_apc_all()
        );
        self::assertSame(1, cache_apc_count());

        self::assertTrue(cache_apc_delete('session:1'));
        self::assertFalse(cache_apc_has('session:1'));
        self::assertFalse(cache_apc_delete('session:1'));
    }

    public function test_cache_apc_get_returns_default_when_key_is_missing(): void
    {
        if (! cache_apc_available()) {
            $this->markTestSkipped('APCu is not available or enabled.');
        }

        self::assertSame('fallback', cache_apc_get('missing', 'fallback'));
    }

    public function test_cache_apc_item_expires_after_ttl(): void
    {
        if (! cache_apc_available()) {
            $this->markTestSkipped('APCu is not available or enabled.');
        }

        cache_apc_set('expiring', 'value', 1);

        usleep(1_200_000);

        self::assertFalse(cache_apc_has('expiring'));
        self::assertSame('fallback', cache_apc_get('expiring', 'fallback'));
    }

    public function test_cache_apc_clear_removes_all_values(): void
    {
        if (! cache_apc_available()) {
            $this->markTestSkipped('APCu is not available or enabled.');
        }

        cache_apc_set('a', 1);
        cache_apc_set('b', 2);

        self::assertSame(2, cache_apc_count());
        self::assertTrue(cache_apc_clear());
        self::assertSame([], cache_apc_all());
        self::assertSame(0, cache_apc_count());
    }

    public function test_cache_apc_set_throws_for_blank_key(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache key cannot be empty.');

        cache_apc_set('   ', 'value');
    }

    public function test_cache_apc_set_throws_when_apcu_is_unavailable(): void
    {
        if (cache_apc_available()) {
            $this->markTestSkipped('APCu is available; unavailable-path test skipped.');
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('APCu extension is not available or enabled.');

        cache_apc_set('apc-check', 'value');
    }

    #[After]
    protected function clear_cache_apc_values(): void
    {
        if (! cache_apc_available()) {
            return;
        }

        cache_apc_clear();
    }
}
