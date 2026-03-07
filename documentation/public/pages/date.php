<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Date Helpers';
$page_description = 'Carbon wrapper helpers for date/time creation and chaining.';
$page_id = 'date';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">namespace: date</span>
    <h1>Date Helpers</h1>
    <p>Create Harbor Carbon instances with <code>carbon()</code> and <code>date_now()</code>.</p>
</section>

<section class="docs-section">
    <h2>Basic Usage</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Date\carbon;
use function Harbor\Date\date_now;

$created_at = carbon('2026-03-08 10:00:00', 'UTC')
    ->addDays(2)
    ->startOfDay();

$current = date_now('UTC');
</code></pre>
    <h3>What it does</h3>
    <p>Returns <code>Harbor\Date\Carbon</code> instances so you can chain Carbon-style date operations.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Date API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">final class Carbon extends \Carbon\Carbon
{
    // Harbor wrapper class.
}

function carbon(DateTimeInterface|string|null $time = null, DateTimeZone|string|null $timezone = null): Carbon
function date_now(DateTimeZone|string|null $timezone = null): Carbon</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Notes</h2>
    <ul class="api-method-list">
        <li>Harbor requires <code>nesbot/carbon</code> in <code>composer.json</code>.</li>
        <li>Load with <code>HelperLoader::load('carbon')</code>.</li>
        <li><code>date_now()</code> is equivalent to <code>carbon('now')</code> with optional timezone.</li>
    </ul>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
