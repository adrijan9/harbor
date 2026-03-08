<?php

declare(strict_types=1);

namespace Harbor\Tests\Bin;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2).'/bin/harbor-fixer';

require_once dirname(__DIR__, 2).'/src/Support/value.php';

use function Harbor\Support\harbor_is_blank;

/**
 * Class HarborFixerTest.
 */
final class HarborFixerTest extends TestCase
{
    private string $workspace_path = '';
    private string $original_working_directory = '';

    public function test_publish_fixer_config_creates_file_in_current_site_root_directory(): void
    {
        $this->prepare_workspace();

        $published_path = \harbor_publish_fixer_config();

        self::assertSame($this->workspace_path.'/.php-cs-fixer.dist.php', $published_path);
        self::assertFileExists($published_path);

        $expected_content = file_get_contents(dirname(__DIR__, 2).'/.php-cs-fixer.dist.php');
        $published_content = file_get_contents($published_path);

        self::assertIsString($expected_content);
        self::assertIsString($published_content);
        self::assertSame($expected_content, $published_content);
    }

    public function test_publish_fixer_config_overwrites_existing_file_without_prompt(): void
    {
        $this->prepare_workspace();
        file_put_contents($this->workspace_path.'/.php-cs-fixer.dist.php', '<?php return [];');

        $published_path = \harbor_publish_fixer_config();

        self::assertSame($this->workspace_path.'/.php-cs-fixer.dist.php', $published_path);

        $content = file_get_contents($published_path);
        self::assertIsString($content);
        self::assertStringContainsString('use PhpCsFixer\Config;', $content);
        self::assertStringContainsString("'@PhpCsFixer' => true", $content);
    }

    public function test_publish_fixer_config_throws_when_site_is_not_selected(): void
    {
        $this->prepare_workspace(with_site_selected: false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No selected site.');

        \harbor_publish_fixer_config();
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

    private function prepare_workspace(bool $with_site_selected = true): void
    {
        $working_directory = getcwd();
        $this->original_working_directory = false === $working_directory ? '' : $working_directory;
        $workspace_path = sys_get_temp_dir().'/harbor_fixer_'.bin2hex(random_bytes(8));

        if (! mkdir($workspace_path, 0o777, true) && ! is_dir($workspace_path)) {
            throw new \RuntimeException(sprintf('Failed to create test workspace "%s".', $workspace_path));
        }

        $resolved_workspace_path = realpath($workspace_path);
        if (false === $resolved_workspace_path) {
            throw new \RuntimeException(sprintf('Failed to resolve test workspace "%s".', $workspace_path));
        }

        $this->workspace_path = $resolved_workspace_path;
        chdir($this->workspace_path);

        if ($with_site_selected) {
            file_put_contents($this->workspace_path.'/.router', '# test');
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
