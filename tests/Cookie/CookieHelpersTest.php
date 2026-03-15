<?php

declare(strict_types=1);

namespace Harbor\Tests\Cookie;

use Harbor\Helper;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

use function Harbor\Cookie\cookie_forget;
use function Harbor\Cookie\cookie_get;
use function Harbor\Cookie\cookie_get_encrypted;
use function Harbor\Cookie\cookie_get_signed;
use function Harbor\Cookie\cookie_has;
use function Harbor\Cookie\cookie_set;
use function Harbor\Cookie\cookie_set_encrypted;
use function Harbor\Cookie\cookie_set_signed;

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

    public function test_cookie_set_signed_and_cookie_get_signed_round_trip(): void
    {
        $signing_key = 'test-signing-key';

        self::assertTrue(cookie_set_signed('remember_token', 'abc123', $signing_key, 3600));
        self::assertArrayHasKey('remember_token', $_COOKIE);
        self::assertNotSame('abc123', $_COOKIE['remember_token']);

        self::assertSame('abc123', cookie_get_signed('remember_token', $signing_key));
        self::assertSame('fallback', cookie_get_signed('remember_token', 'wrong-key', 'fallback'));
    }

    public function test_cookie_set_encrypted_and_cookie_get_encrypted_round_trip(): void
    {
        if (! function_exists('openssl_encrypt') || ! function_exists('openssl_decrypt')) {
            self::markTestSkipped('OpenSSL extension is required for encrypted cookie tests.');
        }

        $encryption_key = 'test-encryption-key';

        self::assertTrue(cookie_set_encrypted('api_token', 'secret-value', $encryption_key, 3600));
        self::assertArrayHasKey('api_token', $_COOKIE);
        self::assertNotSame('secret-value', $_COOKIE['api_token']);

        self::assertSame('secret-value', cookie_get_encrypted('api_token', $encryption_key));
        self::assertSame('fallback', cookie_get_encrypted('api_token', 'wrong-key', 'fallback'));
    }

    public function test_cookie_get_returns_default_when_secure_cookie_is_tampered(): void
    {
        $signing_key = 'test-signing-key';
        self::assertTrue(cookie_set_signed('csrf_token', 'original', $signing_key));

        $_COOKIE['csrf_token'] .= 'tampered';

        self::assertSame(
            'fallback',
            cookie_get('csrf_token', 'fallback', [
                'signed' => true,
                'signing_key' => $signing_key,
            ])
        );
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

        Helper::load_many('cookie');
    }

    #[After]
    protected function restore_cookie_state(): void
    {
        $_COOKIE = $this->original_cookie;
    }
}
