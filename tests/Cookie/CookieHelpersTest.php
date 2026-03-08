<?php

declare(strict_types=1);

namespace Harbor\Tests\Cookie;

use Harbor\HelperLoader;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

use function Harbor\Cookie\cookie_forget;
use function Harbor\Cookie\cookie_get;
use function Harbor\Cookie\cookie_has;
use function Harbor\Cookie\cookie_set;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
/**
 * Class CookieHelpersTest.
 */
final class CookieHelpersTest extends TestCase
{
    private array $original_cookie = [];

    public function test_cookie_set_get_and_forget_flow_updates_cookie_state(): void
    {
        self::assertTrue(cookie_set('theme', 'light'));
        self::assertSame('light', cookie_get('theme'));
        self::assertTrue(cookie_has('theme'));

        self::assertTrue(cookie_forget('theme'));
        self::assertNull(cookie_get('theme'));
        self::assertFalse(cookie_has('theme'));
    }

    public function test_cookie_get_returns_full_cookie_map_when_key_is_missing(): void
    {
        $_COOKIE['locale'] = 'en';
        $_COOKIE['tz'] = 'UTC';

        self::assertSame(
            [
                'locale' => 'en',
                'tz' => 'UTC',
            ],
            cookie_get()
        );
    }

    public function test_cookie_get_returns_default_when_cookie_does_not_exist(): void
    {
        self::assertSame('default-value', cookie_get('missing_key', 'default-value'));
    }

    public function test_cookie_set_throws_for_blank_cookie_key(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cookie key cannot be empty.');

        cookie_set('   ', 'value');
    }

    #[Before]
    protected function bootstrap_cookie_helpers(): void
    {
        $this->original_cookie = is_array($_COOKIE) ? $_COOKIE : [];
        $_COOKIE = [];

        HelperLoader::load('cookie');
    }

    #[After]
    protected function restore_cookie_state(): void
    {
        $_COOKIE = $this->original_cookie;
    }
}
