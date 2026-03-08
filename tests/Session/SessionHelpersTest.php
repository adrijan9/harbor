<?php

declare(strict_types=1);

namespace Harbor\Tests\Session;

use Harbor\HelperLoader;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

use function Harbor\Session\session_all;
use function Harbor\Session\session_clear;
use function Harbor\Session\session_config;
use function Harbor\Session\session_forget;
use function Harbor\Session\session_get;
use function Harbor\Session\session_has;
use function Harbor\Session\session_pull;
use function Harbor\Session\session_set;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
/**
 * Class SessionHelpersTest.
 */
final class SessionHelpersTest extends TestCase
{
    private array $original_cookie = [];
    private array $original_env = [];

    public function test_session_set_get_has_and_forget_use_cookie_storage(): void
    {
        self::assertTrue(session_set('user_id', 15));
        self::assertSame(15, session_get('user_id'));
        self::assertTrue(session_has('user_id'));
        self::assertArrayHasKey('harbor_user_id', $_COOKIE);

        self::assertTrue(session_forget('user_id'));
        self::assertFalse(session_has('user_id'));
        self::assertSame('fallback', session_get('user_id', 'fallback'));
    }

    public function test_session_all_and_clear_only_work_with_session_prefix(): void
    {
        $_COOKIE['plain_cookie'] = 'keep-me';
        session_set('profile', ['id' => 9, 'name' => 'Ada']);
        session_set('token', 'abc');

        self::assertSame(
            [
                'profile' => [
                    'id' => 9,
                    'name' => 'Ada',
                ],
                'token' => 'abc',
            ],
            session_all()
        );

        self::assertTrue(session_clear());
        self::assertSame([], session_all());
        self::assertSame('keep-me', $_COOKIE['plain_cookie']);
    }

    public function test_session_pull_returns_value_and_removes_session_key(): void
    {
        session_set('flash_notice', 'Saved');

        self::assertSame('Saved', session_pull('flash_notice'));
        self::assertFalse(session_has('flash_notice'));
    }

    public function test_session_helpers_use_configured_prefix_and_options(): void
    {
        $_ENV['session'] = [
            'prefix' => 'app_session',
            'ttl_seconds' => 3600,
            'path' => '/account',
            'domain' => 'example.test',
            'secure' => true,
            'http_only' => false,
            'same_site' => 'strict',
            'signed' => true,
            'signing_key' => 'session-signing-key',
            'encrypted' => false,
            'encryption_key' => null,
        ];
        $GLOBALS['_ENV'] = $_ENV;

        self::assertTrue(session_set('theme', 'dark'));
        self::assertArrayHasKey('app_session_theme', $_COOKIE);
        self::assertSame('app_session', session_config('prefix'));
        self::assertSame('Strict', session_config()['same_site']);
        self::assertSame(3600, session_config()['ttl_seconds']);
        self::assertSame('/account', session_config()['path']);
        self::assertSame('example.test', session_config()['domain']);
        self::assertTrue(session_config()['secure']);
        self::assertFalse(session_config()['http_only']);
        self::assertTrue(session_config()['signed']);
        self::assertFalse(session_config()['encrypted']);
        self::assertSame('session-signing-key', session_config()['signing_key']);
        self::assertNull(session_config()['encryption_key']);
    }

    public function test_session_helpers_can_sign_and_encrypt_cookie_payload(): void
    {
        if (! function_exists('openssl_encrypt') || ! function_exists('openssl_decrypt')) {
            self::markTestSkipped('OpenSSL extension is required for encrypted session tests.');
        }

        $_ENV['session'] = [
            'prefix' => 'harbor',
            'ttl_seconds' => 7200,
            'path' => '/',
            'domain' => null,
            'secure' => false,
            'http_only' => true,
            'same_site' => 'lax',
            'signed' => true,
            'encrypted' => true,
            'signing_key' => 'session-signing-key',
            'encryption_key' => 'session-encryption-key',
        ];
        $GLOBALS['_ENV'] = $_ENV;

        self::assertTrue(session_set('auth_token', 'abc123'));
        self::assertArrayHasKey('harbor_auth_token', $_COOKIE);
        self::assertNotSame('abc123', $_COOKIE['harbor_auth_token']);
        self::assertSame('abc123', session_get('auth_token'));

        $_COOKIE['harbor_auth_token'] .= 'tampered';

        self::assertSame('fallback', session_get('auth_token', 'fallback'));
    }

    public function test_session_set_throws_for_blank_key(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Session key cannot be empty.');

        session_set('   ', 'value');
    }

    #[Before]
    protected function bootstrap_session_helpers(): void
    {
        $this->original_cookie = is_array($_COOKIE) ? $_COOKIE : [];
        $this->original_env = is_array($_ENV) ? $_ENV : [];

        $_COOKIE = [];
        $_ENV = $this->original_env;
        $_ENV['session'] = [
            'prefix' => 'harbor',
            'ttl_seconds' => 7200,
            'path' => '/',
            'domain' => null,
            'secure' => false,
            'http_only' => true,
            'same_site' => 'lax',
            'signed' => false,
            'encrypted' => false,
            'signing_key' => null,
            'encryption_key' => null,
        ];
        $GLOBALS['_ENV'] = $_ENV;

        HelperLoader::load('session');
    }

    #[After]
    protected function restore_session_state(): void
    {
        $_COOKIE = $this->original_cookie;
        $_ENV = $this->original_env;
        $GLOBALS['_ENV'] = $_ENV;
    }
}
