<?php

declare(strict_types=1);

namespace Harbor\Performance;

require_once __DIR__.'/../Log/log.php';

require_once __DIR__.'/../Support/value.php';
require_once __DIR__.'/../Units/units.php';

use function Harbor\Log\log_file_path;
use function Harbor\Log\log_info;
use function Harbor\Log\log_init;
use function Harbor\Log\log_is_initialized;
use function Harbor\Support\harbor_is_blank;
use function Harbor\Units\unit_bytes_to_human;
use function Harbor\Units\unit_duration_ms_to_human;

final class PerformanceTracker
{
    private const DEFAULT_MARKER = 'default';
    private const DEFAULT_LOG_PREFIX = '[harbor.performance]';

    /**
     * @var array<string, array{started_at_unix: float, start_memory_usage_bytes: int, start_peak_memory_usage_bytes: int}>
     */
    private static array $markers = [];

    public static function begin(?string $marker = null): void
    {
        $normalized_marker = self::normalize_marker($marker);

        self::$markers[$normalized_marker] = [
            'started_at_unix' => microtime(true),
            'start_memory_usage_bytes' => memory_get_usage(true),
            'start_peak_memory_usage_bytes' => memory_get_peak_usage(true),
        ];
    }

    public static function end(?string $marker = null): array
    {
        $normalized_marker = self::normalize_marker($marker);
        $started_marker = self::$markers[$normalized_marker] ?? null;

        if (! is_array($started_marker)) {
            throw new \RuntimeException(
                sprintf('Performance marker "%s" was not started.', $normalized_marker)
            );
        }

        unset(self::$markers[$normalized_marker]);

        $ended_at_unix = microtime(true);
        $end_memory_usage_bytes = memory_get_usage(true);
        $end_peak_memory_usage_bytes = memory_get_peak_usage(true);
        $duration_ms = round(($ended_at_unix - $started_marker['started_at_unix']) * 1000, 3);
        $memory_usage_delta_bytes = $end_memory_usage_bytes - $started_marker['start_memory_usage_bytes'];
        $peak_memory_usage_delta_bytes = $end_peak_memory_usage_bytes - $started_marker['start_peak_memory_usage_bytes'];

        return [
            'marker' => $normalized_marker,
            'started_at_unix' => $started_marker['started_at_unix'],
            'ended_at_unix' => $ended_at_unix,
            'duration_ms' => $duration_ms,
            'duration_human' => unit_duration_ms_to_human($duration_ms),
            'start_memory_usage_bytes' => $started_marker['start_memory_usage_bytes'],
            'end_memory_usage_bytes' => $end_memory_usage_bytes,
            'memory_usage_delta_bytes' => $memory_usage_delta_bytes,
            'start_memory_usage_human' => unit_bytes_to_human($started_marker['start_memory_usage_bytes']),
            'end_memory_usage_human' => unit_bytes_to_human($end_memory_usage_bytes),
            'memory_usage_delta_human' => unit_bytes_to_human($memory_usage_delta_bytes),
            'start_peak_memory_usage_bytes' => $started_marker['start_peak_memory_usage_bytes'],
            'end_peak_memory_usage_bytes' => $end_peak_memory_usage_bytes,
            'peak_memory_usage_delta_bytes' => $peak_memory_usage_delta_bytes,
            'start_peak_memory_usage_human' => unit_bytes_to_human($started_marker['start_peak_memory_usage_bytes']),
            'end_peak_memory_usage_human' => unit_bytes_to_human($end_peak_memory_usage_bytes),
            'peak_memory_usage_delta_human' => unit_bytes_to_human($peak_memory_usage_delta_bytes),
        ];
    }

    public static function end_log(?string $marker = null, ?string $prefix = null): array
    {
        $results = self::end($marker);

        $normalized_prefix = self::DEFAULT_LOG_PREFIX;
        if (is_string($prefix) && ! harbor_is_blank($prefix)) {
            $normalized_prefix = trim($prefix);
        }

        self::write_results_to_tracking_log($results, $normalized_prefix);

        return $results;
    }

    private static function normalize_marker(?string $marker): string
    {
        if (is_string($marker) && ! harbor_is_blank($marker)) {
            return trim($marker);
        }

        return self::DEFAULT_MARKER;
    }

    private static function serialize_results_for_log(array $results): string
    {
        $serialized_results = json_encode(
            $results,
            JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );

        if (is_string($serialized_results)) {
            return $serialized_results;
        }

        return '{"error":"Failed to serialize performance results."}';
    }

    private static function write_results_to_tracking_log(array $results, string $prefix): void
    {
        $ended_at_unix = $results['ended_at_unix'] ?? null;
        if (! is_float($ended_at_unix) && ! is_int($ended_at_unix)) {
            $ended_at_unix = microtime(true);
        }

        $target_log_file_path = self::resolve_tracking_log_file_path((float) $ended_at_unix);
        $had_initialized_logger = log_is_initialized();
        $previous_log_file_path = log_file_path();

        if (! $had_initialized_logger || $previous_log_file_path !== $target_log_file_path) {
            log_init($target_log_file_path, 'performance');
        }

        log_info(
            $prefix.' marker {marker} finished in {duration_ms}ms',
            [
                'marker' => $results['marker'] ?? self::DEFAULT_MARKER,
                'duration_ms' => $results['duration_ms'] ?? 0,
                'performance' => $results,
                'raw' => self::serialize_results_for_log($results),
            ],
            'performance'
        );

        if ($had_initialized_logger && is_string($previous_log_file_path) && $previous_log_file_path !== $target_log_file_path) {
            log_init($previous_log_file_path);
        }
    }

    private static function resolve_tracking_log_file_path(float $ended_at_unix): string
    {
        $site_directory_path = self::resolve_current_site_directory();
        $logs_directory_path = $site_directory_path.'/logs';
        $timestamp = date('Y-m-d-H-s-i', (int) floor($ended_at_unix));
        $log_file_name = 'performance_'.$timestamp.'_tracking.log';

        return $logs_directory_path.'/'.$log_file_name;
    }

    private static function resolve_current_site_directory(): string
    {
        $document_root = $_SERVER['DOCUMENT_ROOT'] ?? null;
        if (is_string($document_root) && ! harbor_is_blank($document_root)) {
            $resolved_document_root = realpath($document_root);
            $document_root_path = false === $resolved_document_root ? rtrim($document_root, '/\\') : $resolved_document_root;

            if (is_dir($document_root_path)) {
                $parent_directory_path = dirname($document_root_path);
                if (is_file($parent_directory_path.'/global.php') || is_file($parent_directory_path.'/.router')) {
                    return $parent_directory_path;
                }

                return $document_root_path;
            }
        }

        $working_directory_path = getcwd();
        if (is_string($working_directory_path) && ! harbor_is_blank($working_directory_path)) {
            return $working_directory_path;
        }

        return dirname(__DIR__, 2);
    }
}
