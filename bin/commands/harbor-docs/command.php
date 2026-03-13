#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * @param array<int, string> $arguments
 */
function harbor_docs_run(array $arguments): int
{
    if (in_array('--help', $arguments, true) || in_array('-h', $arguments, true)) {
        harbor_docs_print_usage();

        return 0;
    }

    $documentation_root_path = realpath(__DIR__.'/../../../documentation');
    if (false === $documentation_root_path || ! is_dir($documentation_root_path)) {
        fwrite(STDERR, sprintf('Documentation directory not found: %s%s', __DIR__.'/../../../documentation', PHP_EOL));

        return 1;
    }

    $documentation_path = $documentation_root_path.'/public';
    if (! is_dir($documentation_path)) {
        fwrite(STDERR, sprintf('Documentation public directory not found: %s%s', $documentation_path, PHP_EOL));

        return 1;
    }

    $front_controller_path = $documentation_path.'/index.php';
    if (! is_file($front_controller_path)) {
        fwrite(STDERR, sprintf('Documentation front controller not found: %s%s', $front_controller_path, PHP_EOL));

        return 1;
    }

    try {
        $preferred_port = harbor_docs_parse_port_argument($arguments);
    } catch (InvalidArgumentException $exception) {
        fwrite(STDERR, $exception->getMessage().PHP_EOL);

        return 1;
    }

    if (8080 === $preferred_port) {
        fwrite(STDERR, 'Port 8080 is reserved. Use a different port.'.PHP_EOL);

        return 1;
    }

    try {
        $port = harbor_docs_find_available_port($preferred_port);
    } catch (RuntimeException $exception) {
        fwrite(STDERR, $exception->getMessage().PHP_EOL);

        return 1;
    }

    $base_url = sprintf('http://127.0.0.1:%d', $port);

    fwrite(STDOUT, sprintf('Serving documentation from %s%s', $documentation_path, PHP_EOL));
    fwrite(STDOUT, sprintf('Open %s%s', $base_url, PHP_EOL));
    fwrite(STDOUT, 'Press Ctrl+C to stop.'.PHP_EOL);

    $php_binary = '' === PHP_BINARY ? 'php' : PHP_BINARY;
    $command = sprintf(
        '%s -S 127.0.0.1:%d -t %s %s',
        escapeshellarg($php_binary),
        $port,
        escapeshellarg($documentation_path),
        escapeshellarg($front_controller_path),
    );
    passthru($command, $exit_code);

    return $exit_code;
}

function harbor_docs_print_usage(): void
{
    fwrite(STDOUT, 'Usage: harbor-docs [--port=PORT]'.PHP_EOL);
    fwrite(STDOUT, PHP_EOL);
    fwrite(STDOUT, 'Options:'.PHP_EOL);
    fwrite(STDOUT, '  --port=PORT    Preferred starting port (default: 8081, 8080 is never used).'.PHP_EOL);
}

/**
 * @param array<int, string> $arguments
 */
function harbor_docs_parse_port_argument(array $arguments): int
{
    foreach ($arguments as $argument) {
        if (! is_string($argument) || ! str_starts_with($argument, '--port=')) {
            continue;
        }

        $port_value = substr($argument, strlen('--port='));
        if (false === $port_value || ! ctype_digit($port_value)) {
            throw new InvalidArgumentException(sprintf('Invalid port value: %s', $argument));
        }

        $port = (int) $port_value;
        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException(sprintf('Port out of range: %d', $port));
        }

        return $port;
    }

    return 8081;
}

function harbor_docs_find_available_port(int $start_port): int
{
    $max_attempts = 100;
    $last_port = min(65535, $start_port + $max_attempts - 1);

    for ($port = $start_port; $port <= $last_port; ++$port) {
        if (8080 === $port) {
            continue;
        }

        if (harbor_docs_is_port_available($port)) {
            return $port;
        }
    }

    throw new RuntimeException(sprintf('Unable to find an available port between %d and %d.', $start_port, $last_port));
}

function harbor_docs_is_port_available(int $port): bool
{
    $socket = @stream_socket_server(sprintf('tcp://127.0.0.1:%d', $port), $error_number, $error_message);

    if (false === $socket) {
        return false;
    }

    fclose($socket);

    return true;
}

if ('cli' === PHP_SAPI) {
    $script_file = $_SERVER['SCRIPT_FILENAME'] ?? null;
    $resolved_script_file = is_string($script_file) ? realpath($script_file) : false;

    if (is_string($resolved_script_file) && __FILE__ === $resolved_script_file) {
        exit(harbor_docs_run($_SERVER['argv'] ?? []));
    }
}
