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
    <details class="api-details">
        <summary class="api-summary">
            <span>Request Loader API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>HelperLoader::load('request')</code> Load request helper module.</li>
                <li><code>request()</code> Return full request snapshot.</li>
            </ul>
        </div>
    </details>
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
    <details class="api-details">
        <summary class="api-summary">
            <span>Request Helper API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <div class="api-group">
                <p class="api-group-title">Request and Method</p>
                <ul class="api-method-list">
                    <li><code>request()</code> Full request snapshot.</li>
                    <li><code>request_method()</code> HTTP method in uppercase.</li>
                    <li><code>request_is_get()</code> Check GET method.</li>
                    <li><code>request_is_post()</code> Check POST method.</li>
                    <li><code>request_is_put()</code> Check PUT method.</li>
                    <li><code>request_is_patch()</code> Check PATCH method.</li>
                    <li><code>request_is_delete()</code> Check DELETE method.</li>
                    <li><code>request_is_options()</code> Check OPTIONS method.</li>
                    <li><code>request_is_head()</code> Check HEAD method.</li>
                    <li><code>request_is_trace()</code> Check TRACE method.</li>
                    <li><code>request_is_connect()</code> Check CONNECT method.</li>
                </ul>
            </div>

            <div class="api-group">
                <p class="api-group-title">URL and Metadata</p>
                <ul class="api-method-list">
                    <li><code>request_uri()</code> Raw request URI.</li>
                    <li><code>request_path()</code> Path without query string.</li>
                    <li><code>request_query_string()</code> Query string value.</li>
                    <li><code>request_scheme()</code> URL scheme (http/https).</li>
                    <li><code>request_host()</code> Request host value.</li>
                    <li><code>request_port()</code> Request port value.</li>
                    <li><code>request_url()</code> Full URL without query.</li>
                    <li><code>request_full_url()</code> Full URL with query.</li>
                    <li><code>request_ip()</code> Client IP address.</li>
                    <li><code>request_user_agent()</code> User-Agent header value.</li>
                    <li><code>request_referer()</code> Referer header value.</li>
                    <li><code>request_is_secure()</code> Check HTTPS request.</li>
                    <li><code>request_is_ajax()</code> Check AJAX request.</li>
                    <li><code>request_is_json()</code> Check JSON request content type.</li>
                </ul>
            </div>

            <div class="api-group">
                <p class="api-group-title">Headers</p>
                <ul class="api-method-list">
                    <li><code>request_headers()</code> All normalized headers.</li>
                    <li><code>request_header()</code> Header value by key.</li>
                    <li><code>request_header_exists()</code> Header existence check.</li>
                    <li><code>request_header_int()</code> Header as integer.</li>
                    <li><code>request_header_float()</code> Header as float.</li>
                    <li><code>request_header_str()</code> Header as string.</li>
                    <li><code>request_header_bool()</code> Header as boolean.</li>
                    <li><code>request_header_arr()</code> Header as array.</li>
                    <li><code>request_header_obj()</code> Header as object.</li>
                    <li><code>request_header_json()</code> Header decoded as JSON.</li>
                </ul>
            </div>

            <div class="api-group">
                <p class="api-group-title">Body</p>
                <ul class="api-method-list">
                    <li><code>request_raw_body()</code> Raw request payload string.</li>
                    <li><code>request_body()</code> Body value by key or full body.</li>
                    <li><code>request_body_all()</code> Full body as array.</li>
                    <li><code>request_body_count()</code> Body key count.</li>
                    <li><code>request_body_exists()</code> Body key existence check.</li>
                    <li><code>request_body_int()</code> Body value as integer.</li>
                    <li><code>request_body_float()</code> Body value as float.</li>
                    <li><code>request_body_str()</code> Body value as string.</li>
                    <li><code>request_body_bool()</code> Body value as boolean.</li>
                    <li><code>request_body_arr()</code> Body value as array.</li>
                    <li><code>request_body_obj()</code> Body value as object.</li>
                    <li><code>request_body_json()</code> Body value decoded as JSON.</li>
                </ul>
            </div>

            <div class="api-group">
                <p class="api-group-title">Input</p>
                <ul class="api-method-list">
                    <li><code>request_input()</code> Request value from body/query.</li>
                    <li><code>request_input_int()</code> Input value as integer.</li>
                    <li><code>request_input_float()</code> Input value as float.</li>
                    <li><code>request_input_str()</code> Input value as string.</li>
                    <li><code>request_input_bool()</code> Input value as boolean.</li>
                    <li><code>request_input_arr()</code> Input value as array.</li>
                    <li><code>request_input_obj()</code> Input value as object.</li>
                    <li><code>request_input_json()</code> Input value decoded as JSON.</li>
                </ul>
            </div>

            <div class="api-group">
                <p class="api-group-title">Cookie, Files, Server</p>
                <ul class="api-method-list">
                    <li><code>request_cookie()</code> Cookie value by key.</li>
                    <li><code>request_cookies()</code> All cookie values.</li>
                    <li><code>request_cookie_exists()</code> Cookie existence check.</li>
                    <li><code>request_files()</code> File payload by key.</li>
                    <li><code>request_file()</code> File alias for single key lookup.</li>
                    <li><code>request_has_file()</code> Uploaded file existence check.</li>
                    <li><code>request_server()</code> Server value by key.</li>
                </ul>
            </div>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
