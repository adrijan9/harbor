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
    <pre><code class="language-php">use PhpFramework\HelperLoader;

HelperLoader::load('filesystem');</code></pre>
</section>

<section class="docs-section">
    <h2>File Operations</h2>
    <ul>
        <li><code>fs_read()</code>, <code>fs_write()</code>, <code>fs_append()</code></li>
        <li><code>fs_exists()</code>, <code>fs_size()</code></li>
        <li><code>fs_copy()</code>, <code>fs_move()</code>, <code>fs_delete()</code></li>
    </ul>
    <pre><code class="language-php">use function PhpFramework\Filesystem\fs_append;
use function PhpFramework\Filesystem\fs_write;

fs_write(__DIR__.'/cache/output.txt', 'Build started');
fs_append(__DIR__.'/cache/output.txt', PHP_EOL.'Build completed');</code></pre>
</section>

<section class="docs-section">
    <h2>Directory Ops</h2>
    <ul>
        <li><code>fs_dir_create()</code>, <code>fs_dir_exists()</code></li>
        <li><code>fs_dir_list()</code>, <code>fs_dir_is_empty()</code></li>
        <li><code>fs_dir_delete()</code> (recursive optional)</li>
    </ul>
    <pre><code class="language-php">use function PhpFramework\Filesystem\fs_dir_create;
use function PhpFramework\Filesystem\fs_dir_delete;
use function PhpFramework\Filesystem\fs_dir_list;

fs_dir_create(__DIR__.'/storage/reports');
$files = fs_dir_list(__DIR__.'/storage/reports');
fs_dir_delete(__DIR__.'/storage/reports', true);</code></pre>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
