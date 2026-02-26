<?php

declare(strict_types=1);

namespace Harbor\Cache;

require_once __DIR__.'/../Config/config.php';

require_once __DIR__.'/../Filesystem/filesystem.php';

require_once __DIR__.'/../Support/value.php';

use function Harbor\Config\config_get;
use function Harbor\Config\config_global_directory_path;
use function Harbor\Filesystem\fs_delete;
use function Harbor\Filesystem\fs_dir_create;
use function Harbor\Filesystem\fs_dir_delete;
use function Harbor\Filesystem\fs_dir_exists;
use function Harbor\Filesystem\fs_dir_is_empty;
use function Harbor\Filesystem\fs_dir_list;
use function Harbor\Filesystem\fs_exists;
use function Harbor\Filesystem\fs_read;
use function Harbor\Filesystem\fs_write;
use function Harbor\Support\harbor_is_blank;
use function Harbor\Support\harbor_is_null;

$cache_file_runtime_path = null;

function cache_file_set_path(string $path): void
{
    global $cache_file_runtime_path;

    $cache_file_runtime_path = cache_file_normalize_path($path);
}

function cache_file_reset_path(): void
{
    global $cache_file_runtime_path;

    $cache_file_runtime_path = null;
}

function cache_file_set_root_path(string $root_path): void
{
    cache_file_set_path($root_path);
}

function cache_file_reset_root_path(): void
{
    cache_file_reset_path();
}

function cache_file_set(string $key, mixed $value, int $ttl_seconds = 0): bool
{
    $normalized_key = cache_file_normalize_key($key);
    $cache_file_path = cache_file_path_for_key($normalized_key);
    $cache_directory_path = dirname($cache_file_path);

    cache_file_create_directory($cache_directory_path);

    $cache_payload = [
        'key' => $normalized_key,
        'value' => $value,
        'expires_at' => cache_file_expiration_timestamp($ttl_seconds),
    ];

    fs_write($cache_file_path, serialize($cache_payload));

    return true;
}

function cache_file_get(string $key, mixed $default = null): mixed
{
    $normalized_key = cache_file_normalize_key($key);
    $cache_file_path = cache_file_path_for_key($normalized_key);

    if (! fs_exists($cache_file_path)) {
        return $default;
    }

    $cache_item = cache_file_read_item($cache_file_path);
    if (! cache_file_item_is_valid($cache_item) || $cache_item['key'] !== $normalized_key || cache_file_item_is_expired($cache_item)) {
        cache_file_delete_file_path($cache_file_path);
        cache_file_cleanup_empty_directories(dirname($cache_file_path));

        return $default;
    }

    return $cache_item['value'];
}

function cache_file_has(string $key): bool
{
    $normalized_key = cache_file_normalize_key($key);
    $cache_file_path = cache_file_path_for_key($normalized_key);

    if (! fs_exists($cache_file_path)) {
        return false;
    }

    $cache_item = cache_file_read_item($cache_file_path);
    if (! cache_file_item_is_valid($cache_item) || $cache_item['key'] !== $normalized_key || cache_file_item_is_expired($cache_item)) {
        cache_file_delete_file_path($cache_file_path);
        cache_file_cleanup_empty_directories(dirname($cache_file_path));

        return false;
    }

    return true;
}

function cache_file_delete(string $key): bool
{
    $normalized_key = cache_file_normalize_key($key);
    $cache_file_path = cache_file_path_for_key($normalized_key);

    if (! fs_exists($cache_file_path)) {
        return false;
    }

    cache_file_delete_file_path($cache_file_path);
    cache_file_cleanup_empty_directories(dirname($cache_file_path));

    return true;
}

function cache_file_clear(): bool
{
    $cache_root_path = cache_file_ensure_root_directory();
    $entries = fs_dir_list($cache_root_path, true);

    foreach ($entries as $entry) {
        if ('.gitignore' === basename($entry)) {
            continue;
        }

        cache_file_delete_path($entry);
    }

    cache_file_ensure_gitignore($cache_root_path);

    return true;
}

function cache_file_all(): array
{
    $cache_file_paths = cache_file_list_cache_paths();
    $cache_values = [];

    foreach ($cache_file_paths as $cache_file_path) {
        $cache_item = cache_file_read_item($cache_file_path);
        if (! cache_file_item_is_valid($cache_item) || cache_file_item_is_expired($cache_item)) {
            cache_file_delete_file_path($cache_file_path);
            cache_file_cleanup_empty_directories(dirname($cache_file_path));

            continue;
        }

        $cache_values[$cache_item['key']] = $cache_item['value'];
    }

    return $cache_values;
}

function cache_file_count(): int
{
    return count(cache_file_all());
}

function cache_file_path_for_key(string $key): string
{
    $cache_key_hash = sha1($key);
    $cache_root_path = cache_file_ensure_root_directory();
    $first_directory = substr($cache_key_hash, 0, 2);
    $second_directory = substr($cache_key_hash, 2, 2);
    $file_name = substr($cache_key_hash, 4).'.cache';

    return $cache_root_path.'/'.$first_directory.'/'.$second_directory.'/'.$file_name;
}

function cache_file_list_cache_paths(): array
{
    $cache_root_path = cache_file_ensure_root_directory();
    $cache_file_paths = cache_file_collect_cache_paths($cache_root_path);

    sort($cache_file_paths);

    return $cache_file_paths;
}

function cache_file_collect_cache_paths(string $directory_path): array
{
    $entries = fs_dir_list($directory_path, true);

    $cache_file_paths = [];

    foreach ($entries as $entry_path) {
        if ('.gitignore' === basename($entry_path)) {
            continue;
        }

        if (fs_dir_exists($entry_path)) {
            $cache_file_paths = array_merge($cache_file_paths, cache_file_collect_cache_paths($entry_path));

            continue;
        }

        if (fs_exists($entry_path) && str_ends_with($entry_path, '.cache')) {
            $cache_file_paths[] = $entry_path;
        }
    }

    return $cache_file_paths;
}

function cache_file_read_item(string $cache_file_path): mixed
{
    $cache_content = fs_read($cache_file_path);

    return cache_file_unserialize($cache_content);
}

function cache_file_unserialize(string $cache_content): mixed
{
    set_error_handler(static fn (): bool => true);

    try {
        return unserialize($cache_content, ['allowed_classes' => true]);
    } finally {
        restore_error_handler();
    }
}

function cache_file_item_is_valid(mixed $cache_item): bool
{
    if (! is_array($cache_item)) {
        return false;
    }

    if (
        ! array_key_exists('key', $cache_item)
        || ! is_string($cache_item['key'])
        || harbor_is_blank($cache_item['key'])
        || ! array_key_exists('value', $cache_item)
        || ! array_key_exists('expires_at', $cache_item)
    ) {
        return false;
    }

    $expires_at = $cache_item['expires_at'];

    if (harbor_is_null($expires_at)) {
        return true;
    }

    return is_int($expires_at);
}

function cache_file_item_is_expired(array $cache_item): bool
{
    $expires_at = $cache_item['expires_at'] ?? null;

    if (harbor_is_null($expires_at)) {
        return false;
    }

    if (! is_int($expires_at)) {
        return true;
    }

    return $expires_at <= time();
}

function cache_file_expiration_timestamp(int $ttl_seconds): ?int
{
    if ($ttl_seconds <= 0) {
        return null;
    }

    return time() + $ttl_seconds;
}

function cache_file_normalize_key(string $key): string
{
    $normalized_key = trim($key);

    if (harbor_is_blank($normalized_key)) {
        throw new \InvalidArgumentException('Cache key cannot be empty.');
    }

    return $normalized_key;
}

function cache_file_root_path(): string
{
    global $cache_file_runtime_path;

    if (is_string($cache_file_runtime_path) && ! harbor_is_blank($cache_file_runtime_path)) {
        return $cache_file_runtime_path;
    }

    $configured_path = cache_file_configured_path();
    if (! harbor_is_null($configured_path)) {
        return $configured_path;
    }

    $global_directory_path = config_global_directory_path();
    if (! harbor_is_null($global_directory_path)) {
        return $global_directory_path.'/cache';
    }

    return dirname(__DIR__, 2).'/cache';
}

function cache_file_configured_path(): ?string
{
    $configured_path = config_get('cache.file_path');

    if (! is_string($configured_path) || harbor_is_blank($configured_path)) {
        $configured_path = config_get('cache_file_path');
    }

    if (! is_string($configured_path) || harbor_is_blank($configured_path)) {
        return null;
    }

    return cache_file_normalize_path($configured_path);
}

function cache_file_normalize_path(string $path): string
{
    $normalized_path = rtrim(trim($path), '/\\');

    if (harbor_is_blank($normalized_path)) {
        throw new \InvalidArgumentException('Cache path cannot be empty.');
    }

    return $normalized_path;
}

function cache_file_ensure_root_directory(): string
{
    $cache_root_path = cache_file_root_path();

    cache_file_create_directory($cache_root_path);
    cache_file_ensure_gitignore($cache_root_path);

    return $cache_root_path;
}

function cache_file_create_directory(string $directory_path): void
{
    if (fs_dir_exists($directory_path)) {
        return;
    }

    fs_dir_create($directory_path);
}

function cache_file_ensure_gitignore(string $cache_root_path): void
{
    $gitignore_path = $cache_root_path.'/.gitignore';
    if (fs_exists($gitignore_path)) {
        return;
    }

    fs_write($gitignore_path, "*\n!.gitignore\n");
}

function cache_file_delete_path(string $path): void
{
    if (fs_exists($path)) {
        cache_file_delete_file_path($path);

        return;
    }

    if (! fs_dir_exists($path)) {
        return;
    }

    fs_dir_delete($path, true);
}

function cache_file_delete_file_path(string $cache_file_path): void
{
    if (! fs_exists($cache_file_path)) {
        return;
    }

    fs_delete($cache_file_path);
}

function cache_file_cleanup_empty_directories(string $directory_path): void
{
    $cache_root_path = cache_file_ensure_root_directory();
    $current_directory_path = $directory_path;

    while ($current_directory_path !== $cache_root_path && str_starts_with($current_directory_path, $cache_root_path)) {
        if (! fs_dir_exists($current_directory_path)) {
            $current_directory_path = dirname($current_directory_path);

            continue;
        }

        if (! fs_dir_is_empty($current_directory_path)) {
            return;
        }

        try {
            fs_dir_delete($current_directory_path);
        } catch (\RuntimeException) {
            return;
        }

        $current_directory_path = dirname($current_directory_path);
    }
}
