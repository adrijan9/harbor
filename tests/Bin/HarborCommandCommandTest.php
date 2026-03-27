<?php

declare(strict_types=1);

namespace Harbor\Tests\Bin;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2).'/bin/harbor-command';

require_once dirname(__DIR__, 2).'/src/Support/value.php';

use function Harbor\Support\harbor_is_blank;

final class HarborCommandCommandTest extends TestCase
{
    private string $workspace_path = '';
    private string $site_path = '';
    private string $original_working_directory = '';

    public function test_create_lazily_initializes_command_files_and_directory(): void
    {
        $this->prepare_workspace();
        chdir($this->site_path);

        self::assertDirectoryDoesNotExist($this->site_path.'/commands');
        self::assertFileDoesNotExist($this->site_path.'/.commands');

        $exit_code = $this->run_harbor_command(['harbor-command', 'create', 'my:command']);

        self::assertSame(0, $exit_code);
        self::assertDirectoryExists($this->site_path.'/commands');
        self::assertFileExists($this->site_path.'/.commands');
        self::assertFileExists($this->site_path.'/commands/commands.php');
        self::assertFileExists($this->site_path.'/commands/my_command.php');

        $entry_content = file_get_contents($this->site_path.'/commands/my_command.php');
        self::assertIsString($entry_content);
        self::assertStringContainsString('require __DIR__."/../../vendor/autoload.php";', $entry_content);
        self::assertStringContainsString('use Harbor\Helper;', $entry_content);
        self::assertStringContainsString('use function Harbor\Command\command_flag;', $entry_content);
        self::assertStringContainsString('use function Harbor\Command\command_flags_init;', $entry_content);
        self::assertStringContainsString('use function Harbor\Command\command_flags_print_usage;', $entry_content);
        self::assertStringContainsString('use function Harbor\Command\command_info;', $entry_content);
        self::assertStringContainsString('Helper::Command->load();', $entry_content);
        self::assertStringContainsString('command_flags_init(', $entry_content);
        self::assertStringContainsString('command_flags_print_usage(', $entry_content);
        self::assertStringContainsString('command_info(', $entry_content);

        $source_content = file_get_contents($this->site_path.'/.commands');
        self::assertIsString($source_content);
        self::assertStringContainsString('key: my:command', $source_content);
        self::assertStringNotContainsString('created_at', $source_content);
        self::assertStringNotContainsString('updated_at', $source_content);

        $registry = require $this->site_path.'/commands/commands.php';
        self::assertIsArray($registry);
        self::assertArrayHasKey('my:command', $registry);
    }

    public function test_create_returns_validation_error_when_key_already_exists(): void
    {
        $this->prepare_workspace();
        chdir($this->site_path);

        self::assertSame(0, $this->run_harbor_command(['harbor-command', 'create', 'my:command']));

        $exit_code = $this->run_harbor_command(['harbor-command', 'create', 'my:command']);

        self::assertSame(4, $exit_code);

        $source_content = file_get_contents($this->site_path.'/.commands');
        self::assertIsString($source_content);
        self::assertSame(1, substr_count($source_content, 'key: my:command'));
    }

    public function test_run_executes_command_and_forwards_arguments_after_separator(): void
    {
        $this->prepare_workspace();
        chdir($this->site_path);

        self::assertSame(0, $this->run_harbor_command(['harbor-command', 'create', 'sync:users']));

        if (! is_dir($this->site_path.'/storage')) {
            mkdir($this->site_path.'/storage', 0o777, true);
        }

        file_put_contents(
            $this->site_path.'/commands/sync_users.php',
            <<<'PHP_SCRIPT'
                <?php

                declare(strict_types=1);

                $arguments = $argv ?? [];
                array_shift($arguments);

                file_put_contents(__DIR__.'/../storage/command-args.json', json_encode($arguments));
                PHP_SCRIPT
        );

        $exit_code = $this->run_harbor_command([
            'harbor-command',
            'run',
            'sync:users',
            '--',
            '--force',
            'users',
        ]);

        self::assertSame(0, $exit_code);

        $captured_arguments_content = file_get_contents($this->site_path.'/storage/command-args.json');
        self::assertIsString($captured_arguments_content);

        $captured_arguments = json_decode($captured_arguments_content, true);
        self::assertIsArray($captured_arguments);
        self::assertSame(['--force', 'users'], $captured_arguments);
    }

    public function test_run_executes_generated_stub_with_runtime_helpers_loaded_by_default(): void
    {
        $this->prepare_workspace();
        chdir($this->site_path);

        self::assertSame(0, $this->run_harbor_command(['harbor-command', 'create', 'greet:user']));

        $exit_code = $this->run_harbor_command([
            'harbor-command',
            'run',
            'greet:user',
            '--',
            'Harbor',
            '--force',
        ]);

        self::assertSame(0, $exit_code);
    }

    public function test_run_executes_command_using_command_flags_helpers(): void
    {
        $this->prepare_workspace();
        chdir($this->site_path);

        self::assertSame(0, $this->run_harbor_command(['harbor-command', 'create', 'flags:demo']));

        if (! is_dir($this->site_path.'/storage')) {
            mkdir($this->site_path.'/storage', 0o777, true);
        }

        file_put_contents(
            $this->site_path.'/commands/flags_demo.php',
            <<<'PHP_SCRIPT'
                <?php

                declare(strict_types=1);

                require __DIR__."/../../vendor/autoload.php";

                use Harbor\Helper;
                use function Harbor\Command\command_flag;
                use function Harbor\Command\command_flags_init;

                Helper::Command->load();

                $command = command_flags_init('flags:demo', $argc ?? 0, $argv ?? []);
                $name = command_flag($command, '--name', 'User name', required: true);
                $dry_run = command_flag($command, '--dry-run', 'Dry run mode', default_value: false);
                $help = command_flag($command, '--help', 'Display command usage', default_value: false);

                file_put_contents(
                    __DIR__.'/../storage/command-flags.json',
                    json_encode([
                        'name' => $name,
                        'dry_run' => $dry_run,
                        'help' => $help,
                    ], JSON_THROW_ON_ERROR)
                );
                PHP_SCRIPT
        );

        $exit_code = $this->run_harbor_command([
            'harbor-command',
            'run',
            'flags:demo',
            '--',
            '--name=Harbor',
            '--dry-run',
        ]);

        self::assertSame(0, $exit_code);
        self::assertFileExists($this->site_path.'/storage/command-flags.json');

        $payload_content = file_get_contents($this->site_path.'/storage/command-flags.json');
        self::assertIsString($payload_content);

        $payload = json_decode($payload_content, true);
        self::assertIsArray($payload);
        self::assertSame('Harbor', $payload['name'] ?? null);
        self::assertTrue($payload['dry_run'] ?? false);
        self::assertFalse($payload['help'] ?? true);
    }

    public function test_run_returns_missing_key_exit_code_when_command_does_not_exist(): void
    {
        $this->prepare_workspace();
        chdir($this->site_path);

        $exit_code = $this->run_harbor_command(['harbor-command', 'run', 'missing:command']);

        self::assertSame(3, $exit_code);
    }

    public function test_compile_builds_registry_from_existing_source_file(): void
    {
        $this->prepare_workspace();
        chdir($this->site_path);

        file_put_contents(
            $this->site_path.'/.commands',
            <<<'COMMANDS'
                <command>
                    key: build:assets
                    entry: commands/build_assets.php
                    enabled: true
                </command>
                COMMANDS
        );

        if (! is_dir($this->site_path.'/commands')) {
            mkdir($this->site_path.'/commands', 0o777, true);
        }

        file_put_contents(
            $this->site_path.'/commands/build_assets.php',
            <<<'PHP_SCRIPT'
                <?php

                declare(strict_types=1);
                PHP_SCRIPT
        );

        $exit_code = $this->run_harbor_command(['harbor-command', 'compile']);

        self::assertSame(0, $exit_code);
        self::assertFileExists($this->site_path.'/commands/commands.php');

        $registry = require $this->site_path.'/commands/commands.php';
        self::assertIsArray($registry);
        self::assertArrayHasKey('build:assets', $registry);
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

        $workspace_path = sys_get_temp_dir().'/harbor_command_'.bin2hex(random_bytes(8));
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

        $this->create_workspace_autoload_bridge();
    }

    /**
     * @param array<int, string> $arguments
     */
    private function run_harbor_command(array $arguments): int
    {
        ob_start();

        try {
            return \harbor_command_manager_run($arguments);
        } finally {
            ob_end_clean();
        }
    }

    private function create_workspace_autoload_bridge(): void
    {
        $framework_autoload_path = realpath(dirname(__DIR__, 2).'/vendor/autoload.php');
        if (! is_string($framework_autoload_path) || harbor_is_blank($framework_autoload_path)) {
            throw new \RuntimeException('Failed to resolve framework autoload path for command test workspace.');
        }

        $workspace_vendor_path = $this->workspace_path.'/vendor';
        if (! is_dir($workspace_vendor_path)) {
            mkdir($workspace_vendor_path, 0o777, true);
        }

        $autoload_bridge = <<<'PHP'
            <?php

            declare(strict_types=1);

            require_once %s;
            PHP;

        file_put_contents(
            $workspace_vendor_path.'/autoload.php',
            sprintf($autoload_bridge, var_export($framework_autoload_path, true)).PHP_EOL
        );
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
