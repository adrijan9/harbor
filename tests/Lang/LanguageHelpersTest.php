<?php

declare(strict_types=1);

namespace Harbor\Tests\Lang;

use Harbor\HelperLoader;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;

use function Harbor\Lang\lang_get;
use function Harbor\Lang\lang_is;
use function Harbor\Lang\lang_set;

final class LanguageHelpersTest extends TestCase
{
    private array $original_env = [];

    #[Before]
    protected function prepare_environment(): void
    {
        $this->original_env = is_array($_ENV) ? $_ENV : [];
        $_ENV = $this->original_env;
        $GLOBALS['_ENV'] = $_ENV;

        HelperLoader::load('lang');
    }

    public function test_lang_get_reads_lang_from_environment(): void
    {
        $_ENV['lang'] = 'es';
        $GLOBALS['_ENV'] = $_ENV;

        self::assertSame('es', lang_get());
    }

    public function test_lang_get_uses_default_when_lang_is_missing_or_blank(): void
    {
        unset($_ENV['lang']);
        $GLOBALS['_ENV'] = $_ENV;
        self::assertSame('en', lang_get('en'));

        $_ENV['lang'] = '   ';
        $GLOBALS['_ENV'] = $_ENV;
        self::assertSame('en', lang_get('en'));
    }

    public function test_lang_set_updates_environment(): void
    {
        lang_set('mk');

        self::assertSame('mk', $_ENV['lang'] ?? null);
        self::assertSame('mk', $GLOBALS['_ENV']['lang'] ?? null);
    }

    public function test_lang_set_throws_for_empty_locale(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Language locale cannot be empty.');

        lang_set(' ');
    }

    public function test_lang_is_checks_current_locale(): void
    {
        lang_set('en');

        self::assertTrue(lang_is('en'));
        self::assertFalse(lang_is('es'));
    }

    #[After]
    protected function restore_environment(): void
    {
        $_ENV = $this->original_env;
        $GLOBALS['_ENV'] = $_ENV;
    }
}
