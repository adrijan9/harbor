<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Request Helpers';
$page_description = 'Typed request helpers for URL, headers, body, cookies, files, and server data.';
$page_id = 'request';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Helpers</span>
    <h1>Request Helpers</h1>
    <p>Typed helpers for request data and metadata.</p>
</section>

<section class="docs-section">
    <h2>Load Helper</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\HelperLoader;

HelperLoader::load('request');</code></pre>
    <h3>What it does</h3>
    <p>Loads request functions into the <code>Harbor\Request</code> namespace.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Request Loader API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">HelperLoader::load(string $helper_name): void
// Loads helper functions by module name.
// Use "request" to enable request helpers.
HelperLoader::load('request');

function request(): array
// Returns normalized request snapshot.
// Includes method, URL data, headers, body, cookies, files, and server data.
$request_data = request();</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Read Request Data</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Request\request_body_int;
use function Harbor\Request\request_full_url;
use function Harbor\Request\request_header_bool;
use function Harbor\Request\request_input_str;
use function Harbor\Request\request_method;

$method = request_method();
$url = request_full_url();
$user_id = request_body_int('user.id');
$trace_enabled = request_header_bool('x-trace-enabled');
$search_query = request_input_str('search', '');</code></pre>

    <h3>What it does</h3>
    <p>Reads request values with typed helpers and defaults.</p>

    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Request Helper API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <div class="api-group">
                <p class="api-group-title">Request and Method</p>
                <pre><code class="language-php">function request(): array
// Returns normalized request snapshot.
// Use this when you need all request data in one array.
$all = request();

function request_method(): string
// Returns current HTTP method in uppercase.
// Reads REQUEST_METHOD and defaults to GET.
$method = request_method();

function request_is_get(): bool
// Checks if method is GET.
// Internally compares against request_method().
$is_get = request_is_get();

function request_is_post(): bool
// Checks if method is POST.
// Internally compares against request_method().
$is_post = request_is_post();

function request_is_put(): bool
// Checks if method is PUT.
// Internally compares against request_method().
$is_put = request_is_put();

function request_is_patch(): bool
// Checks if method is PATCH.
// Internally compares against request_method().
$is_patch = request_is_patch();

function request_is_delete(): bool
// Checks if method is DELETE.
// Internally compares against request_method().
$is_delete = request_is_delete();

function request_is_options(): bool
// Checks if method is OPTIONS.
// Internally compares against request_method().
$is_options = request_is_options();

function request_is_head(): bool
// Checks if method is HEAD.
// Internally compares against request_method().
$is_head = request_is_head();

function request_is_trace(): bool
// Checks if method is TRACE.
// Internally compares against request_method().
$is_trace = request_is_trace();

function request_is_connect(): bool
// Checks if method is CONNECT.
// Internally compares against request_method().
$is_connect = request_is_connect();</code></pre>
            </div>

            <div class="api-group">
                <p class="api-group-title">URL and Metadata</p>
                <pre><code class="language-php">function request_uri(): string
// Returns raw request URI.
// Includes path and query string when present.
$uri = request_uri(); // "/users/42?tab=profile"

function request_path(): string
// Returns URI path only.
// Removes query string from URI.
$path = request_path(); // "/users/42"

function request_query_string(): string
// Returns raw query string.
// Reads query from URI or QUERY_STRING.
$query = request_query_string(); // "tab=profile"

function request_scheme(): string
// Returns URL scheme.
// Resolves to "http" or "https".
$scheme = request_scheme();

function request_host(): string
// Returns host name.
// Uses Host header or server host fallback.
$host = request_host();

function request_port(): int
// Returns request port.
// Uses host/server values with scheme defaults.
$port = request_port();

function request_url(): string
// Returns full URL without query string.
// Combines scheme, host, port, and path.
$url = request_url();

function request_full_url(): string
// Returns full URL with query string.
// Extends request_url() when query is present.
$full_url = request_full_url();

function request_ip(): ?string
// Returns client IP address.
// Checks x-forwarded-for then REMOTE_ADDR.
$ip = request_ip();

function request_user_agent(string $default = ''): string
// Returns User-Agent header as string.
// Uses provided default when missing.
$agent = request_user_agent('unknown');

function request_referer(?string $default = null): ?string
// Returns Referer header as nullable string.
// Uses provided default when missing.
$referer = request_referer();

function request_is_secure(): bool
// Checks if request is HTTPS.
// Uses HTTPS/server port/forwarded headers.
$is_secure = request_is_secure();

function request_is_ajax(): bool
// Checks if request came from XHR.
// Compares x-requested-with header.
$is_ajax = request_is_ajax();

function request_is_json(): bool
// Checks if request content type is JSON.
// Looks for "application/json" in Content-Type.
$is_json = request_is_json();</code></pre>
            </div>

            <div class="api-group">
                <p class="api-group-title">Headers</p>
                <pre><code class="language-php">function request_headers(): array
// Returns normalized request headers.
// Header names are lowercase with dashes.
$headers = request_headers();

function request_header(string $key, mixed $default = null): mixed
// Returns one header value by key.
// Supports normalized or regular header names.
$trace_id = request_header('x-trace-id');

function request_header_exists(string $key): bool
// Checks if one header key exists.
// Uses normalized header matching.
$has_trace = request_header_exists('x-trace-id');

function request_header_int(string $key, int $default = 0): int
// Returns one header value as int.
// Falls back to default when conversion fails.
$length = request_header_int('content-length', 0);

function request_header_float(string $key, float $default = 0.0): float
// Returns one header value as float.
// Falls back to default when conversion fails.
$ratio = request_header_float('x-ratio', 1.0);

function request_header_str(string $key, string $default = ''): string
// Returns one header value as string.
// Falls back to default when value is missing.
$content_type = request_header_str('content-type', '');

function request_header_bool(string $key, bool $default = false): bool
// Returns one header value as bool.
// Accepts boolean-like string values.
$trace = request_header_bool('x-trace-enabled', false);

function request_header_arr(string $key, array $default = []): array
// Returns one header value as array.
// Supports JSON arrays and CSV-like strings.
$tags = request_header_arr('x-tags', []);

function request_header_obj(string $key, ?object $default = null): ?object
// Returns one header value as object.
// Supports JSON object/array conversion.
$options = request_header_obj('x-options');

function request_header_json(string $key, mixed $default = null): mixed
// Decodes one header value as JSON.
// Returns default when decode fails.
$meta = request_header_json('x-meta', []);</code></pre>
            </div>

            <div class="api-group">
                <p class="api-group-title">Body</p>
                <pre><code class="language-php">function request_raw_body(): string
// Returns raw HTTP body string.
// Reads php://input once and caches it.
$raw = request_raw_body();

function request_body(?string $key = null, mixed $default = null): mixed
// Returns one body value or full body payload.
// Supports dot notation when body is array/object.
$email = request_body('user.email', '');

function request_body_all(): array
// Returns full body as array.
// Object payloads are converted to array.
$body = request_body_all();

function request_body_count(): int
// Returns number of body keys.
// Counts keys from request_body_all().
$body_count = request_body_count();

function request_body_exists(string $key): bool
// Checks if body key exists.
// Supports dot notation for nested keys.
$has_token = request_body_exists('token');

function request_body_int(string $key, int $default = 0): int
// Returns one body value as int.
// Falls back to default when conversion fails.
$user_id = request_body_int('user.id', 0);

function request_body_float(string $key, float $default = 0.0): float
// Returns one body value as float.
// Falls back to default when conversion fails.
$price = request_body_float('price', 0.0);

function request_body_str(string $key, string $default = ''): string
// Returns one body value as string.
// Falls back to default when conversion fails.
$name = request_body_str('name', 'Guest');

function request_body_bool(string $key, bool $default = false): bool
// Returns one body value as bool.
// Accepts common boolean-like values.
$enabled = request_body_bool('enabled', false);

function request_body_arr(string $key, array $default = []): array
// Returns one body value as array.
// Supports JSON and comma-separated values.
$roles = request_body_arr('roles', []);

function request_body_obj(string $key, ?object $default = null): ?object
// Returns one body value as object.
// Supports JSON object/array conversion.
$profile = request_body_obj('profile');

function request_body_json(string $key, mixed $default = null): mixed
// Decodes one body value as JSON.
// Returns default when decode fails.
$settings = request_body_json('settings', []);</code></pre>
            </div>

            <div class="api-group">
                <p class="api-group-title">Input</p>
                <pre><code class="language-php">function request_input(?string $key = null, mixed $default = null): mixed
// Returns request input value.
// Alias of request_body() in Harbor runtime.
$input = request_input('name', '');

function request_input_int(string $key, int $default = 0): int
// Returns input value as int.
// Falls back to default when conversion fails.
$limit = request_input_int('limit', 25);

function request_input_float(string $key, float $default = 0.0): float
// Returns input value as float.
// Falls back to default when conversion fails.
$discount = request_input_float('discount', 0.0);

function request_input_str(string $key, string $default = ''): string
// Returns input value as string.
// Falls back to default when conversion fails.
$search = request_input_str('search', '');

function request_input_bool(string $key, bool $default = false): bool
// Returns input value as bool.
// Accepts common boolean-like values.
$remember = request_input_bool('remember', false);

function request_input_arr(string $key, array $default = []): array
// Returns input value as array.
// Supports JSON and comma-separated values.
$filters = request_input_arr('filters', []);

function request_input_obj(string $key, ?object $default = null): ?object
// Returns input value as object.
// Supports JSON object/array conversion.
$payload = request_input_obj('payload');

function request_input_json(string $key, mixed $default = null): mixed
// Decodes input value as JSON.
// Returns default when decode fails.
$rules = request_input_json('rules', []);</code></pre>
            </div>

            <div class="api-group">
                <p class="api-group-title">Cookie, Files, Server</p>
                <pre><code class="language-php">function request_cookie(?string $key = null, mixed $default = null): mixed
// Returns one cookie value or all cookies.
// Supports dot notation for cookie arrays.
$session_id = request_cookie('session_id', null);

function request_cookies(): array
// Returns all cookies array.
// Maps directly from $_COOKIE.
$cookies = request_cookies();

function request_cookie_exists(string $key): bool
// Checks if cookie key exists.
// Supports dot notation for nested values.
$has_session = request_cookie_exists('session_id');

function request_files(?string $key = null, mixed $default = null): mixed
// Returns one uploaded file payload or all files.
// Supports dot notation for nested uploads.
$files = request_files();

function request_file(string $key, mixed $default = null): mixed
// Returns one uploaded file payload.
// Alias of request_files() for single key lookup.
$avatar = request_file('avatar');

function request_has_file(string $key): bool
// Checks if uploaded file key exists.
// Uses normalized $_FILES lookup.
$has_avatar = request_has_file('avatar');

function request_server(?string $key = null, mixed $default = null): mixed
// Returns one server value or full server array.
// Supports dot notation for nested values.
$request_time = request_server('REQUEST_TIME', 0);</code></pre>
            </div>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
