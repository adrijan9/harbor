<?php

declare(strict_types=1);

namespace Harbor\Tests\Log;

use Harbor\HelperLoader;
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

final class LogHelpersTest extends TestCase
{
    private string $workspace_path;
    private string $log_file_path;

    #[BeforeClass]
    public static function load_log_helpers(): void
    {
        HelperLoader::load('log');
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
        $this->workspace_path = sys_get_temp_dir().'/php_framework_log_'.bin2hex(random_bytes(8));
        $this->log_file_path = $this->workspace_path.'/app.log';

        if (! mkdir($this->workspace_path, 0o777, true) && ! is_dir($this->workspace_path)) {
            throw new \RuntimeException(sprintf('Failed to create test workspace "%s".', $this->workspace_path));
        }

        log_reset();
    }

    #[After]
    protected function cleanup_workspace(): void
    {
        log_reset();

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
