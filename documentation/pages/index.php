<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Overview';
$page_description = 'Get started with Harbor and explore its helpers, router, and CLI tooling.';
$page_id = 'home';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Documentation</span>
    <h1>Pragmatic PHP framework primitives for routing, request handling, filesystem, and logging.</h1>
    <p>
        This documentation walks through every built-in helper and workflow in the project. The goal is to keep setup
        minimal while giving you predictable, explicit APIs.
    </p>
    <div class="button-row">
        <a class="button button-primary" href="/installation">Start Installation</a>
        <a class="button button-ghost" href="/routing">View Routing Guide</a>
    </div>
</section>

<section class="docs-section">
    <span class="status-chip">Stable Core</span>
    <h2>What You Get</h2>
    <p>Core features are packaged as focused helpers. You can load only what you need in runtime pages.</p>

    <div class="card-grid">
        <a class="card" href="/routing">
            <h3>Routing</h3>
            <p>Readable route files with dynamic segment support and query extraction.</p>
        </a>
        <a class="card" href="/request">
            <h3>Request Helpers</h3>
            <p>Typed access for method, URL, headers, body, files, cookies, and metadata.</p>
        </a>
        <a class="card" href="/filesystem">
            <h3>Filesystem</h3>
            <p>Safe file and directory operations with explicit runtime exceptions.</p>
        </a>
        <a class="card" href="/logging">
            <h3>Logging</h3>
            <p>PSR-style levels, structured context, and reusable content creation for multiple outputs.</p>
        </a>
        <a class="card" href="/cli">
            <h3>CLI Tooling</h3>
            <p>Init site scaffolds and compile `.router` definitions into executable route arrays.</p>
        </a>
    </div>
</section>

<section class="docs-section">
    <h2>Project Layout</h2>
    <div class="table-wrapper">
        <table>
            <thead>
            <tr>
                <th>Path</th>
                <th>Purpose</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><code>src/</code></td>
                <td>Framework source modules.</td>
            </tr>
            <tr>
                <td><code>documentation/</code></td>
                <td>This docs site (router, pages, shared templates, assets).</td>
            </tr>
            <tr>
                <td><code>tests/</code></td>
                <td>PHPUnit coverage for framework behavior.</td>
            </tr>
            <tr>
                <td><code>bin/</code></td>
                <td>CLI commands (`harbor`, `harbor-docs`).</td>
            </tr>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
