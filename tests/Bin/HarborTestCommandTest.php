<?php

declare(strict_types=1);

namespace Harbor\Tests\Bin;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2).'/bin/harbor-test';

require_once dirname(__DIR__, 2).'/src/Support/value.php';

use function Harbor\Support\harbor_is_blank;

final class HarborTestCommandTest extends TestCase
{
    private string $workspace_path = '';
    private string $site_path = '';
    private string $original_working_directory = '';

    public function test_run_executes_phpunit_with_site_configuration(): void
    {
        $this->prepare_workspace();
        chdir($this->site_path);

        self::assertSame(0, $this->run_harbor_test_command(['harbor-test']));

        $arguments = $this->phpunit_capture_arguments();

        self::assertContains('--configuration', $arguments);
        self::assertContains($this->site_path.'/phpunit.xml', $arguments);
    }

    public function test_run_accepts_site_path_argument_and_forwards_phpunit_options(): void
    {
        $this->prepare_workspace();
        chdir($this->workspace_path);

        self::assertSame(0, $this->run_harbor_test_command([
            'harbor-test',
            'demo-site',
            '--',
            '--filter',
            'HomePageTest',
        ]));

        $arguments = $this->phpunit_capture_arguments();

        self::assertContains('--filter', $arguments);
        self::assertContains('HomePageTest', $arguments);
    }

    public function test_run_fails_when_current_directory_is_not_a_site(): void
    {
        $this->prepare_workspace();
        chdir($this->workspace_path);

        self::assertSame(1, $this->run_harbor_test_command(['harbor-test']));
    }

    public function test_run_fails_when_phpunit_configuration_is_missing(): void
    {
        $this->prepare_workspace();
        unlink($this->site_path.'/phpunit.xml');
        chdir($this->site_path);

        self::assertSame(1, $this->run_harbor_test_command(['harbor-test']));
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

        $workspace_path = sys_get_temp_dir().'/harbor_test_'.bin2hex(random_bytes(8));
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
        mkdir($this->site_path.'/storage', 0o777, true);
        mkdir($this->site_path.'/vendor/bin', 0o777, true);
        file_put_contents($this->site_path.'/.router', "# site\n");
        file_put_contents($this->site_path.'/phpunit.xml', '<phpunit></phpunit>');

        file_put_contents(
            $this->site_path.'/vendor/bin/phpunit',
            <<<'PHPUNIT'
                <?php

                declare(strict_types=1);

                $arguments = $argv;
                array_shift($arguments);

                file_put_contents(
                    __DIR__.'/../../storage/phpunit-arguments.json',
                    json_encode($arguments)
                );
                PHPUNIT
        );

        chmod($this->site_path.'/vendor/bin/phpunit', 0o755);
    }

    /**
     * @return array<int, string>
     */
    private function phpunit_capture_arguments(): array
    {
        $capture_path = $this->site_path.'/storage/phpunit-arguments.json';
        self::assertFileExists($capture_path);

        $content = file_get_contents($capture_path);
        self::assertIsString($content);

        $decoded = json_decode($content, true);
        self::assertIsArray($decoded);

        $arguments = [];

        foreach ($decoded as $argument) {
            if (! is_string($argument)) {
                continue;
            }

            $arguments[] = $argument;
        }

        return $arguments;
    }

    /**
     * @param array<int, string> $arguments
     */
    private function run_harbor_test_command(array $arguments): int
    {
        ob_start();

        try {
            return \harbor_test_run($arguments);
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
