<?php

declare(strict_types=1);

namespace PhpFramework\Filesystem;

function fs_read(string $file_path): string
{
    if (! fs_exists($file_path)) {
        throw new \RuntimeException(sprintf('File "%s" not found.', $file_path));
    }

    $content = file_get_contents($file_path);
    if (false === $content) {
        throw new \RuntimeException(sprintf('Failed to read file "%s".', $file_path));
    }

    return $content;
}

function fs_write(string $file_path, string $content): int
{
    $directory_path = dirname($file_path);

    if (! fs_dir_exists($directory_path)) {
        throw new \RuntimeException(sprintf('Directory "%s" not found.', $directory_path));
    }

    $written_bytes = file_put_contents($file_path, $content);
    if (false === $written_bytes) {
        throw new \RuntimeException(sprintf('Failed to write file "%s".', $file_path));
    }

    return $written_bytes;
}

function fs_append(string $file_path, string $content): int
{
    $directory_path = dirname($file_path);

    if (! fs_dir_exists($directory_path)) {
        throw new \RuntimeException(sprintf('Directory "%s" not found.', $directory_path));
    }

    $written_bytes = file_put_contents($file_path, $content, FILE_APPEND);
    if (false === $written_bytes) {
        throw new \RuntimeException(sprintf('Failed to append file "%s".', $file_path));
    }

    return $written_bytes;
}

function fs_delete(string $file_path): bool
{
    if (! fs_exists($file_path)) {
        throw new \RuntimeException(sprintf('File "%s" not found.', $file_path));
    }

    if (! unlink($file_path)) {
        throw new \RuntimeException(sprintf('Failed to delete file "%s".', $file_path));
    }

    return true;
}

function fs_exists(string $file_path): bool
{
    return is_file($file_path);
}

function fs_copy(string $source_path, string $destination_path): bool
{
    if (! fs_exists($source_path)) {
        throw new \RuntimeException(sprintf('File "%s" not found.', $source_path));
    }

    $destination_directory_path = dirname($destination_path);
    if (! fs_dir_exists($destination_directory_path)) {
        throw new \RuntimeException(sprintf('Directory "%s" not found.', $destination_directory_path));
    }

    if (! copy($source_path, $destination_path)) {
        throw new \RuntimeException(sprintf('Failed to copy file "%s" to "%s".', $source_path, $destination_path));
    }

    return true;
}

function fs_move(string $source_path, string $destination_path): bool
{
    if (! fs_exists($source_path)) {
        throw new \RuntimeException(sprintf('File "%s" not found.', $source_path));
    }

    $destination_directory_path = dirname($destination_path);
    if (! fs_dir_exists($destination_directory_path)) {
        throw new \RuntimeException(sprintf('Directory "%s" not found.', $destination_directory_path));
    }

    if (! rename($source_path, $destination_path)) {
        throw new \RuntimeException(sprintf('Failed to move file "%s" to "%s".', $source_path, $destination_path));
    }

    return true;
}

function fs_size(string $file_path): int
{
    if (! fs_exists($file_path)) {
        throw new \RuntimeException(sprintf('File "%s" not found.', $file_path));
    }

    $size = filesize($file_path);
    if (false === $size) {
        throw new \RuntimeException(sprintf('Failed to read file size for "%s".', $file_path));
    }

    return $size;
}

function fs_dir_exists(string $directory_path): bool
{
    return is_dir($directory_path);
}

function fs_dir_create(string $directory_path, int $permissions = 0o777, bool $recursive = true): bool
{
    if (fs_dir_exists($directory_path)) {
        return true;
    }

    if (! mkdir($directory_path, $permissions, $recursive) && ! fs_dir_exists($directory_path)) {
        throw new \RuntimeException(sprintf('Failed to create directory "%s".', $directory_path));
    }

    return true;
}

function fs_dir_is_empty(string $directory_path): bool
{
    if (! fs_dir_exists($directory_path)) {
        throw new \RuntimeException(sprintf('Directory "%s" not found.', $directory_path));
    }

    $entries = scandir($directory_path);
    if (false === $entries) {
        throw new \RuntimeException(sprintf('Failed to read directory "%s".', $directory_path));
    }

    return 2 === count($entries);
}

function fs_dir_list(string $directory_path, bool $absolute_paths = false): array
{
    if (! fs_dir_exists($directory_path)) {
        throw new \RuntimeException(sprintf('Directory "%s" not found.', $directory_path));
    }

    $entries = scandir($directory_path);
    if (false === $entries) {
        throw new \RuntimeException(sprintf('Failed to read directory "%s".', $directory_path));
    }

    $items = array_values(array_filter(
        $entries,
        static fn (string $entry): bool => '.' !== $entry && '..' !== $entry
    ));

    sort($items);

    if (! $absolute_paths) {
        return $items;
    }

    return array_map(
        static fn (string $entry): string => rtrim($directory_path, '/').'/'.$entry,
        $items
    );
}

function fs_dir_delete(string $directory_path, bool $recursive = false): bool
{
    if (! fs_dir_exists($directory_path)) {
        throw new \RuntimeException(sprintf('Directory "%s" not found.', $directory_path));
    }

    if (! $recursive && ! fs_dir_is_empty($directory_path)) {
        throw new \RuntimeException(sprintf('Directory "%s" is not empty.', $directory_path));
    }

    if ($recursive) {
        $entries = fs_dir_list($directory_path, true);

        foreach ($entries as $entry_path) {
            if (is_dir($entry_path)) {
                fs_dir_delete($entry_path, true);

                continue;
            }

            if (! unlink($entry_path)) {
                throw new \RuntimeException(sprintf('Failed to delete file "%s".', $entry_path));
            }
        }
    }

    if (! rmdir($directory_path)) {
        throw new \RuntimeException(sprintf('Failed to delete directory "%s".', $directory_path));
    }

    return true;
}
