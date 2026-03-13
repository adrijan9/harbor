<?php

declare(strict_types=1);

namespace Harbor\Tests\Bin;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2).'/bin/harbor-init';

final class HarborInitCommandTest extends TestCase
{
    private string $workspace_path = '';
    private string $original_working_directory = '';

    public function test_run_creates_site_scaffold_with_site_name_argument(): void
    {
        $this->prepare_workspace();
        chdir($this->workspace_path);

        $exit_code = $this->run_init_command(['harbor-init', 'demo-site']);

        self::assertSame(0, $exit_code);
        self::assertDirectoryExists($this->workspace_path.'/demo-site');
        self::assertFileExists($this->workspace_path.'/demo-site/.router');
        self::assertFileExists($this->workspace_path.'/demo-site/serve.sh');
        self::assertFileExists($this->workspace_path.'/demo-site/public/index.php');
        self::assertTrue(is_executable($this->workspace_path.'/demo-site/serve.sh'));
    }

    public function test_run_returns_zero_for_help_flag(): void
    {
        $this->prepare_workspace();
        chdir($this->workspace_path);

        $exit_code = $this->run_init_command(['harbor-init', '--help']);

        self::assertSame(0, $exit_code);
    }

    public function test_run_rejects_extra_arguments(): void
    {
        $this->prepare_workspace();
        chdir($this->workspace_path);

        $exit_code = $this->run_init_command(['harbor-init', 'demo-site', 'unexpected']);

        self::assertSame(1, $exit_code);
        self::assertDirectoryDoesNotExist($this->workspace_path.'/demo-site');
    }

    #[After]
    protected function cleanup_workspace(): void
    {
        if (! empty($this->original_working_directory) && is_dir($this->original_working_directory)) {
            chdir($this->original_working_directory);
        }

        if (empty($this->workspace_path) || ! is_dir($this->workspace_path)) {
            return;
        }

        $this->delete_directory_tree($this->workspace_path);
    }

    private function prepare_workspace(): void
    {
        $working_directory = getcwd();
        $this->original_working_directory = false === $working_directory ? '' : $working_directory;

        $workspace_path = sys_get_temp_dir().'/harbor_init_command_'.bin2hex(random_bytes(8));
        if (! mkdir($workspace_path, 0o777, true) && ! is_dir($workspace_path)) {
            throw new \RuntimeException(sprintf('Failed to create test workspace "%s".', $workspace_path));
        }

        $resolved_workspace_path = realpath($workspace_path);
        if (false === $resolved_workspace_path) {
            throw new \RuntimeException(sprintf('Failed to resolve test workspace "%s".', $workspace_path));
        }

        $this->workspace_path = $resolved_workspace_path;
    }

    /**
     * @param array<int, string> $arguments
     */
    private function run_init_command(array $arguments): int
    {
        ob_start();

        try {
            return \harbor_init_run($arguments);
        } finally {
            ob_end_clean();
        }
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
