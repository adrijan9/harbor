<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Installation';
$page_description = 'Install Harbor, scaffold a site, and run docs locally.';
$page_id = 'installation';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Getting Started</span>
    <h1>Install and Run</h1>
    <p>Install dependencies, scaffold a site, compile routes, and run docs.</p>
</section>

<section class="docs-section">
    <h2>Requirements</h2>
    <ul>
        <li>PHP 8.5+</li>
        <li>Composer 2.x</li>
        <li>Web server support for rewriting to <code>index.php</code> (Apache or PHP built-in server)</li>
    </ul>
</section>

<section class="docs-section">
    <h2>Install Core</h2>
    <h3>Example</h3>
    <pre><code class="language-bash">composer install
./bin/harbor init documentation</code></pre>
    <h3>What it does</h3>
    <p>Installs dependencies and creates a runnable site scaffold.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Install API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>composer install</code> Install PHP dependencies.</li>
                <li><code>./bin/harbor init [site-name]</code> Generate new site scaffold.</li>
                <li><code>site-name</code> Optional site directory name (default: <code>example.site</code>).</li>
            </ul>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Compile Routes</h2>
    <h3>Example</h3>
    <pre><code class="language-bash">./bin/harbor documentation/.router
./bin/harbor documentation</code></pre>
    <h3>What it does</h3>
    <p>Compiles route definitions into <code>routes.php</code>.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Route Compile API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>./bin/harbor &lt;path-to-.router&gt;</code> Compile route file.</li>
                <li><code>./bin/harbor &lt;directory&gt;</code> Compile <code>&lt;directory&gt;/.router</code>.</li>
                <li><code>public/routes.php</code> Output file is generated there when <code>public/</code> exists beside <code>.router</code>.</li>
                <li><code>routes.php</code> Output file is generated beside input when no <code>public/</code> directory exists.</li>
            </ul>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Run Docs</h2>
    <h3>Example</h3>
    <pre><code class="language-bash">./bin/harbor-docs</code></pre>
    <h3>What it does</h3>
    <p>Serves local documentation on the next available port.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Docs Serve API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>./bin/harbor-docs</code> Start docs server.</li>
                <li><code>./bin/harbor-docs --port=PORT</code> Set preferred start port.</li>
                <li><code>8081</code> Default start port.</li>
                <li><code>8080</code> Reserved and skipped.</li>
                <li><code>./vendor/bin/harbor-docs</code> Command path when installed as dependency.</li>
            </ul>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
