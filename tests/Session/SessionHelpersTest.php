<?php

declare(strict_types=1);

namespace Harbor\Tests\Session;

use Harbor\Helper;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

use function Harbor\Session\session_all;
use function Harbor\Session\session_clear;
use function Harbor\Session\session_config;
use function Harbor\Session\session_driver;
use function Harbor\Session\session_flash_all;
use function Harbor\Session\session_flash_clear;
use function Harbor\Session\session_flash_forget;
use function Harbor\Session\session_flash_get;
use function Harbor\Session\session_flash_has;
use function Harbor\Session\session_flash_pull;
use function Harbor\Session\session_flash_set;
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
    private array $original_server = [];
    private array $created_directories = [];

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

    public function test_session_helpers_support_array_driver_without_cookie_storage(): void
    {
        $_ENV['session'] = [
            'driver' => 'array',
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

        self::assertSame('array', session_driver());
        self::assertTrue(session_set('flash_message', 'Saved'));
        self::assertSame('Saved', session_get('flash_message'));
        self::assertArrayNotHasKey('harbor_flash_message', $_COOKIE);
        self::assertSame(['flash_message' => 'Saved'], session_all());

        self::assertTrue(session_clear());
        self::assertSame([], session_all());
    }

    public function test_session_driver_falls_back_to_cookie_when_config_is_invalid(): void
    {
        $_ENV['session']['driver'] = 'redis';
        $GLOBALS['_ENV'] = $_ENV;

        self::assertSame('cookie', session_driver());
    }

    public function test_session_helpers_support_file_driver_with_session_id_cookie(): void
    {
        $session_path = $this->create_temp_directory('harbor_session_file_');

        $_ENV['session'] = [
            'driver' => 'file',
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
            'file_path' => $session_path,
            'id_cookie' => 'harbor-session-id',
        ];
        $GLOBALS['_ENV'] = $_ENV;

        self::assertTrue(session_set('profile_id', 77));
        self::assertSame('file', session_driver());
        self::assertArrayHasKey('harbor-session-id', $_COOKIE);
        self::assertArrayNotHasKey('harbor_profile_id', $_COOKIE);
        self::assertSame(77, session_get('profile_id'));
        self::assertSame(['profile_id' => 77], session_all());
        self::assertDirectoryExists($session_path);
        self::assertFileExists($session_path.'/.gitignore');

        self::assertTrue(session_clear());
        self::assertArrayNotHasKey('harbor-session-id', $_COOKIE);
        self::assertFalse(session_has('profile_id'));
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

    public function test_session_flash_helpers_store_and_read_values(): void
    {
        $_SERVER['REQUEST_TIME_FLOAT'] = 100.1;

        self::assertTrue(session_flash_set('notice', 'Saved'));
        self::assertTrue(session_flash_has('notice'));
        self::assertSame('Saved', session_flash_get('notice'));
        self::assertSame(['notice' => 'Saved'], session_flash_all());
    }

    public function test_session_flash_helpers_expire_after_next_request(): void
    {
        $_SERVER['REQUEST_TIME_FLOAT'] = 200.1;
        session_flash_set('status', 'Account updated');

        $_SERVER['REQUEST_TIME_FLOAT'] = 201.1;
        self::assertSame('Account updated', session_flash_get('status'));
        self::assertTrue(session_flash_has('status'));

        $_SERVER['REQUEST_TIME_FLOAT'] = 202.1;
        self::assertSame('fallback', session_flash_get('status', 'fallback'));
        self::assertFalse(session_flash_has('status'));
    }

    public function test_session_flash_pull_forget_and_clear_flow(): void
    {
        $_SERVER['REQUEST_TIME_FLOAT'] = 300.1;
        session_flash_set('notice', 'Saved');
        session_flash_set('warning', 'Careful');

        self::assertSame('Saved', session_flash_pull('notice'));
        self::assertFalse(session_flash_has('notice'));

        self::assertTrue(session_flash_forget('warning'));
        self::assertFalse(session_flash_has('warning'));

        session_flash_set('a', 1);
        session_flash_set('b', 2);

        self::assertTrue(session_flash_clear());
        self::assertSame([], session_flash_all());
    }

    public function test_session_helpers_use_configured_prefix_and_options(): void
    {
        $_ENV['session'] = [
            'driver' => 'cookie',
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
        self::assertSame('cookie', session_driver());
        self::assertArrayHasKey('app_session_theme', $_COOKIE);
        self::assertSame('app_session', session_config('prefix'));
        self::assertSame('cookie', session_config()['driver']);
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
            'driver' => 'cookie',
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
        $this->original_server = is_array($_SERVER) ? $_SERVER : [];

        $_COOKIE = [];
        $_SERVER = $this->original_server;
        $_SERVER['REQUEST_TIME_FLOAT'] = 1.0;
        $_ENV = $this->original_env;
        $_ENV['session'] = [
            'driver' => 'cookie',
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
            'file_path' => null,
            'id_cookie' => null,
        ];
        $GLOBALS['_ENV'] = $_ENV;

        Helper::load_many('session');
    }

    #[After]
    protected function restore_session_state(): void
    {
        $_COOKIE = $this->original_cookie;
        $_SERVER = $this->original_server;
        $_ENV = $this->original_env;
        $GLOBALS['_ENV'] = $_ENV;

        foreach ($this->created_directories as $directory_path) {
            $this->delete_directory_tree($directory_path);
        }
    }

    private function create_temp_directory(string $prefix): string
    {
        $directory_path = sys_get_temp_dir().'/'.$prefix.bin2hex(random_bytes(8));

        if (! mkdir($directory_path, 0o777, true) && ! is_dir($directory_path)) {
            throw new \RuntimeException(sprintf('Failed to create temp directory "%s".', $directory_path));
        }

        $resolved_directory_path = realpath($directory_path);
        if (false === $resolved_directory_path) {
            throw new \RuntimeException(sprintf('Failed to resolve temp directory "%s".', $directory_path));
        }

        $this->created_directories[] = $resolved_directory_path;

        return $resolved_directory_path;
    }

    private function delete_directory_tree(string $directory_path): void
    {
        if (! is_dir($directory_path)) {
            return;
        }

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
