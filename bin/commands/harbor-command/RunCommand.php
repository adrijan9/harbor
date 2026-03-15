<?php

declare(strict_types=1);

namespace Harbor\CommandSystem;

require_once __DIR__.'/../../../src/Support/value.php';

use function Harbor\Support\harbor_is_blank;

final class RunCommand extends BaseCommand
{
    public function __construct(bool $debug_mode, private readonly CommandCompiler $compiler)
    {
        parent::__construct($debug_mode);
    }

    /**
     * @param array<int, string> $forwarded_arguments
     */
    public function execute(string $key, array $forwarded_arguments, string $working_directory): int
    {
        $this->assert_site_selected($working_directory);
        $this->assert_valid_key($key);

        $source_path = $this->source_path($working_directory);
        $registry_path = $this->registry_path($working_directory);

        if (! is_file($registry_path)) {
            if (! is_file($source_path)) {
                throw new CommandException('No command registry found. Run "./bin/harbor-command create <key>" first.', 3);
            }

            $this->debug('Compiled command registry missing. Rebuilding from .commands.');
            $this->compiler->compile($source_path, $registry_path);
        }

        $registry = require $registry_path;
        if (! is_array($registry)) {
            throw new CommandException(sprintf('Invalid command registry format in file: %s', $registry_path), 4);
        }

        $command_definition = $registry[$key] ?? null;
        if (! is_array($command_definition)) {
            throw new CommandException(sprintf('Command key not found: %s', $key), 3);
        }

        $enabled = $this->normalize_enabled($command_definition['enabled'] ?? true);
        if (! $enabled) {
            throw new CommandException(sprintf('Command is disabled: %s', $key), 4);
        }

        $entry_path = $command_definition['entry'] ?? null;
        if (! is_string($entry_path) || harbor_is_blank($entry_path)) {
            throw new CommandException(sprintf('Invalid entry for command key: %s', $key), 4);
        }

        $resolved_entry_path = $this->resolve_entry_path($entry_path, $working_directory);
        if (! is_file($resolved_entry_path)) {
            throw new CommandException(sprintf('Command entry file not found: %s', $resolved_entry_path), 4);
        }

        $timeout_seconds = $this->normalize_timeout_seconds($command_definition['timeout_seconds'] ?? null);

        $process_command = array_merge([$this->resolve_php_binary_path(), $resolved_entry_path], $forwarded_arguments);
        $this->debug(sprintf('Executing command key: %s', $key));
        $this->debug(sprintf('Resolved entry path: %s', $resolved_entry_path));

        if (is_int($timeout_seconds)) {
            $this->debug(sprintf('Timeout configured: %d second(s)', $timeout_seconds));
        }

        return $this->execute_process($process_command, $working_directory, $timeout_seconds);
    }

    private function normalize_enabled(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ['true', '1', 'yes', 'on'], true)) {
                return true;
            }

            if (in_array($normalized, ['false', '0', 'no', 'off'], true)) {
                return false;
            }
        }

        if (is_int($value)) {
            return 1 === $value;
        }

        return false;
    }

    private function normalize_timeout_seconds(mixed $value): ?int
    {
        if (null === $value) {
            return null;
        }

        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (! is_string($value) || harbor_is_blank($value)) {
            return null;
        }

        if (1 !== preg_match('/^[0-9]+$/', $value)) {
            return null;
        }

        $timeout_seconds = (int) $value;

        return $timeout_seconds > 0 ? $timeout_seconds : null;
    }

    /**
     * @param array<int, string> $command
     */
    private function execute_process(array $command, string $working_directory, ?int $timeout_seconds): int
    {
        $descriptors = [
            0 => ['file', 'php://stdin', 'r'],
            1 => ['file', 'php://stdout', 'w'],
            2 => ['file', 'php://stderr', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, $working_directory);
        if (! is_resource($process)) {
            throw new CommandException('Failed to execute command process.', 1);
        }

        if (null === $timeout_seconds) {
            return proc_close($process);
        }

        $started_at = microtime(true);

        while (true) {
            $status = proc_get_status($process);
            if (! is_array($status)) {
                break;
            }

            $is_running = $status['running'] ?? false;
            if (! $is_running) {
                break;
            }

            $elapsed = microtime(true) - $started_at;
            if ($elapsed >= $timeout_seconds) {
                proc_terminate($process);
                proc_close($process);

                throw new CommandException(sprintf('Command timed out after %d second(s).', $timeout_seconds), 1);
            }

            usleep(100000);
        }

        return proc_close($process);
    }
}
