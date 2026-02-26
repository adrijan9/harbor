<?php

declare(strict_types=1);

namespace Harbor\Tests\Cache;

use Harbor\Cache\CacheDriver;
use Harbor\HelperLoader;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\TestCase;

use function Harbor\Cache\cache_all;
use function Harbor\Cache\cache_array_clear;
use function Harbor\Cache\cache_clear;
use function Harbor\Cache\cache_count;
use function Harbor\Cache\cache_delete;
use function Harbor\Cache\cache_driver;
use function Harbor\Cache\cache_apc_available;
use function Harbor\Cache\cache_apc_clear;
use function Harbor\Cache\cache_file_clear;
use function Harbor\Cache\cache_file_reset_path;
use function Harbor\Cache\cache_get;
use function Harbor\Cache\cache_has;
use function Harbor\Cache\cache_is_apc;
use function Harbor\Cache\cache_is_array;
use function Harbor\Cache\cache_is_file;
use function Harbor\Cache\cache_set;

final class CacheHelpersTest extends TestCase
{
    private string $workspace_path;
    private string $cache_root_path;
    private array $original_env = [];

    #[BeforeClass]
    public static function load_cache_helpers(): void
    {
        HelperLoader::load('cache');
    }

    public function test_cache_helpers_use_array_driver_from_config(): void
    {
        $_ENV['cache'] = [
            'driver' => CacheDriver::ARRAY->value,
            'file_path' => $this->cache_root_path,
        ];
        $GLOBALS['_ENV'] = $_ENV;

        self::assertSame(CacheDriver::ARRAY->value, cache_driver());
        self::assertTrue(cache_is_array());
        self::assertFalse(cache_is_file());
        self::assertTrue(cache_set('user:1', ['id' => 1]));
        self::assertTrue(cache_has('user:1'));
        self::assertSame(['id' => 1], cache_get('user:1'));
        self::assertSame(['user:1' => ['id' => 1]], cache_all());
        self::assertSame(1, cache_count());
        self::assertTrue(cache_delete('user:1'));
        self::assertFalse(cache_has('user:1'));
    }

    public function test_cache_helpers_use_file_driver_from_config(): void
    {
        $_ENV['cache'] = [
            'driver' => CacheDriver::FILE->value,
            'file_path' => $this->cache_root_path,
        ];
        $GLOBALS['_ENV'] = $_ENV;

        self::assertSame(CacheDriver::FILE->value, cache_driver());
        self::assertFalse(cache_is_array());
        self::assertTrue(cache_is_file());
        self::assertTrue(cache_set('session:1', ['id' => 1]));
        self::assertTrue(cache_has('session:1'));
        self::assertSame(['id' => 1], cache_get('session:1'));
        self::assertSame(['session:1' => ['id' => 1]], cache_all());
        self::assertSame(1, cache_count());
        self::assertDirectoryExists($this->cache_root_path);
        self::assertTrue(is_file($this->cache_root_path.'/.gitignore'));
        self::assertTrue(cache_clear());
        self::assertSame([], cache_all());
    }

    public function test_cache_helpers_use_apc_driver_from_config(): void
    {
        $_ENV['cache'] = [
            'driver' => CacheDriver::APC->value,
            'file_path' => $this->cache_root_path,
        ];
        $GLOBALS['_ENV'] = $_ENV;

        self::assertSame(CacheDriver::APC->value, cache_driver());
        self::assertFalse(cache_is_array());
        self::assertFalse(cache_is_file());
        self::assertTrue(cache_is_apc());

        if (! cache_apc_available()) {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('APCu extension is not available or enabled.');

            cache_set('profile:1', ['id' => 1]);

            return;
        }

        self::assertTrue(cache_set('profile:1', ['id' => 1]));
        self::assertTrue(cache_has('profile:1'));
        self::assertSame(['id' => 1], cache_get('profile:1'));
        self::assertSame(['profile:1' => ['id' => 1]], cache_all());
        self::assertSame(1, cache_count());
        self::assertTrue(cache_delete('profile:1'));
    }

    public function test_cache_helpers_resolve_driver_dynamically_after_config_change(): void
    {
        $_ENV['cache'] = [
            'driver' => CacheDriver::ARRAY->value,
            'file_path' => $this->cache_root_path,
        ];
        $GLOBALS['_ENV'] = $_ENV;

        cache_set('driver-switch', 'from-array');

        $_ENV['cache']['driver'] = CacheDriver::FILE->value;
        $GLOBALS['_ENV'] = $_ENV;

        cache_set('driver-switch', 'from-file');
        self::assertSame(CacheDriver::FILE->value, cache_driver());
        self::assertTrue(cache_is_file());
        self::assertSame('from-file', cache_get('driver-switch'));

        $_ENV['cache']['driver'] = CacheDriver::ARRAY->value;
        $GLOBALS['_ENV'] = $_ENV;

        self::assertSame(CacheDriver::ARRAY->value, cache_driver());
        self::assertTrue(cache_is_array());
        self::assertSame('from-array', cache_get('driver-switch'));
    }

    public function test_cache_driver_falls_back_to_default_for_invalid_driver_values(): void
    {
        $_ENV['cache'] = [
            'driver' => 'redis',
            'file_path' => $this->cache_root_path,
        ];
        $GLOBALS['_ENV'] = $_ENV;

        self::assertSame(CacheDriver::ARRAY->value, cache_driver());
        self::assertTrue(cache_is_array());
        self::assertFalse(cache_is_file());
        self::assertSame(CacheDriver::FILE->value, cache_driver(CacheDriver::FILE->value));
        self::assertSame(CacheDriver::FILE->value, cache_driver(CacheDriver::FILE));

        $_ENV['cache']['driver'] = '   ';
        $GLOBALS['_ENV'] = $_ENV;

        self::assertSame(CacheDriver::ARRAY->value, cache_driver());
        self::assertTrue(cache_is_array());
    }

    public function test_cache_set_throws_for_blank_key(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache key cannot be empty.');

        cache_set('   ', 'value');
    }

    #[Before]
    protected function create_workspace(): void
    {
        $this->original_env = is_array($_ENV) ? $_ENV : [];
        $this->workspace_path = sys_get_temp_dir().'/harbor_cache_'.bin2hex(random_bytes(8));
        $this->cache_root_path = $this->workspace_path.'/cache';

        if (! mkdir($this->workspace_path, 0o777, true) && ! is_dir($this->workspace_path)) {
            throw new \RuntimeException(sprintf('Failed to create test workspace "%s".', $this->workspace_path));
        }

        $_ENV = $this->original_env;
        $_ENV['cache'] = [
            'driver' => CacheDriver::ARRAY->value,
            'file_path' => $this->cache_root_path,
        ];
        $GLOBALS['_ENV'] = $_ENV;

        cache_file_reset_path();
        cache_array_clear();
        cache_file_clear();

        if (cache_apc_available()) {
            cache_apc_clear();
        }
    }

    #[After]
    protected function cleanup_workspace(): void
    {
        cache_array_clear();
        cache_file_reset_path();
        cache_file_clear();

        if (cache_apc_available()) {
            cache_apc_clear();
        }

        $_ENV = $this->original_env;
        $GLOBALS['_ENV'] = $_ENV;

        if (! is_dir($this->workspace_path)) {
            return;
        }

        $this->delete_directory_tree($this->workspace_path);
    }

    private function delete_directory_tree(string $directory_path): void
    {
        $entries = scandir($directory_path);
        if (false === $entries) {
            return;
        }

        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }

            $entry_path = $directory_path.'/'.$entry;

            if (is_dir($entry_path)) {
                $this->delete_directory_tree($entry_path);

                continue;
            }

            unlink($entry_path);
        }

        rmdir($directory_path);
    }
}
