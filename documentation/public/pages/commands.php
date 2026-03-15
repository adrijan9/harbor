<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Commands';
$page_description = 'Create, run, and compile custom site commands with harbor-command and .commands.';
$page_id = 'commands';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Tooling</span>
    <h1>Commands</h1>
    <p>Use <code>harbor-command</code> to define, compile, and run custom site commands from your Harbor project root.</p>
</section>

<section class="docs-section">
    <h2>Quick Start</h2>
    <h3>Example</h3>
    <pre><code class="language-bash">cd my-site
../bin/harbor-command create users:sync
../bin/harbor-command run users:sync -- --dry-run</code></pre>
    <h3>What it does</h3>
    <p>Creates <code>.commands</code>, creates <code>commands/users_sync.php</code>, compiles <code>commands/commands.php</code>, then runs the selected key.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Quick Start API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>cd my-site</code> Command system must run from a Harbor site root (directory that contains <code>.router</code>).</li>
                <li><code>create users:sync</code> Key format: lowercase letters/numbers with <code>_</code>, <code>-</code>, and <code>:</code>.</li>
                <li><code>run users:sync -- ...</code> Everything after <code>--</code> is forwarded to the command entry script as argv values.</li>
            </ul>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Create Commands</h2>
    <h3>Example</h3>
    <pre><code class="language-bash">cd my-site
../bin/harbor-command create cache:warm
../bin/harbor-command create users:import --entry=commands/import_users.php --name="Import Users" --description="Import users from CSV" --timeout=120
../bin/harbor-command create debug:sample --disabled</code></pre>
    <h3>What it does</h3>
    <p>Appends command blocks to <code>.commands</code>, creates missing entry files, and compiles a fresh command registry.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Create API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>create &lt;key&gt;</code> Creates default entry file at <code>commands/&lt;key-with-colons-replaced-by-underscores&gt;.php</code>.</li>
                <li><code>--entry=path</code> Overrides command entry path (relative to site root, or absolute path).</li>
                <li><code>--name=value</code> Optional display name in registry.</li>
                <li><code>--description=value</code> Optional description in registry.</li>
                <li><code>--timeout=seconds</code> Optional positive integer timeout for run execution.</li>
                <li><code>--disabled</code> Stores command with <code>enabled: false</code>. Use <code>--enabled</code> to force enabled.</li>
                <li><code>Command key rules</code> Cannot use reserved keys: <code>create</code>, <code>run</code>, <code>compile</code>, <code>list</code>, <code>show</code>, <code>delete</code>, <code>update</code>, <code>help</code>.</li>
            </ul>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Run Commands</h2>
    <h3>Example</h3>
    <pre><code class="language-bash">cd my-site
../bin/harbor-command run users:import -- --file=storage/users.csv --chunk=500
../bin/harbor-command --debug run users:import</code></pre>
    <h3>What it does</h3>
    <p>Loads <code>commands/commands.php</code>, resolves entry file for the key, then executes it as a PHP process.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Run API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>run &lt;key&gt;</code> Executes one compiled command definition by key.</li>
                <li><code>Registry auto-rebuild</code> If <code>commands/commands.php</code> is missing and <code>.commands</code> exists, Harbor recompiles automatically before running.</li>
                <li><code>Disabled command</code> Commands with <code>enabled: false</code> are blocked from execution.</li>
                <li><code>Exit codes</code> Missing key or missing registry returns specific non-zero exits; process errors and timeouts fail execution.</li>
                <li><code>--debug</code> or <code>-v</code> Prints debug diagnostics (paths, execution context, timeout).</li>
            </ul>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Manual Definitions</h2>
    <h3>Example</h3>
    <pre><code class="language-ini"># File: my-site/.commands
#include "./commands/core.commands"

&lt;command&gt;
    key: users:export
    entry: commands/users_export.php
    name: "Export Users"
    description: "Exports users to CSV"
    timeout_seconds: 90
    enabled: true
&lt;/command&gt;</code></pre>
    <pre><code class="language-ini"># File: my-site/commands/core.commands
&lt;command&gt;
    key: cache:warm
    entry: commands/cache_warm.php
    enabled: true
&lt;/command&gt;</code></pre>
    <pre><code class="language-php">&lt;?php

declare(strict_types=1);

// File: my-site/commands/users_export.php
$arguments = $argv ?? [];
array_shift($arguments);

fwrite(STDOUT, sprintf("users:export args: %s%s", json_encode($arguments), PHP_EOL));</code></pre>
    <pre><code class="language-bash">cd my-site
../bin/harbor-command compile
../bin/harbor-command run users:export -- --format=csv</code></pre>
    <h3>What it does</h3>
    <p>Lets you split command definitions across files, compile them into a registry, and run keys normally.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Definition API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>Root file</code> Keep the main source at <code>my-site/.commands</code>.</li>
                <li><code>#include</code> Use <code>#include "./commands/core.commands"</code> to load other definition files.</li>
                <li><code>Relative include path</code> Resolved from the file that contains the <code>#include</code> line.</li>
                <li><code>Required fields</code> <code>key</code> and <code>entry</code> are required in every <code>&lt;command&gt;</code> block.</li>
                <li><code>Optional fields</code> <code>name</code>, <code>description</code>, <code>timeout_seconds</code>, <code>enabled</code>, <code>created_at</code>, <code>updated_at</code>.</li>
                <li><code>After manual edits</code> Run <code>../bin/harbor-command compile</code> to regenerate <code>commands/commands.php</code>.</li>
                <li><code>Validation</code> Nested command blocks, invalid field syntax, duplicate keys, invalid booleans, and circular includes fail compile.</li>
            </ul>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Compile Definitions</h2>
    <h3>Example</h3>
    <pre><code class="language-bash">cd my-site
../bin/harbor-command compile
../bin/harbor-command compile .
../bin/harbor-command compile .commands
../bin/harbor-command compile /absolute/path/to/site/.commands</code></pre>
    <h3>What it does</h3>
    <p>Compiles command definitions into a PHP registry file at <code>commands/commands.php</code>.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Compile API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>compile</code> Uses current directory <code>.commands</code> and writes <code>commands/commands.php</code>.</li>
                <li><code>compile &lt;directory&gt;</code> Reads <code>&lt;directory&gt;/.commands</code> and writes <code>&lt;directory&gt;/commands/commands.php</code>.</li>
                <li><code>compile &lt;path-to-.commands&gt;</code> Reads source file and writes sibling <code>commands/commands.php</code>.</li>
                <li><code>Invalid path argument</code> Must be a directory or a file path ending with <code>.commands</code>.</li>
            </ul>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
