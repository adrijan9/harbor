<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Filesystem Helpers';
$page_description = 'Filesystem helpers for file and directory operations.';
$page_id = 'filesystem';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Helpers</span>
    <h1>Filesystem Helpers</h1>
    <p>Simple file and directory operations with clear errors.</p>
</section>

<section class="docs-section">
    <h2>Load Helpers</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use PhpFramework\HelperLoader;

HelperLoader::load('filesystem');</code></pre>
    <h3>What it does</h3>
    <p>Loads filesystem helper functions.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Filesystem Loader API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>HelperLoader::load('filesystem')</code> Load filesystem helper module.</li>
            </ul>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>File Ops</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function PhpFramework\Filesystem\fs_append;
use function PhpFramework\Filesystem\fs_read;
use function PhpFramework\Filesystem\fs_write;

fs_write(__DIR__.'/cache/output.txt', 'Build started');
fs_append(__DIR__.'/cache/output.txt', PHP_EOL.'Build completed');
$content = fs_read(__DIR__.'/cache/output.txt');</code></pre>
    <h3>What it does</h3>
    <p>Creates, updates, reads, and manages files.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>File API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>fs_read()</code> Read file content.</li>
                <li><code>fs_write()</code> Write file content.</li>
                <li><code>fs_append()</code> Append file content.</li>
                <li><code>fs_exists()</code> Check file existence.</li>
                <li><code>fs_size()</code> Get file size in bytes.</li>
                <li><code>fs_copy()</code> Copy file to destination.</li>
                <li><code>fs_move()</code> Move or rename file.</li>
                <li><code>fs_delete()</code> Delete file.</li>
            </ul>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Directory Ops</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function PhpFramework\Filesystem\fs_dir_create;
use function PhpFramework\Filesystem\fs_dir_delete;
use function PhpFramework\Filesystem\fs_dir_list;

fs_dir_create(__DIR__.'/storage/reports');
$files = fs_dir_list(__DIR__.'/storage/reports');
fs_dir_delete(__DIR__.'/storage/reports', true);</code></pre>
    <h3>What it does</h3>
    <p>Creates, lists, checks, and deletes directories.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Directory API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>fs_dir_exists()</code> Check directory existence.</li>
                <li><code>fs_dir_create()</code> Create directory path.</li>
                <li><code>fs_dir_is_empty()</code> Check if directory is empty.</li>
                <li><code>fs_dir_list()</code> List directory entries.</li>
                <li><code>fs_dir_delete()</code> Delete directory (optional recursive).</li>
            </ul>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
