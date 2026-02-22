<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Routing';
$page_description = 'Define routes with .router files and compile to routes.php.';
$page_id = 'routing';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Routing</span>
    <h1>Routing</h1>
    <p>Write routes in <code>.router</code>, compile to <code>routes.php</code>, then match by path segments.</p>
</section>

<section class="docs-section">
    <h2>Define Routes</h2>

    <h3>Example</h3>
    <pre><code class="language-ini">#route
  path: /
  method: GET
  name: docs.home
  entry: pages/index.php
#endroute

#route
  path: /guides/$
  method: GET
  name: docs.guide
  entry: pages/routing.php
#endroute</code></pre>
    <pre><code class="language-ini">#include "./routes/shared.router"</code></pre>
    <pre><code class="language-bash">./bin/harbor documentation/.router
./bin/harbor .</code></pre>

    <h3>What it does</h3>
    <p>Preprocesses <code>#include</code> lines first, then compiles final route entries into <code>routes.php</code>.</p>

    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Route Definition API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-ini">path: /guides/$
# Defines a route path.
# "$" marks one dynamic segment.

method: GET
# Limits the route to one HTTP method.
# Common values are GET, POST, PUT, PATCH, DELETE.

name: docs.guide
# Optional route name.
# Useful for internal reference and clarity.

entry: pages/routing.php
# PHP file loaded when route matches.
# Can be relative to project or absolute.

#include "./routes/shared.router"
# Imports another .router file before compile.
# Included content replaces the include line.

path: /404
# Fallback path used when no route matches.
# Keep one final 404 route in .router.</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Route Includes</h2>

    <h3>Example</h3>
    <pre><code class="language-ini"># File: ./.router
#route
  path: /
  method: GET
  entry: pages/home.php
#endroute

#include "./routes/blog.router"
#include "/absolute/path/to/admin.router"</code></pre>
    <pre><code class="language-ini"># File: ./routes/blog.router
#route
  path: /blog/$
  method: GET
  entry: pages/blog.php
#endroute</code></pre>

    <h3>What it does</h3>
    <p>Each include is expanded before parsing routes. Nested includes are supported and processed recursively.</p>

    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Include Preprocess API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function harbor_pre_process_routes_file(string $router_source_path, array $include_stack = []): string
// Reads the source .router file and expands #include lines.
// Returns one final router string used by the compiler.
$content = harbor_pre_process_routes_file(__DIR__.'/.router');

function harbor_parse_include_path(string $line): ?string
// Parses include syntax: #include "path/to/file.router".
// Returns null when line is not a valid include directive.
$path = harbor_parse_include_path('#include "./routes/shared.router"');

function harbor_is_absolute_path(string $path): bool
// Detects Unix and Windows absolute paths.
// Relative paths are resolved from the including file directory.
$is_absolute = harbor_is_absolute_path('/var/www/site/.router');

// Compile order:
// 1) preprocess includes, 2) parse routes, 3) append /404 fallback.
// Missing files or circular includes stop compilation with an error.</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Read Route Data</h2>

    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\HelperLoader;
use function Harbor\Router\route;
use function Harbor\Router\route_exists;
use function Harbor\Router\route_name_is;
use function Harbor\Router\route_query;
use function Harbor\Router\route_segment;

HelperLoader::load('route');

$guide_slug = route_segment(0, 'overview');
$tab = route_query('tab', 'general');
$guide_link = route('docs.guide', [$guide_slug]);
$has_home = route_exists('docs.home');
$is_guide_page = route_name_is('docs.guide');</code></pre>

    <h3>What it does</h3>
    <p>Reads matched path segments and query values from the current route context.</p>
    <p>Also builds paths from route names with positional parameters.</p>
    <p>Example URL: <code>/guides/php?tab=general</code> with route path <code>/guides/$</code>.</p>
    <p>Result: <code>route_segment(0)</code> returns <code>'php'</code>, <code>route_query('tab')</code> returns <code>'general'</code>, and <code>route('docs.guide', ['php'])</code> returns <code>/guides/php</code>.</p>

    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Route Helper API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <div class="api-group">
                <p class="api-group-title">Segment Helpers</p>
                <pre><code class="language-php">function route_segment(int $index, mixed $default = null): mixed
// Gets one dynamic segment by index.
// Works with "$" placeholders from the current matched route.
$slug = route_segment(0, 'overview'); // "php" for /guides/php

function route_segment_int(int $index, int $default = 0): int
// Gets one segment and casts numeric values to int.
// Returns $default when the value is not numeric.
$page = route_segment_int(1, 1);

function route_segment_float(int $index, float $default = 0.0): float
// Gets one segment and casts numeric values to float.
// Returns $default when conversion is not possible.
$ratio = route_segment_float(1, 1.0);

function route_segment_str(int $index, string $default = ''): string
// Gets one segment as string.
// Supports scalar and stringable object values.
$section = route_segment_str(0, 'guides');

function route_segment_bool(int $index, bool $default = false): bool
// Gets one segment as bool.
// Accepts boolean-like values such as "true", "false", 0, 1.
$enabled = route_segment_bool(2, false);

function route_segment_arr(int $index, array $default = []): array
// Gets one segment as array.
// Supports JSON arrays and comma-separated values.
$tags = route_segment_arr(1, ['general']);

function route_segment_obj(int $index, ?object $default = null): ?object
// Gets one segment as object.
// Supports JSON object/array input.
$filters = route_segment_obj(1);

function route_segment_json(int $index, mixed $default = null): mixed
// Decodes one segment as JSON.
// Returns $default when JSON decoding fails.
$payload = route_segment_json(1, []);

function route_segments(): array
// Returns all matched dynamic segments.
// Order matches the "$" positions in route path.
$segments = route_segments();

function route_segments_count(): int
// Counts all matched dynamic segments.
// Useful for simple route validation.
$total_segments = route_segments_count();

function route_segment_exists(int $index): bool
// Checks if a segment index exists.
// Uses zero-based index.
$has_first = route_segment_exists(0);</code></pre>
            </div>

            <div class="api-group">
                <p class="api-group-title">Query Helpers</p>
                <pre><code class="language-php">function route_query(?string $key = null, mixed $default = null): mixed
// Gets one query value or full query array.
// Supports dot notation for nested keys.
$tab = route_query('tab', 'general');

function route_query_int(string $key, int $default = 0): int
// Gets one query value as int.
// Returns $default if conversion fails.
$page = route_query_int('page', 1);

function route_query_float(string $key, float $default = 0.0): float
// Gets one query value as float.
// Returns $default if conversion fails.
$ratio = route_query_float('ratio', 1.0);

function route_query_str(string $key, string $default = ''): string
// Gets one query value as string.
// Returns $default when value is missing.
$locale = route_query_str('locale', 'en');

function route_query_bool(string $key, bool $default = false): bool
// Gets one query value as bool.
// Accepts true/false style string values.
$draft = route_query_bool('draft', false);

function route_query_arr(string $key, array $default = []): array
// Gets one query value as array.
// Supports JSON and comma-separated input.
$tags = route_query_arr('tags', []);

function route_query_obj(string $key, ?object $default = null): ?object
// Gets one query value as object.
// Supports JSON object/array input.
$filters = route_query_obj('filters');

function route_query_json(string $key, mixed $default = null): mixed
// Decodes one query value as JSON.
// Returns $default when decoding fails.
$meta = route_query_json('meta', []);

function route_queries(): array
// Returns full query parameter array.
// Values come from current request query string.
$query = route_queries();

function route_queries_count(): int
// Counts total query parameters.
// Useful for quick empty checks.
$total_queries = route_queries_count();

function route_query_exists(string $key): bool
// Checks if one query key exists.
// Supports dot notation for nested keys.
$has_tab = route_query_exists('tab');</code></pre>
            </div>

            <div class="api-group">
                <p class="api-group-title">Named Route Helpers</p>
                <pre><code class="language-php">function route_exists(string $name): bool
// Checks if one named route exists in compiled route definitions.
// Route names come from the "name" key in .router entries.
$has_route = route_exists('docs.guide');

function route_name_is(string $name): bool
// Checks if current matched route has the given name.
// Useful for active nav state checks in templates.
$is_current = route_name_is('docs.guide');

function route(string $name, array $parameters = []): string
// Builds path from a route name.
// "$" segments in route path are replaced by parameters by index.
$guide_path = route('docs.guide', ['php']); // "/guides/php"</code></pre>
            </div>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Router Runtime</h2>

    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\Router\Router;

require __DIR__.'/../vendor/autoload.php';

new Router(__DIR__.'/routes.php', __DIR__.'/config.php')->render();</code></pre>

    <h3>What it does</h3>
    <p>Loads routes, resolves the current route, then includes the matched entry file.</p>

    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Router Class API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">public function __construct(string $router_path, string $config_path)
// Creates router using compiled routes file.
// The config path is required and loaded into $_ENV.
$router = new Router(__DIR__.'/routes.php', __DIR__.'/config.php');

public function get_uri(): string
// Returns current request path.
// Reads and normalizes REQUEST_URI.
$uri = $router->get_uri();

public function current()
// Resolves current route with segments and query.
// Falls back to final /404 route when no match exists.
$current_route = $router->current();

public function render(array $variables = []): void
// Renders current route entry file.
// Variables are extracted before including the entry.
$router->render(['name' => 'Ada']);</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Compile Command</h2>
    <h3>Example</h3>
    <pre><code class="language-bash">./bin/harbor documentation/.router
./bin/harbor .</code></pre>
    <h3>What it does</h3>
    <p>Compiles your route file into executable route arrays.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Compile Command API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-bash">./bin/harbor .
# Compiles ./.router by default.
# Best for project root usage.

./bin/harbor &lt;project-dir&gt;
# Compiles &lt;project-dir&gt;/.router.
# Good for multi-project workspace.

./bin/harbor &lt;path-to-.router&gt;
# Compiles a specific router file path.
# Use when route file is outside default location.

#include "./routes/api.router"
# Include directives are expanded before route parsing.
# Supports nested includes and absolute/relative paths.</code></pre>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
