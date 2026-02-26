<?php

declare(strict_types=1);

namespace Harbor\Tests\Bin;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2).'/bin/harbor-config';
require_once dirname(__DIR__, 2).'/src/Support/value.php';

use function Harbor\Support\harbor_is_blank;

final class HarborConfigTest extends TestCase
{
    private string $workspace_path = '';
    private string $original_working_directory = '';

    public function test_publish_cache_config_creates_file_in_current_site_config_directory(): void
    {
        $this->prepare_workspace();

        $published_path = \harbor_publish_config('cache');

        self::assertSame($this->workspace_path.'/config/cache.php', $published_path);
        self::assertFileExists($published_path);

        $content = file_get_contents($published_path);
        self::assertIsString($content);
        self::assertStringContainsString("use Harbor\\Cache\\CacheDriver;", $content);
        self::assertStringContainsString("'driver' => CacheDriver::FILE->value", $content);
        self::assertStringContainsString("'file_path' => __DIR__.'/../cache'", $content);
    }

    public function test_publish_cache_config_does_not_overwrite_existing_file_by_default(): void
    {
        $this->prepare_workspace();
        $config_directory_path = $this->workspace_path.'/config';
        mkdir($config_directory_path, 0o777, true);
        file_put_contents($config_directory_path.'/cache.php', '<?php return ["driver" => "array"];');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Configuration file already exists:');

        \harbor_publish_config('cache');
    }

    public function test_publish_cache_config_overwrites_existing_file_when_enabled(): void
    {
        $this->prepare_workspace();
        $config_directory_path = $this->workspace_path.'/config';
        mkdir($config_directory_path, 0o777, true);
        file_put_contents($config_directory_path.'/cache.php', '<?php return ["driver" => "array"];');

        $published_path = \harbor_publish_config('cache', true);
        self::assertSame($config_directory_path.'/cache.php', $published_path);

        $content = file_get_contents($published_path);
        self::assertIsString($content);
        self::assertStringContainsString("CacheDriver::FILE->value", $content);
    }

    public function test_publish_config_throws_for_unknown_configuration_key(): void
    {
        $this->prepare_workspace();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration "unknown" is not publishable.');

        \harbor_publish_config('unknown');
    }

    #[After]
    protected function cleanup_workspace(): void
    {
        if ('' !== $this->original_working_directory && is_dir($this->original_working_directory)) {
            chdir($this->original_working_directory);
        }

        if (harbor_is_blank($this->workspace_path) || ! is_dir($this->workspace_path)) {
            return;
        }

        $this->delete_directory_tree($this->workspace_path);
    }

    private function prepare_workspace(): void
    {
        $working_directory = getcwd();
        $this->original_working_directory = false === $working_directory ? '' : $working_directory;
        $workspace_path = sys_get_temp_dir().'/harbor_config_'.bin2hex(random_bytes(8));

        if (! mkdir($workspace_path, 0o777, true) && ! is_dir($workspace_path)) {
            throw new \RuntimeException(sprintf('Failed to create test workspace "%s".', $workspace_path));
        }

        $resolved_workspace_path = realpath($workspace_path);
        if (false === $resolved_workspace_path) {
            throw new \RuntimeException(sprintf('Failed to resolve test workspace "%s".', $workspace_path));
        }

        $this->workspace_path = $resolved_workspace_path;
        chdir($this->workspace_path);
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
