<?php

declare(strict_types=1);

namespace Harbor\Tests\Bin;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2).'/bin/harbor_init.php';
require_once dirname(__DIR__, 2).'/src/Support/value.php';

use function Harbor\Support\harbor_is_blank;

final class HarborInitTest extends TestCase
{
    private string $workspace_path = '';
    private string $original_working_directory = '';

    public function test_init_generates_config_file_and_loads_it_in_index(): void
    {
        $working_directory = getcwd();
        $this->original_working_directory = false === $working_directory ? '' : $working_directory;
        $this->workspace_path = sys_get_temp_dir().'/harbor_init_'.bin2hex(random_bytes(8));

        if (! mkdir($this->workspace_path, 0o777, true) && ! is_dir($this->workspace_path)) {
            throw new \RuntimeException(sprintf('Failed to create test workspace "%s".', $this->workspace_path));
        }

        chdir($this->workspace_path);

        \harbor_run_init('demo-site');

        $site_path = $this->workspace_path.'/demo-site';
        self::assertFileExists($site_path.'/config.php');
        self::assertFileExists($site_path.'/index.php');

        $config = require $site_path.'/config.php';
        self::assertIsArray($config);
        self::assertSame('Harbor Site', $config['app_name'] ?? null);
        self::assertSame('local', $config['environment'] ?? null);

        $index_content = file_get_contents($site_path.'/index.php');
        self::assertIsString($index_content);
        self::assertStringContainsString("new Router(", $index_content);
        self::assertStringContainsString("__DIR__.'/routes.php'", $index_content);
        self::assertStringContainsString("__DIR__.'/config.php'", $index_content);
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
