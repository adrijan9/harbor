<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Cookie Helpers';
$page_description = 'Cookie read/write helpers for HTTP cookie state.';
$page_id = 'cookie';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">namespace: cookie</span>
    <h1>Cookie Helpers</h1>
    <p>Simple cookie set/get/forget helpers with option support.</p>
</section>

<section class="docs-section">
    <h2>Read and Write Cookies</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Cookie\cookie_forget;
use function Harbor\Cookie\cookie_get;
use function Harbor\Cookie\cookie_set;

cookie_set('theme', 'dark', 3600, [
    'path' => '/',
    'secure' => true,
    'http_only' => true,
    'same_site' => 'Lax',
]);

$theme = cookie_get('theme', 'light');

cookie_forget('theme');</code></pre>
    <h3>What it does</h3>
    <p>Writes or removes response cookies and mirrors values in <code>$_COOKIE</code> for immediate runtime reads.</p>

    <h3>Security Note</h3>
    <p>Cookie values are plain cookie values by default and are <strong>not signed</strong> and <strong>not encrypted</strong> by Harbor. Avoid storing sensitive values directly unless you sign or encrypt them yourself.</p>

    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Cookie Helper API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function cookie_set(string $key, string $value, int $ttl_seconds = 0, array $options = []): bool
// Sets one cookie value and returns success state.
// ttl_seconds: 0 = browser session cookie.
$ok = cookie_set('theme', 'dark', 3600);

function cookie_get(?string $key = null, mixed $default = null): mixed
// Reads one cookie value or returns full cookie map when key is null.
$theme = cookie_get('theme', 'light');
$all = cookie_get();

function cookie_has(string $key): bool
// Checks if one cookie key exists.
$has_theme = cookie_has('theme');

function cookie_forget(string $key, array $options = []): bool
// Expires one cookie key immediately.
$deleted = cookie_forget('theme');

function cookie_all(): array
// Returns full cookie map from $_COOKIE.
$cookies = cookie_all();</code></pre>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
