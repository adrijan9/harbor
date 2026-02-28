<?php

declare(strict_types=1);

namespace Harbor\Tests\Performance;

use Harbor\HelperLoader;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\TestCase;

use function Harbor\Log\log_file_path;
use function Harbor\Log\log_reset;
use function Harbor\Performance\performance_begin;
use function Harbor\Performance\performance_end;
use function Harbor\Performance\performance_end_log;

final class PerformanceHelpersTest extends TestCase
{
    private string $workspace_path;
    private string $previous_working_directory;

    #[BeforeClass]
    public static function load_performance_helpers(): void
    {
        HelperLoader::load('log');
        HelperLoader::load('performance');
    }

    public function test_performance_begin_and_end_return_metrics_array(): void
    {
        performance_begin();
        usleep(500);

        $results = performance_end();

        self::assertSame('default', $results['marker']);
        self::assertIsFloat($results['started_at_unix']);
        self::assertIsFloat($results['ended_at_unix']);
        self::assertIsFloat($results['duration_ms']);
        self::assertIsString($results['duration_human']);
        self::assertGreaterThanOrEqual(0.0, $results['duration_ms']);
        self::assertIsInt($results['start_memory_usage_bytes']);
        self::assertIsInt($results['end_memory_usage_bytes']);
        self::assertIsInt($results['memory_usage_delta_bytes']);
        self::assertIsString($results['start_memory_usage_human']);
        self::assertIsString($results['end_memory_usage_human']);
        self::assertIsString($results['memory_usage_delta_human']);
        self::assertIsInt($results['start_peak_memory_usage_bytes']);
        self::assertIsInt($results['end_peak_memory_usage_bytes']);
        self::assertIsInt($results['peak_memory_usage_delta_bytes']);
        self::assertIsString($results['start_peak_memory_usage_human']);
        self::assertIsString($results['end_peak_memory_usage_human']);
        self::assertIsString($results['peak_memory_usage_delta_human']);
    }

    public function test_performance_supports_named_markers(): void
    {
        performance_begin('db.query');
        usleep(200);

        $results = performance_end('db.query');

        self::assertSame('db.query', $results['marker']);
        self::assertGreaterThanOrEqual(0.0, $results['duration_ms']);
    }

    public function test_performance_begin_starts_marker(): void
    {
        performance_begin('begin.alias');
        usleep(200);

        $results = performance_end('begin.alias');

        self::assertSame('begin.alias', $results['marker']);
        self::assertGreaterThanOrEqual(0.0, $results['duration_ms']);
    }

    public function test_performance_end_throws_when_marker_is_missing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Performance marker "missing.marker" was not started.');

        performance_end('missing.marker');
    }

    public function test_performance_end_log_returns_metrics_array_and_writes_tracking_log_file(): void
    {
        performance_begin('request');

        $results = performance_end_log('request', '[harbor.perf]');
        $tracked_log_file_path = log_file_path();

        self::assertSame('request', $results['marker']);
        self::assertArrayHasKey('duration_ms', $results);
        self::assertArrayHasKey('duration_human', $results);
        self::assertIsString($tracked_log_file_path);
        self::assertSame(1, preg_match('/\/logs\/performance_\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2}_tracking\.log$/', $tracked_log_file_path));

        $tracked_log_content = file_get_contents($tracked_log_file_path);
        self::assertIsString($tracked_log_content);
        self::assertStringContainsString('[INFO] [performance] [harbor.perf] marker request finished in', $tracked_log_content);
        self::assertStringContainsString('"marker":"request"', $tracked_log_content);
    }

    #[Before]
    protected function create_workspace_and_set_as_current_site_directory(): void
    {
        $working_directory = getcwd();
        if (false === $working_directory) {
            throw new \RuntimeException('Failed to read current working directory.');
        }

        $this->previous_working_directory = $working_directory;
        $this->workspace_path = sys_get_temp_dir().'/harbor_performance_'.bin2hex(random_bytes(8));

        if (! mkdir($this->workspace_path, 0o777, true) && ! is_dir($this->workspace_path)) {
            throw new \RuntimeException(sprintf('Failed to create test workspace "%s".', $this->workspace_path));
        }

        if (! chdir($this->workspace_path)) {
            throw new \RuntimeException(sprintf('Failed to enter workspace "%s".', $this->workspace_path));
        }

        log_reset();
    }

    #[After]
    protected function restore_working_directory_and_cleanup_workspace(): void
    {
        log_reset();

        chdir($this->previous_working_directory);

        if (! is_dir($this->workspace_path)) {
            return;
        }

        $this->delete_directory_tree($this->workspace_path);
    }

    private function delete_directory_tree(string $directory_path): void
    {
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
