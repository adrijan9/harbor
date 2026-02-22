<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - CLI';
$page_description = 'CLI commands for scaffolding, route generation, and local serving.';
$page_id = 'cli';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Tooling</span>
    <h1>CLI Commands</h1>
    <p>Use the built-in commands to scaffold sites and compile route definitions quickly.</p>
</section>

<section class="docs-section">
    <h2><code>bin/harbor</code></h2>
    <p>Primary CLI command for route compilation and site scaffolding.</p>
    <pre><code class="language-bash">./bin/harbor .
./bin/harbor documentation/.router
./bin/harbor init my-site</code></pre>
    <ul>
        <li>Default mode compiles a <code>.router</code> file into <code>routes.php</code>.</li>
        <li>Passing a directory (for example <code>.</code>) compiles <code>&lt;directory&gt;/.router</code>.</li>
        <li>Creates page scaffolds, route files, and defaults.</li>
        <li>Creates parent-level <code>serve.sh</code> only if it does not already exist.</li>
    </ul>
</section>

<section class="docs-section">
    <h2><code>bin/harbor-docs</code></h2>
    <p>Serves the package documentation locally.</p>
    <pre><code class="language-bash">./bin/harbor-docs
./bin/harbor-docs --port=9090</code></pre>
    <ul>
        <li>Default start port is <code>8081</code>.</li>
        <li>Port <code>8080</code> is skipped by design.</li>
        <li>The command automatically picks the next available port.</li>
        <li>Dependency install users can run <code>./vendor/bin/harbor-docs</code>.</li>
    </ul>
</section>

<section class="docs-section">
    <h2>Development Workflow</h2>
    <pre><code class="language-bash">composer test
./vendor/bin/php-cs-fixer fix --using-cache=no --sequential
./bin/harbor documentation/.router
./bin/harbor-docs</code></pre>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
