<?php

declare(strict_types=1);

namespace Harbor\CommandSystem;

require_once __DIR__.'/../../../src/Support/value.php';

require_once __DIR__.'/../shared/harbor_site.php';

use function Harbor\Support\harbor_is_blank;

abstract class BaseCommand
{
    /** @var array<int, string> */
    private const RESERVED_KEYS = [
        'create',
        'run',
        'compile',
        'list',
        'show',
        'delete',
        'update',
        'help',
    ];

    public function __construct(private readonly bool $debug_mode = false) {}

    protected function info(string $message): void
    {
        fwrite(STDOUT, $message.PHP_EOL);
    }

    protected function error(string $message): void
    {
        fwrite(STDERR, $message.PHP_EOL);
    }

    protected function debug(string $message): void
    {
        if (! $this->debug_mode) {
            return;
        }

        fwrite(STDERR, sprintf('[debug] %s%s', $message, PHP_EOL));
    }

    protected function source_path(string $working_directory): string
    {
        return $working_directory.'/.commands';
    }

    protected function commands_directory_path(string $working_directory): string
    {
        return $working_directory.'/commands';
    }

    protected function registry_path(string $working_directory): string
    {
        return $working_directory.'/commands/commands.php';
    }

    protected function assert_site_selected(string $working_directory): void
    {
        \harbor_site_assert_selected($working_directory);
    }

    protected function assert_valid_key(string $key): void
    {
        if (harbor_is_blank($key)) {
            throw new CommandException('Missing command key.', 2);
        }

        if (1 !== preg_match('/^[a-z0-9_-]+(?::[a-z0-9_-]+)*$/', $key)) {
            throw new CommandException('Invalid command key format. Use lowercase letters, numbers, "_", "-", and ":".', 4);
        }

        if (in_array($key, self::RESERVED_KEYS, true)) {
            throw new CommandException(sprintf('Reserved command key cannot be used: %s', $key), 4);
        }
    }

    protected function ensure_directory_exists(string $directory_path): void
    {
        if (is_dir($directory_path)) {
            return;
        }

        if (file_exists($directory_path)) {
            throw new CommandException(sprintf('Expected directory path but found file: %s', $directory_path), 4);
        }

        if (! mkdir($directory_path, 0o777, true) && ! is_dir($directory_path)) {
            throw new CommandException(sprintf('Failed to create directory: %s', $directory_path), 1);
        }
    }

    protected function resolve_entry_path(string $entry_path, string $working_directory): string
    {
        if ($this->is_absolute_path($entry_path)) {
            return $entry_path;
        }

        return $working_directory.'/'.ltrim($entry_path, '/\\');
    }

    protected function is_absolute_path(string $path): bool
    {
        return 1 === preg_match('#^([a-zA-Z]:[\\\/]|/)#', $path);
    }

    protected function read_file(string $path): string
    {
        $content = file_get_contents($path);
        if (false === $content) {
            throw new CommandException(sprintf('Failed to read file: %s', $path), 1);
        }

        return $content;
    }

    protected function write_file(string $path, string $content): void
    {
        $written = file_put_contents($path, $content);
        if (false === $written) {
            throw new CommandException(sprintf('Failed to write file: %s', $path), 1);
        }
    }

    protected function resolve_php_binary_path(): string
    {
        if (! harbor_is_blank(PHP_BINARY)) {
            return PHP_BINARY;
        }

        return 'php';
    }
}
