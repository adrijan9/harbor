<?php

declare(strict_types=1);

namespace Harbor\Session;

require_once __DIR__.'/../Config/config.php';

require_once __DIR__.'/../Cookie/cookie.php';

require_once __DIR__.'/../Filesystem/filesystem.php';

require_once __DIR__.'/../Support/value.php';

use function Harbor\Config\config_internal_global_directory_path;
use function Harbor\Config\config_resolve;
use function Harbor\Cookie\cookie_forget;
use function Harbor\Cookie\cookie_get;
use function Harbor\Cookie\cookie_set;
use function Harbor\Filesystem\fs_delete;
use function Harbor\Filesystem\fs_dir_create;
use function Harbor\Filesystem\fs_dir_delete;
use function Harbor\Filesystem\fs_dir_exists;
use function Harbor\Filesystem\fs_dir_is_empty;
use function Harbor\Filesystem\fs_exists;
use function Harbor\Filesystem\fs_read;
use function Harbor\Filesystem\fs_write;
use function Harbor\Support\harbor_is_blank;
use function Harbor\Support\harbor_is_null;

$session_file_runtime_path = null;

/** Public */
function session_file_set_path(string $path): void
{
    global $session_file_runtime_path;

    $session_file_runtime_path = session_file_normalize_path($path);
}

function session_file_reset_path(): void
{
    global $session_file_runtime_path;

    $session_file_runtime_path = null;
}

function session_file_set(string $key, mixed $value, int $ttl_seconds, array $cookie_options = []): bool
{
    $normalized_key = session_file_normalize_key($key);
    $session_id = session_file_resolve_or_create_session_id($cookie_options);

    if (null === $session_id) {
        return false;
    }

    $session_payload = session_file_read_payload($session_id);
    session_file_prune_expired_items($session_payload);

    $session_payload['items'][$normalized_key] = [
        'value' => $value,
        'expires_at' => session_file_expiration_timestamp($ttl_seconds),
    ];

    session_file_write_payload($session_id, $session_payload);

    return true;
}

function session_file_get(string $key, mixed $default = null, array $cookie_options = []): mixed
{
    $normalized_key = session_file_normalize_key($key);
    $session_id = session_file_current_session_id($cookie_options);

    if (! is_string($session_id)) {
        return $default;
    }

    $session_payload = session_file_read_payload($session_id);
    $is_dirty = session_file_prune_expired_items($session_payload);

    if (! array_key_exists($normalized_key, $session_payload['items'])) {
        if ($is_dirty) {
            session_file_write_payload($session_id, $session_payload);
        }

        return $default;
    }

    if ($is_dirty) {
        session_file_write_payload($session_id, $session_payload);
    }

    $session_item = $session_payload['items'][$normalized_key];

    return is_array($session_item) && array_key_exists('value', $session_item)
        ? $session_item['value']
        : $default;
}

function session_file_has(string $key, array $cookie_options = []): bool
{
    $sentinel = new \stdClass();

    return $sentinel !== session_file_get($key, $sentinel, $cookie_options);
}

function session_file_forget(string $key, array $cookie_options = []): bool
{
    $normalized_key = session_file_normalize_key($key);
    $session_id = session_file_current_session_id($cookie_options);

    if (! is_string($session_id)) {
        return true;
    }

    $session_payload = session_file_read_payload($session_id);
    session_file_prune_expired_items($session_payload);

    if (! array_key_exists($normalized_key, $session_payload['items'])) {
        return true;
    }

    unset($session_payload['items'][$normalized_key]);

    session_file_write_payload($session_id, $session_payload);

    return true;
}

function session_file_all(array $cookie_options = []): array
{
    $session_id = session_file_current_session_id($cookie_options);

    if (! is_string($session_id)) {
        return [];
    }

    $session_payload = session_file_read_payload($session_id);
    $is_dirty = session_file_prune_expired_items($session_payload);

    if ($is_dirty) {
        session_file_write_payload($session_id, $session_payload);
    }

    $session_values = [];

    foreach ($session_payload['items'] as $session_key => $session_item) {
        if (! is_string($session_key) || ! is_array($session_item) || ! array_key_exists('value', $session_item)) {
            continue;
        }

        $session_values[$session_key] = $session_item['value'];
    }

    return $session_values;
}

function session_file_clear(array $cookie_options = []): bool
{
    $session_id = session_file_current_session_id($cookie_options);

    if (! is_string($session_id)) {
        return true;
    }

    $session_file_path = session_file_path_for_id($session_id);
    if (fs_exists($session_file_path)) {
        session_file_delete_file_path($session_file_path);
        session_file_cleanup_empty_directories(dirname($session_file_path));
    }

    return cookie_forget(session_file_id_cookie_name(), $cookie_options);
}

/** Private */
function session_file_resolve_or_create_session_id(array $cookie_options): ?string
{
    $session_id = session_file_current_session_id($cookie_options);
    if (is_string($session_id)) {
        return $session_id;
    }

    $new_session_id = session_file_generate_session_id();
    $is_set = cookie_set(
        session_file_id_cookie_name(),
        $new_session_id,
        session_ttl_seconds(),
        $cookie_options,
    );

    if (! $is_set) {
        return null;
    }

    return $new_session_id;
}

function session_file_current_session_id(array $cookie_options): ?string
{
    $raw_session_id = cookie_get(session_file_id_cookie_name(), null, $cookie_options);

    if (! is_string($raw_session_id)) {
        return null;
    }

    return session_file_normalize_session_id($raw_session_id);
}

function session_file_generate_session_id(): string
{
    return bin2hex(random_bytes(20));
}

function session_file_read_payload(string $session_id): array
{
    $session_file_path = session_file_path_for_id($session_id);

    if (! fs_exists($session_file_path)) {
        return session_file_empty_payload($session_id);
    }

    $serialized_payload = fs_read($session_file_path);
    $session_payload = session_file_unserialize($serialized_payload);

    if (! session_file_payload_is_valid($session_payload, $session_id)) {
        session_file_delete_file_path($session_file_path);
        session_file_cleanup_empty_directories(dirname($session_file_path));

        return session_file_empty_payload($session_id);
    }

    return $session_payload;
}

function session_file_write_payload(string $session_id, array $session_payload): void
{
    if (empty($session_payload['items'])) {
        $session_file_path = session_file_path_for_id($session_id);

        if (fs_exists($session_file_path)) {
            session_file_delete_file_path($session_file_path);
            session_file_cleanup_empty_directories(dirname($session_file_path));
        }

        return;
    }

    $session_file_path = session_file_path_for_id($session_id);
    $session_file_directory = dirname($session_file_path);

    if (! fs_dir_exists($session_file_directory)) {
        fs_dir_create($session_file_directory);
    }

    fs_write($session_file_path, serialize($session_payload));
}

function session_file_empty_payload(string $session_id): array
{
    return [
        'id' => $session_id,
        'items' => [],
    ];
}

function session_file_payload_is_valid(mixed $session_payload, string $session_id): bool
{
    if (! is_array($session_payload)) {
        return false;
    }

    if (! array_key_exists('id', $session_payload) || ! is_string($session_payload['id']) || $session_payload['id'] !== $session_id) {
        return false;
    }

    if (! array_key_exists('items', $session_payload) || ! is_array($session_payload['items'])) {
        return false;
    }

    return true;
}

function session_file_prune_expired_items(array &$session_payload): bool
{
    $is_dirty = false;

    foreach ($session_payload['items'] as $session_key => $session_item) {
        if (! is_string($session_key) || ! session_file_item_is_valid($session_item) || session_file_item_is_expired($session_item)) {
            unset($session_payload['items'][$session_key]);
            $is_dirty = true;
        }
    }

    return $is_dirty;
}

function session_file_item_is_valid(mixed $session_item): bool
{
    if (! is_array($session_item)) {
        return false;
    }

    if (! array_key_exists('value', $session_item) || ! array_key_exists('expires_at', $session_item)) {
        return false;
    }

    $expires_at = $session_item['expires_at'];

    if (harbor_is_null($expires_at)) {
        return true;
    }

    return is_int($expires_at);
}

function session_file_item_is_expired(array $session_item): bool
{
    $expires_at = $session_item['expires_at'] ?? null;

    if (harbor_is_null($expires_at)) {
        return false;
    }

    if (! is_int($expires_at)) {
        return true;
    }

    return $expires_at <= time();
}

function session_file_expiration_timestamp(int $ttl_seconds): ?int
{
    if ($ttl_seconds <= 0) {
        return null;
    }

    return time() + $ttl_seconds;
}

function session_file_path_for_id(string $session_id): string
{
    $session_hash = sha1($session_id);
    $root_path = session_file_ensure_root_directory();
    $first_directory = substr($session_hash, 0, 2);
    $second_directory = substr($session_hash, 2, 2);
    $file_name = substr($session_hash, 4).'.session';

    return $root_path.'/'.$first_directory.'/'.$second_directory.'/'.$file_name;
}

function session_file_root_path(): string
{
    global $session_file_runtime_path;

    if (is_string($session_file_runtime_path) && ! harbor_is_blank($session_file_runtime_path)) {
        return $session_file_runtime_path;
    }

    $configured_path = session_file_configured_path();
    if (! harbor_is_null($configured_path)) {
        return $configured_path;
    }

    $global_directory_path = config_internal_global_directory_path();
    if (! harbor_is_null($global_directory_path)) {
        return $global_directory_path.'/storage/session';
    }

    return dirname(__DIR__, 2).'/storage/session';
}

function session_file_configured_path(): ?string
{
    $configured_path = config_resolve('session.file_path', 'session_path');

    if (! is_string($configured_path)) {
        return null;
    }

    $normalized_path = trim($configured_path);

    if (harbor_is_blank($normalized_path)) {
        return null;
    }

    return session_file_normalize_path($normalized_path);
}

function session_file_id_cookie_name(): string
{
    $configured_cookie_name = config_resolve('session.id_cookie', 'session_id_cookie');

    if (is_string($configured_cookie_name)) {
        $normalized_cookie_name = trim($configured_cookie_name);

        if (! harbor_is_blank($normalized_cookie_name)) {
            return $normalized_cookie_name;
        }
    }

    return session_cookie_prefix().'-session-id';
}

function session_file_normalize_path(string $path): string
{
    $normalized_path = trim($path);

    if (harbor_is_blank($normalized_path)) {
        throw new \InvalidArgumentException('Session file path cannot be empty.');
    }

    return rtrim($normalized_path, '/\\');
}

function session_file_normalize_key(string $key): string
{
    $normalized_key = trim($key);

    if (harbor_is_blank($normalized_key)) {
        throw new \InvalidArgumentException('Session key cannot be empty.');
    }

    return $normalized_key;
}

function session_file_normalize_session_id(string $session_id): ?string
{
    $normalized_session_id = trim($session_id);

    if (harbor_is_blank($normalized_session_id)) {
        return null;
    }

    if (1 === preg_match('/[^A-Za-z0-9]/', $normalized_session_id)) {
        return null;
    }

    return $normalized_session_id;
}

function session_file_ensure_root_directory(): string
{
    $session_root_path = session_file_root_path();

    if (! fs_dir_exists($session_root_path)) {
        fs_dir_create($session_root_path);
    }

    session_file_ensure_gitignore($session_root_path);

    return $session_root_path;
}

function session_file_ensure_gitignore(string $root_path): void
{
    $gitignore_path = $root_path.'/.gitignore';

    if (fs_exists($gitignore_path)) {
        return;
    }

    fs_write($gitignore_path, "*\n!.gitignore\n");
}

function session_file_delete_file_path(string $file_path): void
{
    if (! fs_exists($file_path)) {
        return;
    }

    fs_delete($file_path);
}

function session_file_cleanup_empty_directories(string $directory_path): void
{
    $root_path = session_file_root_path();

    while (str_starts_with($directory_path, $root_path) && $directory_path !== $root_path) {
        if (! fs_dir_exists($directory_path) || ! fs_dir_is_empty($directory_path)) {
            break;
        }

        fs_dir_delete($directory_path);

        $parent_directory = dirname($directory_path);
        if (! is_string($parent_directory) || $parent_directory === $directory_path) {
            break;
        }

        $directory_path = $parent_directory;
    }
}

function session_file_unserialize(string $session_payload): mixed
{
    set_error_handler(static fn (): bool => true);

    try {
        return unserialize($session_payload, ['allowed_classes' => true]);
    } finally {
        restore_error_handler();
    }
}
