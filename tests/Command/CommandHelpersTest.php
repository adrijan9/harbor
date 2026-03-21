<?php

declare(strict_types=1);

namespace Harbor\Tests\Command;

use Harbor\Helper;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\TestCase;

use function Harbor\Command\command_run;

/**
 * Class CommandHelpersTest.
 */
final class CommandHelpersTest extends TestCase
{
    private string $workspace_path = '';
    private string $site_path = '';
    private string $original_working_directory = '';

    #[BeforeClass]
    public static function load_command_helpers(): void
    {
        Helper::load_many('command');
    }

    public function test_command_run_executes_registered_command_and_forwards_arguments(): void
    {
        $this->prepare_workspace();

        file_put_contents(
            $this->site_path.'/.commands',
            <<<'COMMANDS'
                <command>
                    key: users:sync
                    entry: commands/users_sync.php
                    enabled: true
                </command>
                COMMANDS
        );

        mkdir($this->site_path.'/commands', 0o777, true);
        mkdir($this->site_path.'/storage', 0o777, true);

        file_put_contents(
            $this->site_path.'/commands/users_sync.php',
            <<<'PHP_SCRIPT'
                <?php

                declare(strict_types=1);

                $arguments = $argv ?? [];
                array_shift($arguments);

                file_put_contents(__DIR__.'/../storage/command-args.json', json_encode($arguments));
                PHP_SCRIPT
        );

        $exit_code = command_run('users:sync', ['--force', 'users'], $this->site_path);

        self::assertSame(0, $exit_code);
        self::assertFileExists($this->site_path.'/commands/commands.php');

        $captured_arguments_content = file_get_contents($this->site_path.'/storage/command-args.json');
        self::assertIsString($captured_arguments_content);

        $captured_arguments = json_decode($captured_arguments_content, true);
        self::assertIsArray($captured_arguments);
        self::assertSame(['--force', 'users'], $captured_arguments);
    }

    public function test_command_run_throws_when_working_directory_is_not_harbor_site(): void
    {
        $this->prepare_workspace();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No selected site.');

        command_run('users:sync', [], $this->workspace_path);
    }

    #[After]
    protected function cleanup_workspace(): void
    {
        if ('' !== $this->original_working_directory && is_dir($this->original_working_directory)) {
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

        $workspace_path = sys_get_temp_dir().'/harbor_command_helper_'.bin2hex(random_bytes(8));
        if (! mkdir($workspace_path, 0o777, true) && ! is_dir($workspace_path)) {
            throw new \RuntimeException(sprintf('Failed to create test workspace "%s".', $workspace_path));
        }

        $resolved_workspace_path = realpath($workspace_path);
        if (false === $resolved_workspace_path) {
            throw new \RuntimeException(sprintf('Failed to resolve test workspace "%s".', $workspace_path));
        }

        $this->workspace_path = $resolved_workspace_path;
        $this->site_path = $this->workspace_path.'/demo-site';

        mkdir($this->site_path, 0o777, true);
        file_put_contents($this->site_path.'/.router', "# test site\n");
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
