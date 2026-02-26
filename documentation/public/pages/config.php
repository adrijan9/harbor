<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Config Helpers';
$page_description = 'Load config files and read typed values from runtime environment.';
$page_id = 'config';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Helpers</span>
    <h1>Config Helpers</h1>
    <p>Load one or many config files into <code>$_ENV</code> by file name key, then read typed values.</p>
</section>

<section class="docs-section">
    <h2>Load Helper</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\HelperLoader;

HelperLoader::load('config');</code></pre>
    <h3>What it does</h3>
    <p>Loads config helper functions into the <code>Harbor\Config</code> namespace.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Config Loader API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">HelperLoader::load(string $helper_name): void
// Loads helper module by name.
// Use "config" to register config helpers.
HelperLoader::load('config');</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Init Config Files</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Config\config_init;

config_init(
    __DIR__.'/config/app.php',
    __DIR__.'/config/database.php',
);</code></pre>
    <h3>What it does</h3>
    <p>Loads each file and stores it under its filename key. Example: <code>database.php</code> becomes <code>$_ENV['database']</code>.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Config Init API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function config_init(string ...$config_files): void
// Loads one or many config files that return arrays.
// Stores each file by filename key inside $_ENV.
config_init(__DIR__.'/config/app.php', __DIR__.'/config/database.php');

function config_init_global(string $config_file): void
// Loads one config file and merges values into top-level $_ENV.
// Useful for site-level global.php loaded by Router.
config_init_global(__DIR__.'/../global.php');

function config_all(): array
// Returns full runtime config map from $_ENV.
// Useful for debug snapshots.
$all = config_all();

function config_count(): int
// Returns top-level key count in $_ENV.
// Quick check to verify config was loaded.
$count = config_count();

function config_exists(string $key): bool
// Checks if key exists (supports dot notation).
// Example key: "database.mysql.host".
$has_db_host = config_exists('database.mysql.host');</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Optional Cache Config File</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\Cache\CacheDriver;
use function Harbor\Config\config_init;

// from public/index.php
config_init(__DIR__.'/../config/cache.php');

// file: config/cache.php
return [
    /*
    |--------------------------------------------------------------------------
    | Default Cache Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "array", "file", "apc"
    |
    */
    'driver' => CacheDriver::FILE->value, // or CacheDriver::APC->value

    /*
    |--------------------------------------------------------------------------
    | File Cache Path
    |--------------------------------------------------------------------------
    |
    | Used when driver is "file".
    |
    */
    'file_path' => __DIR__.'/../cache',
];</code></pre>
    <h3>What it does</h3>
    <p>Loads <code>config/cache.php</code> into <code>$_ENV['cache']</code>, so cache resolver helpers can read <code>cache.driver</code> and <code>cache.file_path</code>. Use <code>driver = apc</code> only when APCu is installed and enabled.</p>
</section>

<section class="docs-section">
    <h2>Read Config Values</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Config\config_arr;
use function Harbor\Config\config_bool;
use function Harbor\Config\config_get;
use function Harbor\Config\config_int;
use function Harbor\Config\config_str;

$app_name = config_str('app.app_name', 'Harbor');
$db_port = config_int('database.mysql.port', 3306);
$debug = config_bool('app.debug', false);
$hosts = config_arr('database.hosts', []);
$host = config_get('database.mysql.host', '127.0.0.1');</code></pre>
    <h3>What it does</h3>
    <p>Reads values with typed helpers and defaults. Dot notation is supported for nested arrays.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Config Read API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function config(?string $key = null, mixed $default = null): mixed
// Alias of config_get().
// Without key, returns full config array.
$all = config();

function config_get(?string $key = null, mixed $default = null): mixed
// Reads value by key with optional default.
// Supports dot notation for nested keys.
$host = config_get('database.mysql.host', '127.0.0.1');

function config_resolve(string $primary_key, string $fallback_key, mixed $default = null): mixed
// Reads primary key first, then fallback key, then default.
// Useful for supporting new config keys with legacy fallback keys.
$driver = config_resolve('cache.driver', 'cache_driver', 'array');

function config_int(string $key, int $default = 0): int
// Reads value as integer.
// Returns default when conversion fails.
$port = config_int('database.mysql.port', 3306);

function config_float(string $key, float $default = 0.0): float
// Reads value as float.
// Returns default when conversion fails.
$ratio = config_float('app.ratio', 1.0);

function config_str(string $key, string $default = ''): string
// Reads value as string.
// Returns default when conversion fails.
$name = config_str('app.app_name', 'Harbor');

function config_bool(string $key, bool $default = false): bool
// Reads value as bool.
// Supports true/false style strings and numeric flags.
$debug = config_bool('app.debug', false);

function config_arr(string $key, array $default = []): array
// Reads value as array.
// Supports array, traversable, JSON string, and comma-separated string.
$hosts = config_arr('database.hosts', []);

function config_obj(string $key, ?object $default = null): ?object
// Reads value as object.
// Supports arrays and JSON object strings.
$profile = config_obj('database.profile');

function config_json(string $key, mixed $default = null): mixed
// Decodes one JSON string value.
// Returns default on decode failure.
$meta = config_json('database.meta', []);</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Router Integration</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\Router\Router;

new Router(
    __DIR__.'/routes.php',
    __DIR__.'/../global.php',
)->render();</code></pre>
    <h3>What it does</h3>
    <p>Router constructor requires a config path and calls <code>config_init_global()</code> before route render.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Router Config API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">public function __construct(string $router_path, string $config_path)
// Loads routes from file.
// Loads the provided config file into top-level $_ENV keys.
$router = new Router(__DIR__.'/routes.php', __DIR__.'/../global.php');</code></pre>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
