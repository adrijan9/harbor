<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Performance Helpers';
$page_description = 'Measure execution time and memory usage between start and end markers.';
$page_id = 'performance';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Helpers</span>
    <h1>Performance Helpers</h1>
    <p>Measure elapsed time and memory usage with explicit start/end markers.</p>
</section>

<section class="docs-section">
    <h2>Load Helper</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\HelperLoader;

HelperLoader::load('performance');</code></pre>
    <h3>What it does</h3>
    <p>Loads performance helpers into the <code>Harbor\Performance</code> namespace.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Performance Loader API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">HelperLoader::load(string $helper_name): void
// Loads helper module by name.
// Use "performance" to register performance helpers.
HelperLoader::load('performance');</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Measure Blocks</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Performance\performance_end_log;
use function Harbor\Performance\performance_begin;
use function Harbor\Performance\performance_end;

performance_begin('db.query');
// ... work ...
$metrics = performance_end('db.query');

performance_begin('request');
// ... work ...
$logged_metrics = performance_end_log('request', '[harbor.performance]');
$delta_human = $logged_metrics['memory_usage_delta_human'];</code></pre>
    <h3>What it does</h3>
    <p>Starts a marker at one point and ends it later, returning timing and memory metrics as an array with raw and human-readable values.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Performance Helper API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function performance_begin(?string $marker = null): void
// Begins (or resets) one marker.
// Uses "default" marker when omitted.
performance_begin();

function performance_end(?string $marker = null): array
// Ends one marker and returns metrics array.
// Throws when marker was not started.
$metrics = performance_end('db.query');

function performance_end_log(?string $marker = null, ?string $prefix = null): array
// Same as performance_end(), then logs with Harbor log helpers.
// Writes into current_site_directory/logs/performance_Y-m-d-H-s-i_tracking.log.
// Optional prefix defaults to "[harbor.performance]".
$metrics = performance_end_log('request', '[perf]');

// Returned array keys:
// marker, started_at_unix, ended_at_unix, duration_ms, duration_human,
// start_memory_usage_bytes, end_memory_usage_bytes, memory_usage_delta_bytes,
// start_memory_usage_human, end_memory_usage_human, memory_usage_delta_human,
// start_peak_memory_usage_bytes, end_peak_memory_usage_bytes, peak_memory_usage_delta_bytes,
// start_peak_memory_usage_human, end_peak_memory_usage_human, peak_memory_usage_delta_human</code></pre>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
