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

    <h3>Install Dependencies</h3>
    <pre><code class="language-bash">composer install</code></pre>

    <h3>Create a Site Scaffold</h3>
    <pre><code class="language-bash">./bin/harbor init documentation</code></pre>
    <p>The command creates pages, route templates, and a parent-level <code>serve.sh</code> if missing.</p>
</section>

<section class="docs-section">
    <h2>Generate Routes</h2>
    <p>Route definitions are authored in a <code>.router</code> file and compiled into <code>routes.php</code>.</p>
    <pre><code class="language-bash">./bin/harbor documentation/.router</code></pre>
</section>

<section class="docs-section">
    <h2>Run Docs</h2>
    <p>Serve documentation with the package CLI:</p>
    <pre><code class="language-bash">./bin/harbor-docs</code></pre>
    <p>
        The command starts from port <code>8081</code>, skips <code>8080</code>, and finds the next available port
        automatically.
    </p>
    <p>When installed as a dependency package, use <code>./vendor/bin/harbor-docs</code> instead.</p>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
