<?php

declare(strict_types=1);

namespace Harbor\Tests\Request;

use Harbor\Helper;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

use function Harbor\Request\request_clear_old_input;
use function Harbor\Request\request_flash_old_input;
use function Harbor\Request\request_has_old;
use function Harbor\Request\request_old;
use function Harbor\Session\session_array_clear;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
/**
 * Class RequestOldInputHelpersTest.
 */
final class RequestOldInputHelpersTest extends TestCase
{
    private array $original_cookie = [];
    private array $original_env = [];
    private array $original_post = [];
    private array $original_server = [];

    public function test_request_flash_old_input_defaults_to_request_body_and_filters_sensitive_keys(): void
    {
        $_SERVER['REQUEST_TIME_FLOAT'] = 100.1;
        $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        $_POST = [
            'email' => 'ada@example.com',
            'password' => 'secret',
            '_token' => 'csrf-token',
            'profile' => [
                'name' => 'Ada',
            ],
        ];

        Helper::load_many('request');

        self::assertTrue(request_flash_old_input());
        self::assertSame('ada@example.com', request_old('email', ''));
        self::assertSame('Ada', request_old('profile.name', ''));
        self::assertFalse(request_has_old('password'));
        self::assertFalse(request_has_old('_token'));
        self::assertSame(
            [
                'email' => 'ada@example.com',
                'profile' => [
                    'name' => 'Ada',
                ],
            ],
            request_old()
        );
    }

    public function test_request_old_helpers_support_named_bags_and_custom_except(): void
    {
        $_SERVER['REQUEST_TIME_FLOAT'] = 200.1;

        Helper::load_many('request');

        self::assertTrue(request_flash_old_input([
            'name' => 'Ada',
            'role' => 'admin',
            'token' => 'x',
        ], ['token'], 'profile'));

        self::assertSame('fallback', request_old('name', 'fallback'));
        self::assertSame('Ada', request_old('name', 'fallback', 'profile'));
        self::assertSame('admin', request_old('role', 'fallback', 'profile'));
        self::assertFalse(request_has_old('token', 'profile'));
    }

    public function test_request_old_helpers_expire_after_next_request_cycle(): void
    {
        $_SERVER['REQUEST_TIME_FLOAT'] = 300.1;
        Helper::load_many('request');

        request_flash_old_input([
            'email' => 'ada@example.com',
        ]);

        $_SERVER['REQUEST_TIME_FLOAT'] = 301.1;
        self::assertTrue(request_has_old('email'));
        self::assertSame('ada@example.com', request_old('email', ''));

        $_SERVER['REQUEST_TIME_FLOAT'] = 302.1;
        self::assertFalse(request_has_old('email'));
        self::assertSame('fallback', request_old('email', 'fallback'));
    }

    public function test_request_clear_old_input_removes_named_bag(): void
    {
        $_SERVER['REQUEST_TIME_FLOAT'] = 400.1;
        Helper::load_many('request');

        request_flash_old_input([
            'name' => 'Ada',
        ], [], 'profile');

        self::assertTrue(request_has_old('name', 'profile'));
        self::assertTrue(request_clear_old_input('profile'));
        self::assertFalse(request_has_old('name', 'profile'));
    }

    #[Before]
    protected function bootstrap_old_input_helpers(): void
    {
        $this->original_cookie = is_array($_COOKIE) ? $_COOKIE : [];
        $this->original_env = is_array($_ENV) ? $_ENV : [];
        $this->original_post = is_array($_POST) ? $_POST : [];
        $this->original_server = is_array($_SERVER) ? $_SERVER : [];

        $_COOKIE = [];
        $_POST = [];
        $_SERVER = $this->original_server;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/submit';
        $_SERVER['REQUEST_TIME_FLOAT'] = 1.0;
        $_ENV = $this->original_env;
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
            'file_path' => null,
            'id_cookie' => null,
        ];
        $GLOBALS['_ENV'] = $_ENV;

        Helper::load_many('session');
        session_array_clear();
    }

    #[After]
    protected function restore_old_input_helper_state(): void
    {
        $_COOKIE = $this->original_cookie;
        $_POST = $this->original_post;
        $_SERVER = $this->original_server;
        $_ENV = $this->original_env;
        $GLOBALS['_ENV'] = $_ENV;
    }
}
