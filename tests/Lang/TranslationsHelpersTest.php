<?php

declare(strict_types=1);

namespace Harbor\Tests\Lang;

use Harbor\HelperLoader;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;

use function Harbor\Lang\lang_set;
use function Harbor\Lang\t;
use function Harbor\Lang\translation_exists;
use function Harbor\Lang\translation_get;
use function Harbor\Lang\translation_init;
use function Harbor\Lang\translations_all;

final class TranslationsHelpersTest extends TestCase
{
    private array $original_env = [];
    private bool $had_translations = false;
    private mixed $original_translations = null;

    /** @var array<int, string> */
    private array $temporary_files = [];

    /** @var array<int, string> */
    private array $temporary_directories = [];

    #[Before]
    protected function prepare_environment(): void
    {
        $this->original_env = is_array($_ENV) ? $_ENV : [];
        $_ENV = $this->original_env;
        $GLOBALS['_ENV'] = $_ENV;

        $this->had_translations = array_key_exists('translations', $GLOBALS);
        $this->original_translations = $this->had_translations ? $GLOBALS['translations'] : null;

        HelperLoader::load('translation');
    }

    public function test_translation_init_loads_and_merges_files_per_locale(): void
    {
        $en_common = $this->create_temp_translation_file('en_common.php', [
            'home' => [
                'welcome' => 'Welcome :name',
                'bye' => 'Bye',
            ],
            'shared' => [
                'name' => 'Harbor',
            ],
        ]);
        $en_override = $this->create_temp_translation_file('en_override.php', [
            'home' => [
                'welcome' => 'Hello :name',
            ],
        ]);
        $es_common = $this->create_temp_translation_file('es_common.php', [
            'home' => [
                'welcome' => 'Hola :name',
            ],
        ]);

        translation_init([
            'en' => [$en_common, $en_override],
            'es' => [$es_common],
        ]);

        $all = translations_all();
        self::assertSame('Hello :name', $all['en']['home']['welcome']);
        self::assertSame('Bye', $all['en']['home']['bye']);
        self::assertSame('Hola :name', $all['es']['home']['welcome']);
    }

    public function test_translation_helpers_support_locale_resolution_and_replacements(): void
    {
        $en_file = $this->create_temp_translation_file('en_messages.php', [
            'messages' => [
                'welcome' => 'Hello :name, :Name, :NAME',
            ],
        ]);
        $es_file = $this->create_temp_translation_file('es_messages.php', [
            'messages' => [
                'welcome' => 'Hola :name',
            ],
        ]);

        translation_init([
            'en' => $en_file,
            'es' => $es_file,
        ]);

        lang_set('es');
        self::assertSame('Hola Ada', t('messages.welcome', ['name' => 'Ada']));
        self::assertSame('Hello ada, Ada, ADA', translation_get('messages.welcome', ['name' => 'ada'], 'en'));
    }

    public function test_translation_helpers_fallback_for_missing_keys(): void
    {
        $en_file = $this->create_temp_translation_file('en_fallback.php', [
            'messages' => [
                'ok' => 'OK',
            ],
        ]);

        translation_init([
            'en' => [$en_file],
        ]);

        self::assertSame('messages.missing', t('messages.missing'));
        self::assertSame('', t(''));
        self::assertSame('   ', t('   '));
    }

    public function test_translation_exists_checks_nested_keys(): void
    {
        $en_file = $this->create_temp_translation_file('en_exists.php', [
            'alerts' => [
                'success' => 'Done',
            ],
        ]);

        translation_init([
            'en' => [$en_file],
        ]);

        self::assertTrue(translation_exists('alerts.success', 'en'));
        self::assertFalse(translation_exists('alerts.error', 'en'));
    }

    public function test_translation_init_throws_for_invalid_locale_key(): void
    {
        $en_file = $this->create_temp_translation_file('en_invalid_locale.php', [
            'x' => 'y',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Translation locale key must be a non-empty string.');

        translation_init([
            '' => [$en_file],
        ]);
    }

    public function test_translation_init_throws_for_missing_file(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Translation file not found:');

        translation_init([
            'en' => ['/path/does/not/exist/en.php'],
        ]);
    }

    public function test_translation_init_throws_when_file_does_not_return_array(): void
    {
        $invalid_file = $this->create_temp_php_file('<?php return "invalid";');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must return an array');

        translation_init([
            'en' => [$invalid_file],
        ]);
    }

    #[After]
    protected function restore_environment_and_cleanup_files(): void
    {
        $_ENV = $this->original_env;
        $GLOBALS['_ENV'] = $_ENV;

        if ($this->had_translations) {
            $GLOBALS['translations'] = $this->original_translations;
        } else {
            unset($GLOBALS['translations']);
        }

        foreach ($this->temporary_files as $temporary_file) {
            if (is_file($temporary_file)) {
                unlink($temporary_file);
            }
        }

        foreach ($this->temporary_directories as $temporary_directory) {
            if (is_dir($temporary_directory)) {
                rmdir($temporary_directory);
            }
        }

        $this->temporary_files = [];
        $this->temporary_directories = [];
    }

    private function create_temp_translation_file(string $file_name, array $translations): string
    {
        $content = '<?php return '.var_export($translations, true).';';
        $directory = sys_get_temp_dir().'/harbor_lang_'.bin2hex(random_bytes(8));
        if (! mkdir($directory, 0o777, true) && ! is_dir($directory)) {
            throw new \RuntimeException(sprintf('Failed to create temporary directory "%s".', $directory));
        }

        $this->temporary_directories[] = $directory;
        $file_path = $directory.'/'.$file_name;
        file_put_contents($file_path, $content);
        $this->temporary_files[] = $file_path;

        return $file_path;
    }

    private function create_temp_php_file(string $content): string
    {
        $file_path = tempnam(sys_get_temp_dir(), 'harbor_lang_');
        if (false === $file_path) {
            throw new \RuntimeException('Failed to create temporary file.');
        }

        file_put_contents($file_path, $content);
        $this->temporary_files[] = $file_path;

        return $file_path;
    }
}
