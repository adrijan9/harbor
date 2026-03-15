<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Rate Limiter Helpers';
$page_description = 'Window-based rate limiter helpers for attempts, remaining slots, and retry windows.';
$page_id = 'rate_limiter';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">namespace: rate_limiter</span>
    <h1>Rate Limiter Helpers</h1>
    <p>Track request attempts in fixed windows and reuse the same limiter state across helpers and middleware.</p>
</section>

<section class="docs-section">
    <h2>Basic Usage</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\RateLimiter\rate_limiter_attempts;
use function Harbor\RateLimiter\rate_limiter_available_in;
use function Harbor\RateLimiter\rate_limiter_hit;
use function Harbor\RateLimiter\rate_limiter_remaining;
use function Harbor\RateLimiter\rate_limiter_too_many_attempts;

$key = 'login:198.51.100.42';

if (rate_limiter_too_many_attempts($key, 5)) {
    $retry_after = rate_limiter_available_in($key);
    // Return 429 with Retry-After header
}

rate_limiter_hit($key, 60);

$attempts = rate_limiter_attempts($key);
$remaining = rate_limiter_remaining($key, 5);</code></pre>
    <h3>What it does</h3>
    <p>Keeps attempt counters in cache with TTL-backed windows. Once a window expires, attempts reset automatically.</p>
</section>

<section class="docs-section">
    <h2>Middleware Integration</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\Middleware\ThrottleMiddleware;
use function Harbor\Middleware\middleware;

middleware(
    new ThrottleMiddleware(
        max_attempts: 60,
        decay_seconds: 60,
    )
);</code></pre>
    <h3>What it does</h3>
    <p><code>ThrottleMiddleware</code> uses these helpers internally, so manual limiter hits and middleware checks share the same counter state when keys match.</p>
</section>

<section class="docs-section">
    <h2>API</h2>
    <details class="api-details">
        <summary class="api-summary">
            <span>Rate Limiter API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function rate_limiter_hit(string $key, int $decay_seconds = 60, int $amount = 1): int
// Adds attempts for the key and returns total attempts in the current window.
$attempts = rate_limiter_hit('login:198.51.100.42', 60);

function rate_limiter_attempts(string $key): int
// Returns attempts already recorded for the key in the current window.
$attempts = rate_limiter_attempts('login:198.51.100.42');

function rate_limiter_too_many_attempts(string $key, int $max_attempts): bool
// True when current attempts are >= max_attempts.
$blocked = rate_limiter_too_many_attempts('login:198.51.100.42', 5);

function rate_limiter_remaining(string $key, int $max_attempts): int
// Returns remaining attempts (never below 0).
$remaining = rate_limiter_remaining('login:198.51.100.42', 5);

function rate_limiter_available_in(string $key): int
// Returns seconds until the current window resets (0 when no active window exists).
$retry_after = rate_limiter_available_in('login:198.51.100.42');

function rate_limiter_clear(string $key): bool
// Removes limiter state for one key.
rate_limiter_clear('login:198.51.100.42');</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Notes</h2>
    <ul class="api-method-list">
        <li>Load with <code>Helper::load_many('rate_limiter')</code>.</li>
        <li>Limiter keys are normalized and stored using cache-backed bucket entries.</li>
        <li>Blank keys throw <code>InvalidArgumentException</code>.</li>
        <li><code>rate_limiter_hit()</code> supports batched increments through <code>$amount</code>.</li>
    </ul>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
