<?php

declare(strict_types=1);

$exception_payload = json_decode((string) ($_EXCEPTION['FULL'] ?? '{}'), true);
$exception_payload = is_array($exception_payload) ? $exception_payload : [];
$trace = $exception_payload['trace'] ?? [];
$trace = is_array($trace) ? $trace : [];
$first_trace_file = is_array($trace[0] ?? null) && is_string($trace[0]['file'] ?? null)
    ? $trace[0]['file']
    : 'missing-trace';

echo sprintf(
    'Exception Page | %s | %s | %s | %s | %s',
    (string) ($exception_payload['type'] ?? 'unknown-type'),
    (string) ($exception_payload['message'] ?? 'unknown-message'),
    (string) ($exception_payload['file'] ?? 'unknown-file'),
    (string) ($exception_payload['line'] ?? 'unknown-line'),
    $first_trace_file
);
