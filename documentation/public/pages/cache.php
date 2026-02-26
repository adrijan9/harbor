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
    <p>Use array, file, or APC cache helpers directly, or use the optional driver resolver for one unified cache API.</p>
</section>

<section class="docs-section">
    <h2>Load Helpers</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\HelperLoader;

HelperLoader::load('cache');      // loads cache resolver + cache_array + cache_file + cache_apc
HelperLoader::load('cache_array'); // loads only array cache helpers
HelperLoader::load('cache_file');  // loads only file cache helpers
HelperLoader::load('cache_apc');   // loads only APC cache helpers</code></pre>
    <h3>What it does</h3>
    <p>Registers cache helper functions under <code>Harbor\Cache</code>. You can call <code>cache_array_*</code>, <code>cache_file_*</code>, and <code>cache_apc_*</code> directly without any resolver config.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Cache Loader API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">HelperLoader::load(string $helper_name): void
// Loads helper functions by module name.
// Use "cache", "cache_array", "cache_file", or "cache_apc".
HelperLoader::load('cache');</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Resolver Setup (Required)</h2>
    <p>Skip this section if you are using <code>cache_array_*</code>, <code>cache_file_*</code>, or <code>cache_apc_*</code> directly. This setup is only for the resolver helpers (<code>cache_set</code>, <code>cache_get</code>, <code>cache_has</code>, etc.).</p>
    <h3>1. Create <code>config/cache.php</code></h3>
    <pre><code class="language-php">&lt;?php

declare(strict_types=1);

use Harbor\Cache\CacheDriver;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Cache Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "array", "file", "apc"
    | - array: in-memory cache for current PHP process
    | - file: serialized cache files under file_path
    | - apc: APCu shared memory cache
    |
    */
    'driver' => CacheDriver::FILE->value, // or CacheDriver::APC->value

    /*
    |--------------------------------------------------------------------------
    | File Cache Path
    |--------------------------------------------------------------------------
    |
    | Used when driver is "file".
    | Keep this path writable by PHP.
    |
    */
    'file_path' => __DIR__.'/../cache',
];</code></pre>
    <h3>2. Load It In Bootstrap</h3>
    <pre><code class="language-php">use function Harbor\Config\config_init;

// Example: in public/index.php before using cache_* resolver helpers
config_init(__DIR__.'/../config/cache.php');</code></pre>
    <h3>3. Use Resolver Helpers</h3>
    <pre><code class="language-php">use function Harbor\Cache\cache_get;
use function Harbor\Cache\cache_set;

cache_set('profile:1', ['id' => 1], 300);
$profile = cache_get('profile:1');</code></pre>
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
    <h2>Driver Resolver Cache</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Cache\cache_get;
use function Harbor\Cache\cache_set;

// config/cache.php must be loaded via config_init(...) first
cache_set('profile:1', ['id' => 1], 300);
$profile = cache_get('profile:1');</code></pre>
    <h3>What it does</h3>
    <p>Resolves the active cache backend from <code>cache.driver</code> (loaded from <code>config/cache.php</code> via <code>config_init()</code>). You can switch between <code>array</code> and <code>file</code> without changing cache call sites.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Driver Resolver API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">use Harbor\Cache\CacheDriver;

function cache_driver(string|CacheDriver $default_driver = CacheDriver::ARRAY): string
// Returns active driver from cache.driver config.
// Falls back to $default_driver when config is missing/invalid.
$driver = cache_driver();

function cache_is_array(): bool
// True when resolved cache driver is "array".
$is_array = cache_is_array();

function cache_is_file(): bool
// True when resolved cache driver is "file".
$is_file = cache_is_file();

function cache_is_apc(): bool
// True when resolved cache driver is "apc".
$is_apc = cache_is_apc();

function cache_set(string $key, mixed $value, int $ttl_seconds = 0): bool
// Stores using the resolved driver (array, file, or apc).
cache_set('profile:1', ['id' => 1], 300);

function cache_get(string $key, mixed $default = null): mixed
// Reads using the resolved driver.
$profile = cache_get('profile:1', []);

function cache_has(string $key): bool
// Checks key existence using the resolved driver.
$exists = cache_has('profile:1');

function cache_delete(string $key): bool
// Deletes one key from the resolved driver.
$deleted = cache_delete('profile:1');

function cache_clear(): bool
// Clears entries only on the currently resolved driver.
cache_clear();

function cache_all(): array
// Returns all non-expired entries from the resolved driver.
$all = cache_all();

function cache_count(): int
// Returns count of non-expired entries on the resolved driver.
$total = cache_count();</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>APC Cache</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Cache\cache_apc_get;
use function Harbor\Cache\cache_apc_set;

cache_apc_set('feature.flags', ['new_nav' => true], 120);
$flags = cache_apc_get('feature.flags', []);</code></pre>
    <h3>What it does</h3>
    <p>Stores cache values in APCu shared memory. Requires APCu extension enabled in your PHP runtime.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>APC Cache API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function cache_apc_available(): bool
// Returns true when APCu extension is available/enabled.
$available = cache_apc_available();

function cache_apc_set(string $key, mixed $value, int $ttl_seconds = 0): bool
// Stores value in APCu cache.
cache_apc_set('feature.flags', ['new_nav' => true], 120);

function cache_apc_get(string $key, mixed $default = null): mixed
// Returns cached value or default when missing/expired.
$flags = cache_apc_get('feature.flags', []);

function cache_apc_has(string $key): bool
// Checks if key exists and is not expired.
$exists = cache_apc_has('feature.flags');

function cache_apc_delete(string $key): bool
// Deletes one key and returns true when key existed.
$deleted = cache_apc_delete('feature.flags');

function cache_apc_clear(): bool
// Clears all Harbor APC cache entries.
cache_apc_clear();

function cache_apc_all(): array
// Returns all non-expired APC cache entries.
$all = cache_apc_all();

function cache_apc_count(): int
// Returns count of non-expired APC cache entries.
$total = cache_apc_count();</code></pre>
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
    <p>Stores serialized cache payloads under <code>cache.file_path</code> from <code>config/cache.php</code> when loaded with <code>config_init()</code>. If not configured, fallback is the loaded <code>global.php</code> directory <code>/cache</code> (site root), then project-root <code>cache/</code>.</p>
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
// Resets runtime override and uses cache.file_path config.
// Fallback: global.php directory /cache, then project /cache.
cache_file_reset_path();</code></pre>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
