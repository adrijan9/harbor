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
    <ul>
        <li><code>composer install</code> installs dependencies.</li>
        <li><code>./bin/harbor init [site-name]</code> generates <code>.router</code>, <code>routes.php</code>, <code>index.php</code>, and <code>pages/</code>.</li>
    </ul>
</section>

<section class="docs-section">
    <h2>Compile Routes</h2>
    <h3>Example</h3>
    <pre><code class="language-bash">./bin/harbor documentation/.router
./bin/harbor documentation</code></pre>
    <h3>What it does</h3>
    <p>Compiles route definitions into <code>routes.php</code>.</p>
    <h3>API</h3>
    <ul>
        <li>Input: <code>.router</code> route definitions.</li>
        <li>Output: <code>routes.php</code> in the same directory.</li>
        <li>Modes: file path input or directory input.</li>
    </ul>
</section>

<section class="docs-section">
    <h2>Run Docs</h2>
    <h3>Example</h3>
    <pre><code class="language-bash">./bin/harbor-docs</code></pre>
    <h3>What it does</h3>
    <p>Serves local documentation on the next available port.</p>
    <h3>API</h3>
    <ul>
        <li>Default start port: <code>8081</code>.</li>
        <li>Reserved port: <code>8080</code> is skipped.</li>
        <li>Option: <code>--port=PORT</code>.</li>
        <li>Installed package path: <code>./vendor/bin/harbor-docs</code>.</li>
    </ul>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
