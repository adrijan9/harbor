<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - CLI';
$page_description = 'CLI commands for route compile, custom commands, scaffolding, tests, migrations, seeders, and local docs.';
$page_id = 'cli';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Tooling</span>
    <h1>CLI</h1>
    <p>Compile routes, create and run custom commands, scaffold sites, run tests/migrations/seeders, and run docs.</p>
    <div class="button-row">
        <a class="button button-ghost" href="/commands">Open Full Commands Guide</a>
    </div>
</section>

<section class="docs-section">
    <h2><code>bin/harbor-command</code></h2>
    <h3>Example</h3>
    <pre><code class="language-bash">cd my-site
../bin/harbor-command create users:sync
../bin/harbor-command run users:sync -- --force
../bin/harbor-command compile</code></pre>
    <h3>What it does</h3>
    <p>Manages custom site commands using <code>.commands</code> source files and compiled <code>commands/commands.php</code> registry files.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Command System CLI API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>../bin/harbor-command create &lt;key&gt;</code> Append command definition and create entry stub file.</li>
                <li><code>../bin/harbor-command create &lt;key&gt; --entry=commands/custom.php</code> Set custom entry path.</li>
                <li><code>../bin/harbor-command run &lt;key&gt; [-- &lt;args...&gt;]</code> Execute one command key and forward arguments.</li>
                <li><code>../bin/harbor-command compile [path-to-.commands|directory]</code> Compile source definitions into registry.</li>
                <li><code>--debug</code> or <code>-v</code> Enables debug output.</li>
                <li><code><a href="/commands">/commands</a></code> Full guide for creating, running, and manually compiling command definitions.</li>
            </ul>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2><code>bin/harbor</code></h2>
    <h3>Example</h3>
    <pre><code class="language-bash">./bin/harbor .
./bin/harbor documentation/.router</code></pre>
    <h3>What it does</h3>
    <p>Compiles routes (with <code>#include</code> preprocessing).</p>
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
                <li><code>./bin/harbor -h</code> Show CLI help.</li>
            </ul>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2><code>bin/harbor-init</code></h2>
    <h3>Example</h3>
    <pre><code class="language-bash">./bin/harbor-init
./bin/harbor-init my-site</code></pre>
    <h3>What it does</h3>
    <p>Creates a new site scaffold from <code>bin/stubs/site</code>.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Site Scaffold CLI API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>./bin/harbor-init [site-name]</code> Create site scaffold.</li>
                <li><code>site-name</code> Optional site directory name (default: <code>example.site</code>).</li>
                <li><code>./bin/harbor-init -h</code> Show command usage.</li>
            </ul>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2><code>bin/harbor-test</code></h2>
    <h3>Example</h3>
    <pre><code class="language-bash">cd my-site
../bin/harbor-test
../bin/harbor-test -- --filter HomePageTest
cd ..
./bin/harbor-test my-site -- --testsuite Feature</code></pre>
    <h3>What it does</h3>
    <p>Runs a site's PHPUnit tests using <code>phpunit.xml</code> from that site root. Additional PHPUnit arguments are forwarded.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Test Runner CLI API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>cd my-site &amp;&amp; ../bin/harbor-test</code> Run tests in current selected site.</li>
                <li><code>./bin/harbor-test my-site</code> Run tests for a site path from parent workspace.</li>
                <li><code>--</code> Optional separator before forwarded PHPUnit options.</li>
                <li><code>--filter Name</code>, <code>--testsuite Feature</code> Forwarded to PHPUnit unchanged.</li>
                <li><code>phpunit.xml</code> Required in the site root.</li>
                <li><code>-h</code> Show command usage.</li>
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
    <p>Interactive runtime configuration publisher. Prompts for template type and publishes to the current working directory <code>./config/</code> directory.</p>
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
                <li><code>auth.php</code>, <code>cache.php</code>, <code>database.php</code>, <code>migration.php</code>, <code>session.php</code> Available published runtime config templates.</li>
                <li><code>./config/auth.php</code>, <code>./config/cache.php</code>, <code>./config/database.php</code>, <code>./config/migration.php</code>, <code>./config/session.php</code> Target publish paths under the directory where you run the command.</li>
                <li><code>-h</code> Show command usage.</li>
            </ul>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2><code>bin/harbor-fixer</code></h2>
    <h3>Example</h3>
    <pre><code class="language-bash">cd my-site
../bin/harbor-fixer</code></pre>
    <h3>What it does</h3>
    <p>Publishes Harbor's root <code>.php-cs-fixer.dist.php</code> preset into the current site root. It does not ask for confirmation and overwrites existing file content.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Fixer Publisher CLI API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>cd my-site &amp;&amp; ../bin/harbor-fixer</code> Publish Harbor style preset into current site root.</li>
                <li><code>./.php-cs-fixer.dist.php</code> Target file path.</li>
                <li><code>.router</code> Required in current working directory (selected site check).</li>
                <li><code>Overwrite behavior</code> Existing <code>.php-cs-fixer.dist.php</code> is replaced without prompt.</li>
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
    <h2><code>bin/harbor-docs-index</code></h2>
    <h3>Example</h3>
    <pre><code class="language-bash">./bin/harbor-docs-index
./bin/harbor-docs-index --output=documentation/public/assets/search-index.json</code></pre>
    <h3>What it does</h3>
    <p>Builds the docs search index JSON from <code>documentation/.router</code> and page files.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Docs Search Index CLI API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>./bin/harbor-docs-index</code> Generate <code>documentation/public/assets/search-index.json</code>.</li>
                <li><code>--output=PATH</code> Write the generated JSON to a custom path.</li>
                <li><code>Re-index after docs edits</code> Run this command after every docs content change so search results stay accurate.</li>
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
./bin/harbor-docs-index
cd my-site && ../bin/harbor-config
cd my-site && ../bin/harbor-test
cd my-site && ../bin/harbor-fixer
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
                <li><code>./bin/harbor-docs-index</code> Rebuild docs search index JSON after docs updates.</li>
                <li><code>cd my-site &amp;&amp; ../bin/harbor-config</code> Publish selected runtime config templates into that site <code>config/</code>.</li>
                <li><code>cd my-site &amp;&amp; ../bin/harbor-test</code> Run site PHPUnit suite using that site's <code>phpunit.xml</code>.</li>
                <li><code>cd my-site &amp;&amp; ../bin/harbor-fixer</code> Publish Harbor <code>.php-cs-fixer.dist.php</code> preset into that site root.</li>
                <li><code>cd my-site &amp;&amp; ../bin/harbor-migration</code> Run pending migrations.</li>
                <li><code>cd my-site &amp;&amp; ../bin/harbor-seed</code> Run pending seeders.</li>
                <li><code>/migrations</code> Full guide for migrations + seeders setup and workflow.</li>
                <li><code>./bin/harbor-docs</code> Run local docs server.</li>
            </ul>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
