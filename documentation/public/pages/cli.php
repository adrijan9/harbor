<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - CLI';
$page_description = 'CLI commands for scaffolding, route compile, migrations, seeders, and local docs.';
$page_id = 'cli';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Tooling</span>
    <h1>CLI</h1>
    <p>Scaffold sites, compile routes, run migrations/seeders, and run docs.</p>
</section>

<section class="docs-section">
    <h2><code>bin/harbor</code></h2>
    <h3>Example</h3>
    <pre><code class="language-bash">./bin/harbor .
./bin/harbor documentation/.router
./bin/harbor init my-site</code></pre>
    <h3>What it does</h3>
    <p>Compiles routes by default (with <code>#include</code> preprocessing) and scaffolds sites with <code>init</code>.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Harbor CLI API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>./bin/harbor .</code> Compile <code>./.router</code>.</li>
                <li><code>./bin/harbor &lt;directory&gt;</code> Compile <code>&lt;directory&gt;/.router</code>.</li>
                <li><code>./bin/harbor &lt;path-to-.router&gt;</code> Compile route file.</li>
                <li><code>public/routes.php</code> Output target when a sibling <code>public/</code> directory exists.</li>
                <li><code>routes.php</code> Output target when no sibling <code>public/</code> directory exists.</li>
                <li><code>#include "./path/to/file.router"</code> Include files are expanded before parsing routes.</li>
                <li><code>Nested includes</code> Recursively processed; circular include chains fail compile.</li>
                <li><code>./bin/harbor init [site-name]</code> Create site scaffold.</li>
                <li><code>./bin/harbor -h</code> Show CLI help.</li>
            </ul>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2><code>bin/harbor-config</code></h2>
    <h3>Example</h3>
    <pre><code class="language-bash">cd my-site
../bin/harbor-config</code></pre>
    <h3>What it does</h3>
    <p>Interactive configuration publisher. Prompts for config type and publishes to the current working directory <code>./config/</code> directory.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Config Publisher CLI API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>cd my-site &amp;&amp; ../bin/harbor-config</code> Publish for a specific site directory.</li>
                <li><code>Current working directory</code> Defines where <code>config/*.php</code> files are created.</li>
                <li><code>cache.php</code>, <code>database.php</code>, <code>migration.php</code>, <code>session.php</code> Available published config templates.</li>
                <li><code>./config/cache.php</code>, <code>./config/database.php</code>, <code>./config/migration.php</code>, <code>./config/session.php</code> Target publish paths under the directory where you run the command.</li>
                <li><code>-h</code> Show command usage.</li>
            </ul>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2><code>bin/harbor-migration</code></h2>
    <h3>Example</h3>
    <pre><code class="language-bash">cd my-site
../bin/harbor-migration new "create_users_table"
../bin/harbor-migration
../bin/harbor-migration rollback</code></pre>
    <h3>What it does</h3>
    <p>Creates timestamped migration files, runs pending <code>up()</code> methods, and rolls back the latest batch with <code>down()</code>.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Migration CLI API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>cd my-site &amp;&amp; ../bin/harbor-migration new "name"</code> Create <code>database/migrations/YYYY-mm-dd-HH-ii-ss_name.php</code>.</li>
                <li><code>cd my-site &amp;&amp; ../bin/harbor-migration</code> Run pending migration files in order.</li>
                <li><code>cd my-site &amp;&amp; ../bin/harbor-migration rollback</code> Roll back the latest migration batch.</li>
                <li><code>config/migration.php</code> Defines the tracking connection + migration table/directory.</li>
            </ul>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2><code>bin/harbor-seed</code></h2>
    <h3>Example</h3>
    <pre><code class="language-bash">cd my-site
../bin/harbor-seed new "seed_permissions"
../bin/harbor-seed
../bin/harbor-seed rollback</code></pre>
    <h3>What it does</h3>
    <p>Creates timestamped seeder files, runs pending <code>up()</code> methods, and rolls back the latest seeder batch with <code>down()</code>.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Seeder CLI API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>cd my-site &amp;&amp; ../bin/harbor-seed new "name"</code> Create <code>database/seeders/YYYY-mm-dd-HH-ii-ss_name.php</code>.</li>
                <li><code>cd my-site &amp;&amp; ../bin/harbor-seed</code> Run pending seeder files in order.</li>
                <li><code>cd my-site &amp;&amp; ../bin/harbor-seed rollback</code> Roll back the latest seeder batch.</li>
                <li><code>config/migration.php</code> Defines the shared tracking connection + seeder table/directory.</li>
            </ul>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2><code>bin/harbor-docs</code></h2>
    <h3>Example</h3>
    <pre><code class="language-bash">./bin/harbor-docs
./bin/harbor-docs --port=9090</code></pre>
    <h3>What it does</h3>
    <p>Starts the local docs server on an available port.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Docs CLI API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>./bin/harbor-docs</code> Start docs server with auto port selection.</li>
                <li><code>./bin/harbor-docs --port=PORT</code> Set preferred start port.</li>
                <li><code>--port=8081</code> Default start port.</li>
                <li><code>8080</code> Reserved and skipped by design.</li>
                <li><code>./vendor/bin/harbor-docs</code> Installed package command path.</li>
            </ul>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Dev Workflow</h2>
    <h3>Example</h3>
    <pre><code class="language-bash">composer test
./vendor/bin/php-cs-fixer fix --using-cache=no --sequential
./bin/harbor documentation/.router
cd my-site && ../bin/harbor-config
cd my-site && ../bin/harbor-migration
cd my-site && ../bin/harbor-seed
./bin/harbor-docs</code></pre>
    <h3>What it does</h3>
    <p>Runs tests, formats code, compiles routes, and starts docs.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Workflow API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>composer test</code> Run PHPUnit suite.</li>
                <li><code>./vendor/bin/php-cs-fixer fix --using-cache=no --sequential</code> Apply coding style fixes.</li>
                <li><code>./bin/harbor documentation/.router</code> Compile docs route file.</li>
                <li><code>cd my-site &amp;&amp; ../bin/harbor-config</code> Publish selected config templates into that site <code>config/</code>.</li>
                <li><code>cd my-site &amp;&amp; ../bin/harbor-migration</code> Run pending migrations.</li>
                <li><code>cd my-site &amp;&amp; ../bin/harbor-seed</code> Run pending seeders.</li>
                <li><code>/migrations</code> Full guide for migrations + seeders setup and workflow.</li>
                <li><code>./bin/harbor-docs</code> Run local docs server.</li>
            </ul>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
