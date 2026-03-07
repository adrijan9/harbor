<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Migrations & Seeders';
$page_description = 'Run tracked migrations and seeders with Harbor CLI commands.';
$page_id = 'migrations';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Tooling</span>
    <h1>Migrations &amp; Seeders</h1>
    <p>Use file-based up/down scripts with batch tracking for both schema and repeatable data changes.</p>
</section>

<section class="docs-section">
    <h2>Setup</h2>
    <h3>Example</h3>
    <pre><code class="language-bash">cd my-site
../bin/harbor-config
# choose: migration.php</code></pre>
    <h3>What it does</h3>
    <p>Publishes <code>config/migration.php</code> which defines one tracker connection and both directories/tables.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Config API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">return [
    'driver' => 'sqlite'|'mysql'|'mysqli',
    'sqlite' => [...],
    'mysql' => [...],
    'migrations' => [
        'directory' => __DIR__.'/../database/migrations',
        'table' => 'migrations',
    ],
    'seeders' => [
        'directory' => __DIR__.'/../database/seeders',
        'table' => 'seeders',
    ],
];</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Create Files</h2>
    <h3>Example</h3>
    <pre><code class="language-bash">cd my-site
../bin/harbor-migration new "create_users_table"
../bin/harbor-seed new "seed_permissions"</code></pre>
    <h3>What it does</h3>
    <p>Creates timestamped files in <code>database/migrations</code> and <code>database/seeders</code>.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Create API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>../bin/harbor-migration new "name"</code> Creates <code>YYYY-mm-dd-HH-ii-ss_name.php</code> under <code>database/migrations</code>.</li>
                <li><code>../bin/harbor-seed new "name"</code> Creates <code>YYYY-mm-dd-HH-ii-ss_name.php</code> under <code>database/seeders</code>.</li>
            </ul>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>File Template</h2>
    <h3>Example</h3>
    <pre><code class="language-php">&lt;?php

declare(strict_types=1);

require __DIR__.'/../../../vendor/autoload.php';

// $connection = \Harbor\Database\db_sqlite_connect(__DIR__.'/../db.sqlite');
// or
// include/require some global file that defines the connection

return new class {
    public function up(): void
    {
        // Do the stuff
    }

    public function down(): void
    {
        // Do the stuff
    }
};</code></pre>
    <h3>What it does</h3>
    <p><code>up()</code> is executed on run; <code>down()</code> is executed on rollback. Migration and seeder files share this shape.</p>
</section>

<section class="docs-section">
    <h2>Run And Rollback</h2>
    <h3>Example</h3>
    <pre><code class="language-bash">cd my-site
../bin/harbor-migration
../bin/harbor-seed

../bin/harbor-migration rollback
../bin/harbor-seed rollback</code></pre>
    <h3>What it does</h3>
    <p>Runs all pending files in timestamp order and stores each file name with a batch. Rollback reverts the latest batch only.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Run API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>migrations</code> tracking table columns: <code>name</code>, <code>batch</code>.</li>
                <li><code>seeders</code> tracking table columns: <code>name</code>, <code>batch</code>.</li>
                <li><code>rollback</code> rolls back the latest batch and removes its tracking rows.</li>
                <li>Because rolled-back rows are removed, the same seeder can run again later.</li>
            </ul>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
