<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Filesystem Helpers';
$page_description = 'Filesystem helpers for file and directory operations.';
$page_id = 'filesystem';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">namespace: filesystem</span>
    <h1>Filesystem Helpers</h1>
    <p>Simple file and directory operations with clear errors.</p>
</section>

<section class="docs-section">
    <h2>File Ops</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Filesystem\fs_append;
use function Harbor\Filesystem\fs_read;
use function Harbor\Filesystem\fs_write;

fs_write(__DIR__.'/cache/output.txt', 'Build started');
fs_append(__DIR__.'/cache/output.txt', PHP_EOL.'Build completed');
$content = fs_read(__DIR__.'/cache/output.txt');</code></pre>
    <h3>What it does</h3>
    <p>Creates, updates, reads, and manages files.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>File API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function fs_read(string $file_path): string
// Reads file content and returns it as string.
// Throws RuntimeException if file is missing or unreadable.
$content = fs_read(__DIR__.'/cache/output.txt');

function fs_write(string $file_path, string $content): int
// Writes full content to file.
// Returns number of written bytes.
$bytes = fs_write(__DIR__.'/cache/output.txt', 'Build started');

function fs_append(string $file_path, string $content): int
// Appends content to existing file.
// Returns number of appended bytes.
$bytes = fs_append(__DIR__.'/cache/output.txt', PHP_EOL.'Done');

function fs_exists(string $file_path): bool
// Checks if path points to an existing file.
// Returns true only for files (not directories).
$exists = fs_exists(__DIR__.'/cache/output.txt');

function fs_size(string $file_path): int
// Returns file size in bytes.
// Throws RuntimeException when file is missing.
$size = fs_size(__DIR__.'/cache/output.txt');

function fs_copy(string $source_path, string $destination_path): bool
// Copies source file to destination.
// Throws RuntimeException on missing source/destination directory.
$copied = fs_copy(__DIR__.'/cache/output.txt', __DIR__.'/cache/output.copy.txt');

function fs_move(string $source_path, string $destination_path): bool
// Moves or renames file path.
// Throws RuntimeException on invalid source/destination.
$moved = fs_move(__DIR__.'/cache/output.copy.txt', __DIR__.'/cache/archive/output.txt');

function fs_delete(string $file_path): bool
// Deletes one file path.
// Throws RuntimeException if file does not exist.
$deleted = fs_delete(__DIR__.'/cache/output.txt');</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Directory Ops</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Filesystem\fs_dir_create;
use function Harbor\Filesystem\fs_dir_delete;
use function Harbor\Filesystem\fs_dir_list;

fs_dir_create(__DIR__.'/storage/reports');
$files = fs_dir_list(__DIR__.'/storage/reports');
fs_dir_delete(__DIR__.'/storage/reports', true);</code></pre>
    <h3>What it does</h3>
    <p>Creates, lists, checks, and deletes directories.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Directory API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function fs_dir_exists(string $directory_path): bool
// Checks if directory exists.
// Returns true only for directories.
$exists = fs_dir_exists(__DIR__.'/storage/reports');

function fs_dir_create(string $directory_path, int $permissions = 0o777, bool $recursive = true): bool
// Creates directory path when missing.
// Returns true when created or already exists.
$created = fs_dir_create(__DIR__.'/storage/reports');

function fs_dir_is_empty(string $directory_path): bool
// Checks if directory has no files/subdirectories.
// Throws RuntimeException if directory does not exist.
$empty = fs_dir_is_empty(__DIR__.'/storage/reports');

function fs_dir_list(string $directory_path, bool $absolute_paths = false): array
// Lists directory entries sorted ascending.
// Set $absolute_paths to true for full file paths.
$entries = fs_dir_list(__DIR__.'/storage/reports', true);

function fs_dir_delete(string $directory_path, bool $recursive = false): bool
// Deletes directory path.
// Set $recursive to true to remove non-empty directories.
$deleted = fs_dir_delete(__DIR__.'/storage/reports', true);</code></pre>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
