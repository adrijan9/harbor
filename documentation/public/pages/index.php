<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Overview';
$page_description = 'Start here, then continue to Installation or jump to Creating New Site.';
$page_id = 'home';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Documentation</span>
    <h1>Harbor Documentation</h1>
    <p>If you are reading docs, you probably already created a project and installed Harbor.</p>
    <div class="button-row">
        <a class="button button-primary" href="/installation">Read Installation</a>
        <a class="button button-ghost" href="/creating-new-site">Go To Creating New Site</a>
    </div>
</section>

<section class="docs-section">
    <h2>Overview Flow</h2>
    <p>If you want full context, continue to the next page in docs: <a href="/installation"><code>Installation</code></a>.</p>
    <p>If you want to jump straight into scaffolding, open <a href="/creating-new-site"><code>Creating New Site</code></a>.</p>
</section>

<section class="docs-section">
    <span class="status-chip">Stable Core</span>
    <h2>What You Get</h2>
    <p>Core features are packaged as focused helpers. You can load only what you need in runtime pages.</p>

    <div class="card-grid">
        <a class="card" href="/load-helpers">
            <h3>Load Helpers</h3>
            <p>One place to see all helper module keys and how to load them.</p>
        </a>
        <a class="card" href="/code">
            <h3>Code Conventions</h3>
            <p>Style workflow and private helper method rules.</p>
        </a>
        <a class="card" href="/cli">
            <h3>CLI Tooling</h3>
            <p>Scaffold sites and compile `.router` files.</p>
        </a>
        <a class="card" href="/routing">
            <h3>Routing</h3>
            <p>Route files with segment and query support.</p>
        </a>
        <a class="card" href="/robots-txt-generator">
            <h3>Robots.txt Generator</h3>
            <p>Generate a starter file for <code>public/robots.txt</code> without leaving the Harbor docs.</p>
        </a>
        <a class="card" href="/request">
            <h3>Request Helpers</h3>
            <p>Typed access to method, URL, headers, body, files, and cookies.</p>
        </a>
        <a class="card" href="/view">
            <h3>View Helpers</h3>
            <p>Native PHP views with layouts, partials, and shared data helpers.</p>
        </a>
        <a class="card" href="/cookie">
            <h3>Cookie Helpers</h3>
            <p>Set, read, and forget cookies with one helper module.</p>
        </a>
        <a class="card" href="/session">
            <h3>Session Helpers</h3>
            <p>Simplified sessions with cookie, array, and file drivers.</p>
        </a>
        <a class="card" href="/password">
            <h3>Password Helpers</h3>
            <p>Password hashing and verification wrappers around PHP password APIs.</p>
        </a>
        <a class="card" href="/auth">
            <h3>Auth Helpers</h3>
            <p>Use explicit <code>auth_web_*</code> and <code>auth_api_*</code> helpers for session and bearer auth flows.</p>
        </a>
        <a class="card" href="/config">
            <h3>Config Helpers</h3>
            <p>Load one or many config files and read typed values from env.</p>
        </a>
        <a class="card" href="/environment">
            <h3>Environment</h3>
            <p>Configure environment values and branch logic for local, development, stage, and production.</p>
        </a>
        <a class="card" href="/database">
            <h3>Database Helpers</h3>
            <p>Lightweight wrappers for SQLite, MySQL PDO, MySQLi, and resolver helpers.</p>
        </a>
        <a class="card" href="/model">
            <h3>Model Pattern</h3>
            <p>Optional plain-PHP model classes without ORM complexity.</p>
        </a>
        <a class="card" href="/model/pagination">
            <h3>Model Pagination</h3>
            <p>Standalone pagination module with class mode and helper mode.</p>
        </a>
        <a class="card" href="/migrations">
            <h3>Migrations &amp; Seeders</h3>
            <p>Create, run, and roll back tracked migration and seeder batches.</p>
        </a>
        <a class="card" href="/lang">
            <h3>Lang &amp; Translation</h3>
            <p>Locale helpers and Laravel-style translation keys.</p>
        </a>
        <a class="card" href="/support">
            <h3>Support Helpers</h3>
            <p>Shared value checks for blank and null behavior.</p>
        </a>
        <a class="card" href="/date">
            <h3>Date Helpers</h3>
            <p>Carbon wrapper helpers for creating and reading date-time instances.</p>
        </a>
        <a class="card" href="/pipeline">
            <h3>Pipeline Helpers</h3>
            <p>Functional pipeline helpers for composing action chains.</p>
        </a>
        <a class="card" href="/middleware">
            <h3>Middleware Helpers</h3>
            <p>Run request middleware callbacks backed by the pipeline engine.</p>
        </a>
        <a class="card" href="/validation">
            <h3>Validation</h3>
            <p>Fluent rule builders and result objects for request validation.</p>
        </a>
        <a class="card" href="/filesystem">
            <h3>Filesystem</h3>
            <p>Safe file and directory operations.</p>
        </a>
        <a class="card" href="/cache">
            <h3>Cache</h3>
            <p>Array and file cache helpers with shared API shape.</p>
        </a>
        <a class="card" href="/rate-limiter">
            <h3>Rate Limiter</h3>
            <p>Track attempts and retry windows for throttle and lockout flows.</p>
        </a>
        <a class="card" href="/logging">
            <h3>Logging</h3>
            <p>Levels, context, and reusable log entries.</p>
        </a>
    </div>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
