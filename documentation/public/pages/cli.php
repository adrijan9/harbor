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
                <li><code>Current working directory</code> Defines where <code>config/cache.php</code> is created.</li>
                <li><code>cache.php</code> Currently available published config template.</li>
                <li><code>./config/cache.php</code> Target publish path under the directory where you run the command.</li>
                <li><code>-h</code> Show command usage.</li>
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
                <li><code>./bin/harbor-docs</code> Run local docs server.</li>
            </ul>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
