<?php

declare(strict_types=1);

namespace Harbor\Tests\Log;

use Harbor\Helper;
use Harbor\Log\LogLevel;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\TestCase;

use function Harbor\Log\log_create_content;
use function Harbor\Log\log_exception;
use function Harbor\Log\log_file_path;
use function Harbor\Log\log_info;
use function Harbor\Log\log_init;
use function Harbor\Log\log_is_initialized;
use function Harbor\Log\log_levels;
use function Harbor\Log\log_reset;
use function Harbor\Log\log_warning;
use function Harbor\Log\log_write;
use function Harbor\Log\log_write_content;

/**
 * Class LogHelpersTest.
 */
final class LogHelpersTest extends TestCase
{
    private string $workspace_path;
    private string $log_file_path;
    private array $original_env = [];

    #[BeforeClass]
    public static function load_log_helpers(): void
    {
        Helper::load_many('log');
    }

    public function test_log_write_throws_when_log_is_not_initialized(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Log file not initialized. Call log_init() first.');

        log_info('Should fail');
    }

    public function test_log_init_creates_log_file_and_marks_initialized(): void
    {
        log_init($this->log_file_path);

        self::assertTrue(log_is_initialized());
        self::assertSame($this->log_file_path, log_file_path());
        self::assertTrue(is_file($this->log_file_path));
    }

    public function test_log_is_initialized_returns_false_when_internal_state_is_missing(): void
    {
        unset($GLOBALS['log_is_initialized']);

        self::assertFalse(log_is_initialized());
    }

    public function test_log_helpers_write_entries_with_context_and_interpolation(): void
    {
        log_init($this->log_file_path);

        log_info('User {user} logged in', [
            'user' => 'ada',
            'id' => 7,
        ]);
        log_warning('Disk is low', [
            'free_mb' => 128,
        ]);

        $content = file_get_contents($this->log_file_path);
        self::assertIsString($content);
        self::assertStringContainsString('[INFO] [app] User ada logged in', $content);
        self::assertStringContainsString('"id":7', $content);
        self::assertStringContainsString('[WARNING] [app] Disk is low', $content);
        self::assertStringContainsString('"free_mb":128', $content);
    }

    public function test_log_create_content_returns_formatted_log_line_for_stdout_or_stderr_use(): void
    {
        $log_content = log_create_content(LogLevel::NOTICE, 'Health check for {service}', [
            'service' => 'api',
            'ok' => true,
        ], 'cli');

        self::assertStringContainsString('[NOTICE] [cli] Health check for api', $log_content);
        self::assertStringContainsString('"ok":true', $log_content);
        self::assertStringNotContainsString(PHP_EOL, $log_content);
    }

    public function test_log_write_content_appends_prebuilt_content(): void
    {
        log_init($this->log_file_path);

        $log_content = log_create_content(LogLevel::INFO, 'Prebuilt message', [
            'id' => 15,
        ], 'worker');

        log_write_content($log_content);

        $content = file_get_contents($this->log_file_path);
        self::assertIsString($content);
        self::assertStringContainsString('[INFO] [worker] Prebuilt message', $content);
        self::assertStringContainsString('"id":15', $content);
    }

    public function test_log_write_supports_custom_channel_and_level(): void
    {
        log_init($this->log_file_path);

        log_write(LogLevel::DEBUG, 'Cache miss for {key}', [
            'key' => 'profile:1',
        ], 'http');

        $content = file_get_contents($this->log_file_path);
        self::assertIsString($content);
        self::assertStringContainsString('[DEBUG] [http] Cache miss for profile:1', $content);
    }

    public function test_log_helpers_write_to_configured_single_channel_without_log_init(): void
    {
        $single_log_path = $this->workspace_path.'/single.log';
        $_ENV['logging'] = [
            'default' => 'single',
            'channels' => [
                'single' => [
                    'driver' => 'single',
                    'path' => $single_log_path,
                    'channel' => 'app',
                ],
            ],
        ];
        $GLOBALS['_ENV'] = $_ENV;

        log_info('Single channel {status}', [
            'status' => 'works',
        ]);

        self::assertFileExists($single_log_path);

        $content = file_get_contents($single_log_path);
        self::assertIsString($content);
        self::assertStringContainsString('[INFO] [app] Single channel works', $content);
    }

    public function test_log_helpers_write_to_configured_daily_channel_file(): void
    {
        $daily_base_path = $this->workspace_path.'/daily.log';
        $_ENV['logging'] = [
            'default' => 'daily',
            'channels' => [
                'daily' => [
                    'driver' => 'daily',
                    'path' => $daily_base_path,
                    'days' => 7,
                    'channel' => 'daily',
                ],
            ],
        ];
        $GLOBALS['_ENV'] = $_ENV;

        log_warning('Daily channel entry');

        $daily_file_path = $this->workspace_path.'/daily-'.date('Y-m-d').'.log';
        self::assertFileExists($daily_file_path);

        $content = file_get_contents($daily_file_path);
        self::assertIsString($content);
        self::assertStringContainsString('[WARNING] [daily] Daily channel entry', $content);
    }

    public function test_log_helpers_write_to_stack_channel_and_fan_out_to_each_target(): void
    {
        $single_log_path = $this->workspace_path.'/stack-single.log';
        $daily_base_path = $this->workspace_path.'/stack-daily.log';
        $_ENV['logging'] = [
            'default' => 'stack',
            'channels' => [
                'single' => [
                    'driver' => 'single',
                    'path' => $single_log_path,
                    'channel' => 'single',
                ],
                'daily' => [
                    'driver' => 'daily',
                    'path' => $daily_base_path,
                    'days' => 3,
                    'channel' => 'daily',
                ],
                'stack' => [
                    'driver' => 'stack',
                    'channels' => ['single', 'daily'],
                ],
            ],
        ];
        $GLOBALS['_ENV'] = $_ENV;

        log_write(LogLevel::ERROR, 'Stacked {id}', [
            'id' => 77,
        ], 'stack');

        $daily_file_path = $this->workspace_path.'/stack-daily-'.date('Y-m-d').'.log';

        self::assertFileExists($single_log_path);
        self::assertFileExists($daily_file_path);

        $single_content = file_get_contents($single_log_path);
        self::assertIsString($single_content);
        self::assertStringContainsString('[ERROR] [single] Stacked 77', $single_content);

        $daily_content = file_get_contents($daily_file_path);
        self::assertIsString($daily_content);
        self::assertStringContainsString('[ERROR] [daily] Stacked 77', $daily_content);
    }

    public function test_log_exception_writes_exception_payload(): void
    {
        log_init($this->log_file_path);

        try {
            throw new \RuntimeException('Boom', 500);
        } catch (\RuntimeException $exception) {
            log_exception($exception, [
                'request_id' => 'req-1',
            ]);
        }

        $content = file_get_contents($this->log_file_path);
        self::assertIsString($content);
        self::assertStringContainsString('[ERROR] [app] Unhandled exception', $content);
        self::assertStringContainsString('"request_id":"req-1"', $content);
        self::assertStringContainsString('"class":"RuntimeException"', $content);
        self::assertStringContainsString('"message":"Boom"', $content);
        self::assertStringContainsString('"code":500', $content);
    }

    public function test_log_write_throws_for_invalid_level(): void
    {
        log_init($this->log_file_path);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid log level "verbose".');

        log_write('verbose', 'Invalid level');
    }

    public function test_log_levels_returns_supported_levels(): void
    {
        self::assertSame(
            [
                'debug',
                'info',
                'notice',
                'warning',
                'error',
                'critical',
                'alert',
                'emergency',
            ],
            log_levels()
        );
    }

    #[Before]
    protected function create_workspace(): void
    {
        $this->original_env = is_array($_ENV) ? $_ENV : [];
        $this->workspace_path = sys_get_temp_dir().'/php_framework_log_'.bin2hex(random_bytes(8));
        $this->log_file_path = $this->workspace_path.'/app.log';

        if (! mkdir($this->workspace_path, 0o777, true) && ! is_dir($this->workspace_path)) {
            throw new \RuntimeException(sprintf('Failed to create test workspace "%s".', $this->workspace_path));
        }

        $_ENV = $this->original_env;
        unset($_ENV['logging']);
        $GLOBALS['_ENV'] = $_ENV;

        log_reset();
    }

    #[After]
    protected function cleanup_workspace(): void
    {
        log_reset();
        $_ENV = $this->original_env;
        $GLOBALS['_ENV'] = $_ENV;

        if (! is_dir($this->workspace_path)) {
            return;
        }

        $entries = scandir($this->workspace_path);
        if (false === $entries) {
            return;
        }

        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }

            $entry_path = $this->workspace_path.'/'.$entry;

            if (is_file($entry_path)) {
                unlink($entry_path);
            }
        }

        rmdir($this->workspace_path);
    }
}
