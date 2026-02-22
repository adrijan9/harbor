<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Request Helpers';
$page_description = 'Typed request accessors for URL, headers, body, cookies, files, and server variables.';
$page_id = 'request';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Helpers</span>
    <h1>Request Helper Reference</h1>
    <p>
        Request helpers expose normalized values with typed accessors so page code stays compact and explicit.
    </p>
</section>

<section class="docs-section">
    <h2>Load Request Helpers</h2>
    <pre><code class="language-php">use PhpFramework\HelperLoader;

HelperLoader::load('request');</code></pre>
</section>

<section class="docs-section">
    <h2>Common Functions</h2>
    <div class="table-wrapper">
        <table>
            <thead>
            <tr>
                <th>Function</th>
                <th>Description</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><code>request_method()</code></td>
                <td>HTTP method in uppercase.</td>
            </tr>
            <tr>
                <td><code>request_full_url()</code></td>
                <td>Scheme + host + path + query string.</td>
            </tr>
            <tr>
                <td><code>request_header_str('x-key')</code></td>
                <td>Typed header accessor.</td>
            </tr>
            <tr>
                <td><code>request_body('user.id')</code></td>
                <td>Nested body lookup with dot notation.</td>
            </tr>
            <tr>
                <td><code>request_cookie('session')</code></td>
                <td>Cookie value with optional default.</td>
            </tr>
            <tr>
                <td><code>request_has_file('avatar')</code></td>
                <td>File upload presence check.</td>
            </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="docs-section">
    <h2>Example</h2>
    <pre><code class="language-php">use function PhpFramework\Request\request_body_int;
use function PhpFramework\Request\request_header_bool;
use function PhpFramework\Request\request_input_str;

$user_id = request_body_int('user.id');
$trace_enabled = request_header_bool('x-trace-enabled');
$search_query = request_input_str('search', '');</code></pre>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
