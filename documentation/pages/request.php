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
    <pre><code class="language-php">use PhpFramework\HelperLoader;

HelperLoader::load('request');</code></pre>
    <h3>What it does</h3>
    <p>Loads request functions into the <code>PhpFramework\Request</code> namespace.</p>
    <h3>API</h3>
    <ul>
        <li>Loader call: <code>HelperLoader::load('request')</code>.</li>
        <li>Main snapshot helper: <code>request()</code>.</li>
    </ul>
</section>

<section class="docs-section">
    <h2>Read Request Data</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function PhpFramework\Request\request_body_int;
use function PhpFramework\Request\request_full_url;
use function PhpFramework\Request\request_header_bool;
use function PhpFramework\Request\request_input_str;
use function PhpFramework\Request\request_method;

$method = request_method();
$url = request_full_url();
$user_id = request_body_int('user.id');
$trace_enabled = request_header_bool('x-trace-enabled');
$search_query = request_input_str('search', '');</code></pre>

    <h3>What it does</h3>
    <p>Reads request values with typed helpers and defaults.</p>

    <h3>API</h3>
    <ul>
        <li>Snapshot: <code>request()</code>.</li>
        <li>Method and checks: <code>request_method()</code>, <code>request_is_get()</code>, <code>request_is_post()</code>, <code>request_is_put()</code>, <code>request_is_patch()</code>, <code>request_is_delete()</code>, <code>request_is_options()</code>, <code>request_is_head()</code>, <code>request_is_trace()</code>, <code>request_is_connect()</code>.</li>
        <li>URL and meta: <code>request_uri()</code>, <code>request_path()</code>, <code>request_query_string()</code>, <code>request_scheme()</code>, <code>request_host()</code>, <code>request_port()</code>, <code>request_url()</code>, <code>request_full_url()</code>, <code>request_ip()</code>, <code>request_user_agent()</code>, <code>request_referer()</code>, <code>request_is_secure()</code>, <code>request_is_ajax()</code>, <code>request_is_json()</code>.</li>
        <li>Headers: <code>request_headers()</code>, <code>request_header()</code>, <code>request_header_exists()</code>, <code>request_header_int()</code>, <code>request_header_float()</code>, <code>request_header_str()</code>, <code>request_header_bool()</code>, <code>request_header_arr()</code>, <code>request_header_obj()</code>, <code>request_header_json()</code>.</li>
        <li>Body: <code>request_raw_body()</code>, <code>request_body()</code>, <code>request_body_all()</code>, <code>request_body_count()</code>, <code>request_body_exists()</code>, <code>request_body_int()</code>, <code>request_body_float()</code>, <code>request_body_str()</code>, <code>request_body_bool()</code>, <code>request_body_arr()</code>, <code>request_body_obj()</code>, <code>request_body_json()</code>.</li>
        <li>Input: <code>request_input()</code>, <code>request_input_int()</code>, <code>request_input_float()</code>, <code>request_input_str()</code>, <code>request_input_bool()</code>, <code>request_input_arr()</code>, <code>request_input_obj()</code>, <code>request_input_json()</code>.</li>
        <li>Cookies, files, server: <code>request_cookie()</code>, <code>request_cookies()</code>, <code>request_cookie_exists()</code>, <code>request_files()</code>, <code>request_file()</code>, <code>request_has_file()</code>, <code>request_server()</code>.</li>
    </ul>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
