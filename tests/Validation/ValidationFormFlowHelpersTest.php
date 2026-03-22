<?php

declare(strict_types=1);

namespace Harbor\Tests\Validation;

use Harbor\Helper;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

use function Harbor\Session\session_array_clear;
use function Harbor\Validation\validation_form_clear;
use function Harbor\Validation\validation_form_errors;
use function Harbor\Validation\validation_form_field_errors;
use function Harbor\Validation\validation_form_first_error;
use function Harbor\Validation\validation_form_flash;
use function Harbor\Validation\validation_form_has_errors;
use function Harbor\Validation\validation_rule;
use function Harbor\Validation\validation_validate;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
/**
 * Class ValidationFormFlowHelpersTest.
 */
final class ValidationFormFlowHelpersTest extends TestCase
{
    private array $original_cookie = [];
    private array $original_env = [];
    private array $original_server = [];

    public function test_validation_form_flash_and_read_default_bag(): void
    {
        $_SERVER['REQUEST_TIME_FLOAT'] = 100.1;

        $result = validation_validate([
            'email' => 'invalid-email',
        ], [
            validation_rule('email')->required()->email(),
        ]);

        self::assertTrue(validation_form_flash($result));
        self::assertTrue(validation_form_has_errors());

        $errors = validation_form_errors();
        self::assertArrayHasKey('email', $errors);
        self::assertNotEmpty(validation_form_field_errors('email'));
        self::assertStringContainsString('email', validation_form_field_errors('email')[0]);
        self::assertStringContainsString('email', validation_form_first_error('email', 'default', ''));
    }

    public function test_validation_form_helpers_support_named_bags_and_clear(): void
    {
        $_SERVER['REQUEST_TIME_FLOAT'] = 200.1;

        $result = validation_validate([
            'name' => '',
        ], [
            validation_rule('name')->required()->string()->min(2),
        ]);

        self::assertTrue(validation_form_flash($result, 'profile'));
        self::assertFalse(validation_form_has_errors());
        self::assertTrue(validation_form_has_errors('profile'));
        self::assertStringContainsString('name', validation_form_first_error('name', 'profile', ''));

        self::assertTrue(validation_form_clear('profile'));
        self::assertFalse(validation_form_has_errors('profile'));
        self::assertSame([], validation_form_errors('profile'));
    }

    public function test_validation_form_helpers_expire_after_next_request_cycle(): void
    {
        $_SERVER['REQUEST_TIME_FLOAT'] = 300.1;

        $result = validation_validate([
            'email' => '',
        ], [
            validation_rule('email')->required()->email(),
        ]);

        validation_form_flash($result);

        $_SERVER['REQUEST_TIME_FLOAT'] = 301.1;
        self::assertTrue(validation_form_has_errors());
        self::assertNotEmpty(validation_form_field_errors('email'));

        $_SERVER['REQUEST_TIME_FLOAT'] = 302.1;
        self::assertFalse(validation_form_has_errors());
        self::assertSame([], validation_form_errors());
        self::assertSame('fallback', validation_form_first_error('email', 'default', 'fallback'));
    }

    public function test_validation_form_flash_with_ok_result_clears_existing_error_bag(): void
    {
        $_SERVER['REQUEST_TIME_FLOAT'] = 400.1;

        $failed_result = validation_validate([
            'email' => '',
        ], [
            validation_rule('email')->required()->email(),
        ]);
        validation_form_flash($failed_result);
        self::assertTrue(validation_form_has_errors());

        $ok_result = validation_validate([
            'email' => 'ada@example.com',
        ], [
            validation_rule('email')->required()->email(),
        ]);

        self::assertTrue(validation_form_flash($ok_result));
        self::assertFalse(validation_form_has_errors());
    }

    #[Before]
    protected function bootstrap_validation_form_flow_helpers(): void
    {
        $this->original_cookie = is_array($_COOKIE) ? $_COOKIE : [];
        $this->original_env = is_array($_ENV) ? $_ENV : [];
        $this->original_server = is_array($_SERVER) ? $_SERVER : [];

        $_COOKIE = [];
        $_SERVER = $this->original_server;
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

        Helper::load_many('session', 'validation');
        session_array_clear();
    }

    #[After]
    protected function restore_validation_form_flow_state(): void
    {
        $_COOKIE = $this->original_cookie;
        $_SERVER = $this->original_server;
        $_ENV = $this->original_env;
        $GLOBALS['_ENV'] = $_ENV;
    }
}
