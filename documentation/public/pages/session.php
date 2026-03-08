<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Session Helpers';
$page_description = 'Cookie-backed session helpers using config/session.php values.';
$page_id = 'session';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">namespace: session</span>
    <h1>Session Helpers</h1>
    <p>Simplified cookie-backed sessions configured by <code>config/session.php</code>.</p>
</section>

<section class="docs-section">
    <h2>Configure Session Cookies</h2>
    <h3>Example</h3>
    <pre><code class="language-php">// file: config/session.php
return [
    'prefix' => 'harbor',
    'ttl_seconds' => 7200,
    'path' => '/',
    'domain' => null,
    'secure' => false,
    'http_only' => true,
    'same_site' => 'Lax',
];</code></pre>
    <h3>What it does</h3>
    <p>Defines cookie naming and security options used by all session helper calls.</p>
</section>

<section class="docs-section">
    <h2>Security Note</h2>
    <p>Session values are stored in client cookies and are <strong>not signed</strong> and <strong>not encrypted</strong> by Harbor. Do not store sensitive data in session cookies unless you add your own signing or encryption layer.</p>
</section>

<section class="docs-section">
    <h2>Use Session Helpers</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Session\session_forget;
use function Harbor\Session\session_get;
use function Harbor\Session\session_set;

session_set('user_id', 42);

$user_id = session_get('user_id');

session_forget('user_id');</code></pre>
    <h3>What it does</h3>
    <p>Stores and reads session values directly from cookies with a shared prefix and cookie options from config.</p>

    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Session Helper API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function session_set(string $key, mixed $value, ?int $ttl_seconds = null): bool
// Stores one session value in a prefixed cookie.
// Uses config/session.php defaults when ttl_seconds is null.
session_set('user_id', 42);

function session_get(?string $key = null, mixed $default = null): mixed
// Reads one session value or all session values when key is null.
$user_id = session_get('user_id', 0);
$all = session_get();

function session_has(string $key): bool
// Checks if one session key exists.
$has_user = session_has('user_id');

function session_forget(string $key): bool
// Removes one session key.
session_forget('user_id');

function session_pull(string $key, mixed $default = null): mixed
// Reads and removes one session key in one call.
$flash = session_pull('flash_notice', '');

function session_all(): array
// Returns all session entries for the configured prefix.
$sessions = session_all();

function session_clear(): bool
// Removes all session entries for the configured prefix.
session_clear();

function session_config(?string $key = null, mixed $default = null): mixed
// Reads session config values.
$prefix = session_config('prefix', 'harbor');</code></pre>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
