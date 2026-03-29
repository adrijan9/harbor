<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Load Helpers';
$page_description = 'One place to load Harbor helper modules by key.';
$page_id = 'load_helpers';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">namespace: helper_loader</span>
    <h1>Load Helpers</h1>
    <p>Load all helper modules from one place using module keys.</p>
</section>

<section class="docs-section">
    <h2>Enum Usage (Recommended)</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\Helper;

Helper::Request->load();
Helper::Cookie->load();
Helper::Session->load();
Helper::Validation->load();
Helper::Response->load();
Helper::View->load();
Helper::Command->load();
Helper::CommandFlags->load();
Helper::Database->load();</code></pre>
    <h3>What it does</h3>
    <p>Loads helper modules through typed enum cases, so keys are discoverable and typo-safe.</p>
</section>

<section class="docs-section">
    <h2>String Loader (Backward Compatible)</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\Helper;

Helper::load_many('request');
Helper::load_many('cookie', 'session');
Helper::load_many('validation', 'response');
Helper::load_many('view');
Helper::load_many('command_flags');
Helper::load_many('db'); // alias supported, resolves to Helper::Database
Helper::load_many(Helper::Cache, Helper::Log);</code></pre>
    <h3>What it does</h3>
    <p>Keeps the previous string API working while also accepting enum cases directly.</p>
</section>

<section class="docs-section">
    <h2>Module Keys</h2>
    <div class="table-wrapper">
        <table>
            <thead>
            <tr>
                <th>Module Key</th>
                <th>Namespace</th>
                <th>Notes</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><code>route</code></td>
                <td><code>Harbor\Router</code></td>
                <td>Loads route segment/query/named helper set.</td>
            </tr>
            <tr>
                <td><code>config</code></td>
                <td><code>Harbor\Config</code></td>
                <td>Config loading and typed value readers.</td>
            </tr>
            <tr>
                <td><code>value</code>, <code>array</code></td>
                <td><code>Harbor\Support</code></td>
                <td>Blank/null checks and array mutation helpers.</td>
            </tr>
            <tr>
                <td><code>carbon</code></td>
                <td><code>Harbor\Date</code></td>
                <td>Carbon wrapper helper functions (<code>carbon()</code>, <code>date_now()</code>).</td>
            </tr>
            <tr>
                <td><code>pipeline</code></td>
                <td><code>Harbor\Pipeline</code></td>
                <td>Functional pipeline primitives (<code>pipeline_new()</code>, <code>pipeline_send()</code>, <code>pipeline_through()</code>, <code>pipeline_clog()</code>, <code>pipeline_get()</code>).</td>
            </tr>
            <tr>
                <td><code>middleware</code></td>
                <td><code>Harbor\Middleware</code></td>
                <td>Runs request middleware callbacks and first-class middleware classes (<code>WebAuthMiddleware</code>, <code>ApiAuthMiddleware</code>, <code>BasicAuthMiddleware</code>, <code>CsrfMiddleware</code>, <code>ThrottleMiddleware</code>, <code>CorsMiddleware</code>).</td>
            </tr>
            <tr>
                <td><code>request</code></td>
                <td><code>Harbor\Request</code></td>
                <td>Typed request helpers.</td>
            </tr>
            <tr>
                <td><code>cookie</code></td>
                <td><code>Harbor\Cookie</code></td>
                <td>Cookie set/get/forget helpers with optional signing and encryption.</td>
            </tr>
            <tr>
                <td><code>session</code></td>
                <td><code>Harbor\Session</code></td>
                <td>Simplified session helpers driven by <code>session</code> config with <code>cookie</code>, <code>array</code>, and <code>file</code> drivers.</td>
            </tr>
            <tr>
                <td><code>password</code></td>
                <td><code>Harbor\Password</code></td>
                <td>Password hash/verify wrappers, enum-based algorithm selection, and direct shortcuts (<code>bcrypt()</code>, <code>argon2i()</code>, <code>argon2id()</code>).</td>
            </tr>
            <tr>
                <td><code>auth</code>, <code>auth_web</code>, <code>auth_api</code></td>
                <td><code>Harbor\Auth</code></td>
                <td>Split auth helpers: load all with <code>auth</code>, or only <code>auth_web</code> / <code>auth_api</code> depending on transport.</td>
            </tr>
            <tr>
                <td><code>response</code></td>
                <td><code>Harbor\Response</code></td>
                <td>HTTP response helpers, full official <code>ResponseStatus</code> enum set, and <code>abort()</code>.</td>
            </tr>
            <tr>
                <td><code>view</code></td>
                <td><code>Harbor\View</code></td>
                <td>Native PHP view renderer with layouts, partials, shared data, and HTML escaping helper.</td>
            </tr>
            <tr>
                <td><code>command</code></td>
                <td><code>Harbor\Command</code></td>
                <td>Run custom <code>harbor-command</code> keys from application code with <code>command_run()</code>.</td>
            </tr>
            <tr>
                <td><code>command_flags</code></td>
                <td><code>Harbor\Command</code></td>
                <td>Loads standalone command flag helpers (<code>command_flags_init()</code>, <code>command_flag_no_value()</code>, <code>command_flag()</code>, <code>command_flags_print_usage()</code>).</td>
            </tr>
            <tr>
                <td><code>db</code>, <code>database</code>, <code>db_sqlite</code>, <code>db_mysql_pdo</code>, <code>db_mysqli</code></td>
                <td><code>Harbor\Database</code></td>
                <td>Database resolver and concrete wrappers for SQLite/MySQL connections and queries.</td>
            </tr>
            <tr>
                <td><code>pagination</code></td>
                <td><code>Harbor\Pagination</code></td>
                <td>Standalone pagination module with reusable abstract class and helper mode (<code>pagination_paginate()</code>).</td>
            </tr>
            <tr>
                <td><code>validation</code></td>
                <td><code>Harbor\Validation</code></td>
                <td>Fluent validation rules and results.</td>
            </tr>
            <tr>
                <td><code>performance</code></td>
                <td><code>Harbor\Performance</code></td>
                <td>Performance markers and tracking logs.</td>
            </tr>
            <tr>
                <td><code>units</code></td>
                <td><code>Harbor\Units</code></td>
                <td>Byte/time conversions and readable format helpers.</td>
            </tr>
            <tr>
                <td><code>filesystem</code></td>
                <td><code>Harbor\Filesystem</code></td>
                <td>File and directory helpers.</td>
            </tr>
            <tr>
                <td><code>cache</code>, <code>cache_array</code>, <code>cache_file</code>, <code>cache_apc</code></td>
                <td><code>Harbor\Cache</code></td>
                <td>Resolver and explicit cache backends.</td>
            </tr>
            <tr>
                <td><code>rate_limiter</code></td>
                <td><code>Harbor\RateLimiter</code></td>
                <td>Window-based request attempt tracking helpers for throttling and lockout flows.</td>
            </tr>
            <tr>
                <td><code>log</code></td>
                <td><code>Harbor\Log</code></td>
                <td>Log init/write helpers and levels.</td>
            </tr>
            <tr>
                <td><code>translation</code>, <code>translations</code>, <code>lang</code>, <code>language</code></td>
                <td><code>Harbor\Lang</code></td>
                <td>Locale and translation helpers.</td>
            </tr>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
