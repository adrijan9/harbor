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

    public function test_command_run_uses_runtime_helpers_when_entry_loads_command_helper(): void
    {
        $this->prepare_workspace();

        file_put_contents(
            $this->site_path.'/.commands',
            <<<'COMMANDS'
                <command>
                    key: users:inspect
                    entry: commands/users_inspect.php
                    enabled: true
                </command>
                COMMANDS
        );

        mkdir($this->site_path.'/commands', 0o777, true);
        mkdir($this->site_path.'/storage', 0o777, true);

        file_put_contents(
            $this->site_path.'/commands/users_inspect.php',
            <<<'PHP_SCRIPT'
                <?php

                declare(strict_types=1);

                use Harbor\Helper;
                use function Harbor\Command\command_arg_int;
                use function Harbor\Command\command_arg_string;
                use function Harbor\Command\command_arguments;
                use function Harbor\Command\command_debug;
                use function Harbor\Command\command_debug_enabled;
                use function Harbor\Command\command_error;
                use function Harbor\Command\command_flag;
                use function Harbor\Command\command_init;
                use function Harbor\Command\command_info;
                use function Harbor\Command\command_raw_arguments;

                require __DIR__."/../../vendor/autoload.php";

                Helper::Command->load();

                $command = command_init('users:inspect', $argc ?? 0, $argv ?? []);

                $payload = [
                    'has_command_info' => function_exists('Harbor\Command\command_info'),
                    'has_command_error' => function_exists('Harbor\Command\command_error'),
                    'has_command_debug' => function_exists('Harbor\Command\command_debug'),
                    'has_command_arg_string' => function_exists('Harbor\Command\command_arg_string'),
                    'has_command_arg_int' => function_exists('Harbor\Command\command_arg_int'),
                    'has_command_init' => function_exists('Harbor\Command\command_init'),
                    'has_command_flag' => function_exists('Harbor\Command\command_flag'),
                    'has_command_flag_string' => function_exists('Harbor\Command\command_flag_string'),
                    'has_command_flag_int' => function_exists('Harbor\Command\command_flag_int'),
                    'has_command_flag_float' => function_exists('Harbor\Command\command_flag_float'),
                    'has_command_flag_bool' => function_exists('Harbor\Command\command_flag_bool'),
                    'has_command_flag_array' => function_exists('Harbor\Command\command_flag_array'),
                    'has_command_flags_print_usage' => function_exists('Harbor\Command\command_flags_print_usage'),
                    'has_command_option_string' => function_exists('Harbor\Command\command_option_string'),
                    'has_command_run' => function_exists('Harbor\Command\command_run'),
                    'raw_arguments' => command_raw_arguments(),
                    'arguments' => command_arguments(),
                    'first_argument' => command_arg_string(0),
                    'second_argument_defaulted' => command_arg_string(1, 'fallback'),
                    'retry_count' => command_arg_int(1, 7),
                    'flag_name' => command_flag($command, '--name', 'Name value'),
                    'flag_force' => command_flag($command, '--force', 'Force mode', default_value: false),
                    'flag_limit' => command_flag($command, '--limit', 'Limit value', default_value: '0'),
                    'flag_verbose' => command_flag($command, '-v', 'Verbose mode', default_value: false),
                    'debug_enabled' => command_debug_enabled(),
                ];

                command_info('runtime helper info');
                command_error('runtime helper error');
                command_debug('runtime helper debug');

                file_put_contents(__DIR__.'/../storage/command-runtime.json', json_encode($payload, JSON_THROW_ON_ERROR));
                PHP_SCRIPT
        );

        $exit_code = command_run(
            'users:inspect',
            ['alpha', '--name=Harbor', '--force', '--limit', '10', '-v'],
            $this->site_path,
            true
        );

        self::assertSame(0, $exit_code);
        self::assertFileExists($this->site_path.'/storage/command-runtime.json');

        $runtime_content = file_get_contents($this->site_path.'/storage/command-runtime.json');
        self::assertIsString($runtime_content);

        $runtime_payload = json_decode($runtime_content, true);
        self::assertIsArray($runtime_payload);

        self::assertTrue($runtime_payload['has_command_info'] ?? false);
        self::assertTrue($runtime_payload['has_command_error'] ?? false);
        self::assertTrue($runtime_payload['has_command_debug'] ?? false);
        self::assertTrue($runtime_payload['has_command_arg_string'] ?? false);
        self::assertTrue($runtime_payload['has_command_arg_int'] ?? false);
        self::assertTrue($runtime_payload['has_command_init'] ?? false);
        self::assertTrue($runtime_payload['has_command_flag'] ?? false);
        self::assertTrue($runtime_payload['has_command_flag_string'] ?? false);
        self::assertTrue($runtime_payload['has_command_flag_int'] ?? false);
        self::assertTrue($runtime_payload['has_command_flag_float'] ?? false);
        self::assertTrue($runtime_payload['has_command_flag_bool'] ?? false);
        self::assertTrue($runtime_payload['has_command_flag_array'] ?? false);
        self::assertTrue($runtime_payload['has_command_flags_print_usage'] ?? false);
        self::assertFalse($runtime_payload['has_command_option_string'] ?? true);
        self::assertTrue($runtime_payload['has_command_run'] ?? false);

        self::assertSame(['alpha', '--name=Harbor', '--force', '--limit', '10', '-v'], $runtime_payload['raw_arguments'] ?? null);
        self::assertSame(['alpha'], $runtime_payload['arguments'] ?? null);
        self::assertSame('alpha', $runtime_payload['first_argument'] ?? null);
        self::assertSame('fallback', $runtime_payload['second_argument_defaulted'] ?? null);
        self::assertSame(7, $runtime_payload['retry_count'] ?? null);
        self::assertSame('Harbor', $runtime_payload['flag_name'] ?? null);
        self::assertTrue($runtime_payload['flag_force'] ?? false);
        self::assertSame('10', $runtime_payload['flag_limit'] ?? null);
        self::assertTrue($runtime_payload['flag_verbose'] ?? false);
        self::assertTrue($runtime_payload['debug_enabled'] ?? false);
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

        $this->create_workspace_autoload_bridge();
    }

    private function create_workspace_autoload_bridge(): void
    {
        $framework_autoload_path = realpath(dirname(__DIR__, 2).'/vendor/autoload.php');
        if (! is_string($framework_autoload_path) || '' === trim($framework_autoload_path)) {
            throw new \RuntimeException('Failed to resolve framework autoload path for command helper test workspace.');
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
