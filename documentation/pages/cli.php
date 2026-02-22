<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - CLI';
$page_description = 'CLI commands for scaffolding, route compile, and local docs.';
$page_id = 'cli';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Tooling</span>
    <h1>CLI</h1>
    <p>Scaffold sites, compile routes, and run docs.</p>
</section>

<section class="docs-section">
    <h2><code>bin/harbor</code></h2>
    <h3>Example</h3>
    <pre><code class="language-bash">./bin/harbor .
./bin/harbor documentation/.router
./bin/harbor init my-site</code></pre>
    <h3>What it does</h3>
    <p>Compiles routes by default and scaffolds sites with <code>init</code>.</p>
    <h3>API</h3>
    <ul>
        <li><code>./bin/harbor .</code> compiles <code>./.router</code>.</li>
        <li><code>./bin/harbor &lt;directory&gt;</code> compiles <code>&lt;directory&gt;/.router</code>.</li>
        <li><code>./bin/harbor &lt;path-to-.router&gt;</code> compiles that route file.</li>
        <li><code>./bin/harbor init [site-name]</code> creates a site scaffold.</li>
    </ul>
</section>

<section class="docs-section">
    <h2><code>bin/harbor-docs</code></h2>
    <h3>Example</h3>
    <pre><code class="language-bash">./bin/harbor-docs
./bin/harbor-docs --port=9090</code></pre>
    <h3>What it does</h3>
    <p>Starts the local docs server on an available port.</p>
    <h3>API</h3>
    <ul>
        <li>Option: <code>--port=PORT</code>.</li>
        <li>Default start port: <code>8081</code>.</li>
        <li>Reserved port: <code>8080</code> is skipped.</li>
        <li>Installed package path: <code>./vendor/bin/harbor-docs</code>.</li>
    </ul>
</section>

<section class="docs-section">
    <h2>Dev Workflow</h2>
    <h3>Example</h3>
    <pre><code class="language-bash">composer test
./vendor/bin/php-cs-fixer fix --using-cache=no --sequential
./bin/harbor documentation/.router
./bin/harbor-docs</code></pre>
    <h3>What it does</h3>
    <p>Runs tests, formats code, compiles routes, and starts docs.</p>
    <h3>API</h3>
    <ul>
        <li><code>composer test</code>.</li>
        <li><code>./vendor/bin/php-cs-fixer fix --using-cache=no --sequential</code>.</li>
        <li><code>./bin/harbor documentation/.router</code>.</li>
        <li><code>./bin/harbor-docs</code>.</li>
    </ul>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
