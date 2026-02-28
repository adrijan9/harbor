<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Units Helpers';
$page_description = 'Convert bytes and time values to kb/mb/gb/tb and human-readable output.';
$page_id = 'units';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">namespace: units</span>
    <h1>Units Helpers</h1>
    <p>Convert between bytes-based units and format values for readable performance output.</p>
</section>

<section class="docs-section">
    <h2>Unit Conversion</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Units\unit_bytes_from_mb;
use function Harbor\Units\unit_kb_from_mb;
use function Harbor\Units\unit_mb_from_bytes;
use function Harbor\Units\unit_mb_from_kb;

$kb = unit_kb_from_mb(2);           // 2048
$mb = unit_mb_from_kb(1536);        // 1.5
$bytes = unit_bytes_from_mb(8);     // 8388608
$memory_mb = unit_mb_from_bytes(6291456); // 6</code></pre>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Conversion API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function unit_kb_from_bytes(int|float $bytes, int $precision = 3): float
function unit_mb_from_bytes(int|float $bytes, int $precision = 3): float
function unit_gb_from_bytes(int|float $bytes, int $precision = 3): float
function unit_tb_from_bytes(int|float $bytes, int $precision = 3): float

function unit_bytes_from_kb(int|float $kilobytes, int $precision = 3): float
function unit_bytes_from_mb(int|float $megabytes, int $precision = 3): float
function unit_bytes_from_gb(int|float $gigabytes, int $precision = 3): float
function unit_bytes_from_tb(int|float $terabytes, int $precision = 3): float

function unit_kb_from_mb(int|float $megabytes, int $precision = 3): float
function unit_mb_from_kb(int|float $kilobytes, int $precision = 3): float
function unit_mb_from_gb(int|float $gigabytes, int $precision = 3): float
function unit_gb_from_mb(int|float $megabytes, int $precision = 3): float
function unit_gb_from_tb(int|float $terabytes, int $precision = 3): float
function unit_tb_from_gb(int|float $gigabytes, int $precision = 3): float</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Readable Formatting</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Units\unit_bytes_to_human;
use function Harbor\Units\unit_duration_ms_to_human;

$memory = unit_bytes_to_human(7340032);      // "7 MB"
$duration = unit_duration_ms_to_human(1425); // "1.425 s"</code></pre>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Readable API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function unit_bytes_to_human(int|float $bytes, int $precision = 3): string
// Formats bytes to B, KB, MB, GB, TB, or PB.

function unit_duration_ms_to_human(int|float $duration_ms, int $precision = 3): string
// Formats duration milliseconds to ms, s, min, h, or d.</code></pre>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
