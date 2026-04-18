<?php

declare(strict_types=1);

namespace Harbor\Tests\Bin;

use Harbor\Environment;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2).'/bin/commands/harbor-init/harbor_init.php';

require_once dirname(__DIR__, 2).'/src/Support/value.php';

use function Harbor\Support\harbor_is_blank;

/**
 * Class HarborInitTest.
 */
final class HarborInitTest extends TestCase
{
    private string $workspace_path = '';
    private string $original_working_directory = '';

    public function test_init_generates_global_config_and_public_entrypoint_structure(): void
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
        $template_path = dirname(__DIR__, 2).'/bin/stubs/site';
        self::assertFileExists($site_path.'/global.php');
        self::assertFileExists($site_path.'/.router');
        self::assertFileExists($site_path.'/phpunit.xml');
        self::assertFileExists($site_path.'/serve.sh');
        self::assertFileExists($site_path.'/public/.htaccess');
        self::assertFileExists($site_path.'/public/index.php');
        self::assertFileExists($site_path.'/public/robots.txt');
        self::assertFileExists($site_path.'/public/routes.php');
        self::assertFileExists($site_path.'/public/pages/index.php');
        self::assertFileExists($site_path.'/public/pages/error/404.php');
        self::assertFileExists($site_path.'/public/pages/error/405.php');
        self::assertFileExists($site_path.'/public/pages/error/500.php');
        self::assertFileExists($site_path.'/public/pages/error/exception.php');
        self::assertFileExists($site_path.'/lang/en.php');
        self::assertFileExists($site_path.'/lang/.keep');
        self::assertFileExists($site_path.'/config/.gitkeep');
        self::assertFileExists($site_path.'/src/.gitkeep');
        self::assertFileExists($site_path.'/database/.gitkeep');
        self::assertFileExists($site_path.'/tests/TestCase.php');
        self::assertFileExists($site_path.'/tests/Feature/HomePageTest.php');
        self::assertFileExists($site_path.'/tests/Unit/ExampleTest.php');
        self::assertDirectoryDoesNotExist($site_path.'/commands');
        self::assertFileDoesNotExist($site_path.'/.commands');

        $config = require $site_path.'/global.php';
        self::assertIsArray($config);
        self::assertSame('Harbor Site', $config['app_name'] ?? null);
        self::assertSame(Environment::LOCAL, $config['environment'] ?? null);
        self::assertSame('en', $config['lang'] ?? null);

        $index_content = file_get_contents($site_path.'/public/index.php');
        self::assertIsString($index_content);
        self::assertStringContainsString('new Router(', $index_content);
        self::assertStringContainsString("__DIR__.'/routes.php'", $index_content);
        self::assertStringContainsString("__DIR__.'/../global.php'", $index_content);

        $page_index_content = file_get_contents($site_path.'/public/pages/index.php');
        self::assertIsString($page_index_content);
        self::assertStringContainsString('Helper::Translations->load();', $page_index_content);
        self::assertStringContainsString('translation_init([', $page_index_content);
        self::assertStringContainsString("__DIR__.'/../../lang/en.php'", $page_index_content);

        $htaccess_content = file_get_contents($site_path.'/public/.htaccess');
        self::assertIsString($htaccess_content);
        self::assertStringContainsString('RewriteRule ^index\.php$ - [L]', $htaccess_content);
        self::assertStringContainsString('RewriteRule ^ index.php [L,QSA]', $htaccess_content);
        self::assertStringNotContainsString('RewriteCond %{REQUEST_FILENAME} !-f', $htaccess_content);
        self::assertStringNotContainsString('RewriteCond %{REQUEST_FILENAME} !-d', $htaccess_content);

        self::assertSame(
            file_get_contents($template_path.'/.router'),
            file_get_contents($site_path.'/.router'),
        );
        self::assertSame(
            file_get_contents($template_path.'/public/robots.txt'),
            file_get_contents($site_path.'/public/robots.txt'),
        );
        self::assertSame(
            file_get_contents($template_path.'/public/routes.php'),
            file_get_contents($site_path.'/public/routes.php'),
        );
        self::assertSame(
            file_get_contents($template_path.'/public/pages/index.php'),
            file_get_contents($site_path.'/public/pages/index.php'),
        );
        self::assertSame(
            file_get_contents($template_path.'/phpunit.xml'),
            file_get_contents($site_path.'/phpunit.xml'),
        );
        self::assertSame(
            file_get_contents($template_path.'/serve.sh'),
            file_get_contents($site_path.'/serve.sh'),
        );
        self::assertTrue(is_executable($site_path.'/serve.sh'));
        self::assertSame(
            file_get_contents($template_path.'/tests/TestCase.php'),
            file_get_contents($site_path.'/tests/TestCase.php'),
        );
        self::assertSame(
            file_get_contents($template_path.'/tests/Feature/HomePageTest.php'),
            file_get_contents($site_path.'/tests/Feature/HomePageTest.php'),
        );
        self::assertSame(
            file_get_contents($template_path.'/tests/Unit/ExampleTest.php'),
            file_get_contents($site_path.'/tests/Unit/ExampleTest.php'),
        );
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
