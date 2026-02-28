<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Load Helpers';
$page_description = 'One place to load Harbor helper modules by key.';
$page_id = 'load_helpers';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Helper Loader</span>
    <h1>Load Helpers</h1>
    <p>Load all helper modules from one place using module keys.</p>
</section>

<section class="docs-section">
    <h2>Basic Usage</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\HelperLoader;

HelperLoader::load('request');
HelperLoader::load('validation', 'response');
HelperLoader::load('cache');</code></pre>
    <h3>What it does</h3>
    <p>Registers helper functions from each module key so their namespaced functions are available in your runtime code.</p>
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
                <td><code>request</code></td>
                <td><code>Harbor\Request</code></td>
                <td>Typed request helpers.</td>
            </tr>
            <tr>
                <td><code>response</code></td>
                <td><code>Harbor\Response</code></td>
                <td>HTTP response helpers.</td>
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
