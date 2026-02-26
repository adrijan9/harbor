<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Cache Helpers';
$page_description = 'Array and file cache helpers with matching set/get/delete style APIs.';
$page_id = 'cache';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Helpers</span>
    <h1>Cache Helpers</h1>
    <p>Use in-memory array cache or file-backed cache with the same helper shape.</p>
</section>

<section class="docs-section">
    <h2>Load Helpers</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\HelperLoader;

HelperLoader::load('cache');      // loads cache_array + cache_file
HelperLoader::load('cache_array'); // loads only array cache helpers
HelperLoader::load('cache_file');  // loads only file cache helpers</code></pre>
    <h3>What it does</h3>
    <p>Registers cache helper functions under <code>Harbor\Cache</code>.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Cache Loader API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">HelperLoader::load(string $helper_name): void
// Loads helper functions by module name.
// Use "cache", "cache_array", or "cache_file".
HelperLoader::load('cache');</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Array Cache</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Cache\cache_array_get;
use function Harbor\Cache\cache_array_set;

cache_array_set('user:1', ['id' => 1, 'name' => 'Ada']);
$user = cache_array_get('user:1');</code></pre>
    <h3>What it does</h3>
    <p>Stores cache values in-memory for the current process lifetime.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Array Cache API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function cache_array_set(string $key, mixed $value, int $ttl_seconds = 0): bool
// Stores value in array cache.
// ttl_seconds <= 0 keeps the value until deleted/cleared.
cache_array_set('user:1', ['id' => 1], 60);

function cache_array_get(string $key, mixed $default = null): mixed
// Returns cached value or default when missing/expired.
$user = cache_array_get('user:1', []);

function cache_array_has(string $key): bool
// Checks if key exists and is not expired.
$exists = cache_array_has('user:1');

function cache_array_delete(string $key): bool
// Deletes one key and returns true when key existed.
$deleted = cache_array_delete('user:1');

function cache_array_clear(): bool
// Removes all in-memory cache entries.
cache_array_clear();

function cache_array_all(): array
// Returns all non-expired entries.
$all = cache_array_all();

function cache_array_count(): int
// Returns count of non-expired entries.
$total = cache_array_count();</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>File Cache</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Cache\cache_file_clear;
use function Harbor\Cache\cache_file_get;
use function Harbor\Cache\cache_file_set;

cache_file_set('build.meta', ['sha' => 'abc123'], 300);
$meta = cache_file_get('build.meta');

// remove all file cache entries from project /cache directory
cache_file_clear();</code></pre>
    <h3>What it does</h3>
    <p>Stores serialized cache payloads under <code>cache_file_path</code> from <code>global.php</code> (fallback: project-root <code>cache/</code>) using hash-based nested folders and file names.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>File Cache API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function cache_file_set(string $key, mixed $value, int $ttl_seconds = 0): bool
// Stores value as serialized payload in /cache.
// Hash-based nested folders and file names reduce path conflicts.
cache_file_set('build.meta', ['sha' => 'abc123'], 300);

function cache_file_get(string $key, mixed $default = null): mixed
// Returns cached value or default when missing/expired.
$meta = cache_file_get('build.meta', []);

function cache_file_has(string $key): bool
// Checks if key exists and is not expired.
$exists = cache_file_has('build.meta');

function cache_file_delete(string $key): bool
// Deletes one cached key and returns true when key existed.
$deleted = cache_file_delete('build.meta');

function cache_file_clear(): bool
// Removes all cache files/folders from /cache while keeping /cache/.gitignore.
cache_file_clear();

function cache_file_all(): array
// Returns all non-expired file cache entries.
$all = cache_file_all();

function cache_file_count(): int
// Returns count of non-expired file cache entries.
$total = cache_file_count();

function cache_file_set_path(string $path): void
// Overrides cache path at runtime.
cache_file_set_path(__DIR__.'/tmp/cache');

function cache_file_reset_path(): void
// Resets runtime override and uses cache_file_path from global.php (fallback: project /cache).
cache_file_reset_path();</code></pre>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
