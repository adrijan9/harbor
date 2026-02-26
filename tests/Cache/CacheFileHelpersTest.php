<?php

declare(strict_types=1);

namespace Harbor\Tests\Cache;

use Harbor\HelperLoader;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\TestCase;

use function Harbor\Cache\cache_file_all;
use function Harbor\Cache\cache_file_clear;
use function Harbor\Cache\cache_file_count;
use function Harbor\Cache\cache_file_delete;
use function Harbor\Cache\cache_file_get;
use function Harbor\Cache\cache_file_has;
use function Harbor\Cache\cache_file_reset_path;
use function Harbor\Cache\cache_file_set;
use function Harbor\Cache\cache_file_set_path;

final class CacheFileHelpersTest extends TestCase
{
    private string $workspace_path;
    private string $cache_root_path;
    private array $original_env = [];

    #[BeforeClass]
    public static function load_cache_file_helpers(): void
    {
        HelperLoader::load('cache_file');
    }

    public function test_cache_file_helpers_manage_cache_lifecycle(): void
    {
        self::assertTrue(cache_file_set('session:1', ['id' => 1, 'role' => 'admin']));
        self::assertTrue(cache_file_has('session:1'));
        self::assertSame(['id' => 1, 'role' => 'admin'], cache_file_get('session:1'));
        self::assertSame(
            ['session:1' => ['id' => 1, 'role' => 'admin']],
            cache_file_all()
        );
        self::assertSame(1, cache_file_count());

        self::assertTrue(cache_file_delete('session:1'));
        self::assertFalse(cache_file_has('session:1'));
        self::assertFalse(cache_file_delete('session:1'));
    }

    public function test_cache_file_get_returns_default_when_key_is_missing(): void
    {
        self::assertSame('fallback', cache_file_get('missing', 'fallback'));
    }

    public function test_cache_file_item_expires_after_ttl(): void
    {
        cache_file_set('expiring', 'value', 1);

        usleep(1_200_000);

        self::assertFalse(cache_file_has('expiring'));
        self::assertSame('fallback', cache_file_get('expiring', 'fallback'));
    }

    public function test_cache_file_clear_removes_cached_files_and_keeps_gitignore(): void
    {
        cache_file_set('a', 1);
        cache_file_set('b', 2);

        self::assertSame(2, cache_file_count());
        self::assertTrue(cache_file_clear());
        self::assertSame([], cache_file_all());
        self::assertTrue(is_file($this->cache_root_path.'/.gitignore'));
        self::assertSame(['.gitignore'], $this->root_entries($this->cache_root_path));
    }

    public function test_cache_file_set_throws_for_blank_key(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache key cannot be empty.');

        cache_file_set('   ', 'value');
    }

    public function test_cache_file_reset_path_uses_global_cache_file_path_after_runtime_override(): void
    {
        $runtime_path = $this->workspace_path.'/runtime-cache';

        cache_file_set_path($runtime_path);
        cache_file_set('runtime-only', 'value');

        self::assertDirectoryExists($runtime_path);
        self::assertTrue(is_file($runtime_path.'/.gitignore'));
        self::assertSame('value', cache_file_get('runtime-only'));

        cache_file_reset_path();
        cache_file_set('global-path', 'from-global');

        self::assertFalse(cache_file_has('runtime-only'));
        self::assertSame('from-global', cache_file_get('global-path'));
        self::assertDirectoryExists($this->cache_root_path);
        self::assertTrue(is_file($this->cache_root_path.'/.gitignore'));
    }

    #[Before]
    protected function create_workspace(): void
    {
        $this->original_env = is_array($_ENV) ? $_ENV : [];
        $this->workspace_path = sys_get_temp_dir().'/harbor_cache_file_'.bin2hex(random_bytes(8));
        $this->cache_root_path = $this->workspace_path.'/cache';

        if (! mkdir($this->workspace_path, 0o777, true) && ! is_dir($this->workspace_path)) {
            throw new \RuntimeException(sprintf('Failed to create test workspace "%s".', $this->workspace_path));
        }

        $_ENV['cache_file_path'] = $this->cache_root_path;
        $GLOBALS['_ENV'] = $_ENV;

        cache_file_reset_path();
        cache_file_clear();
    }

    #[After]
    protected function cleanup_workspace(): void
    {
        cache_file_clear();
        cache_file_reset_path();
        $_ENV = $this->original_env;
        $GLOBALS['_ENV'] = $_ENV;

        if (! is_dir($this->workspace_path)) {
            return;
        }

        $this->delete_directory_tree($this->workspace_path);
    }

    private function root_entries(string $directory_path): array
    {
        $entries = scandir($directory_path);
        if (false === $entries) {
            return [];
        }

        $visible_entries = array_values(array_filter(
            $entries,
            static fn (string $entry): bool => '.' !== $entry && '..' !== $entry
        ));

        sort($visible_entries);

        return $visible_entries;
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
