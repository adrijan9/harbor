<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Session Helpers';
$page_description = 'Session helpers using cookie, array, or file drivers from config/session.php.';
$page_id = 'session';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">namespace: session</span>
    <h1>Session Helpers</h1>
    <p>Simplified sessions configured by <code>config/session.php</code> with cookie, array, or file drivers.</p>
</section>

<section class="docs-section">
    <h2>Configure Session Cookies</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\Session\SessionDriver;

// file: config/session.php
return [
    'driver' => SessionDriver::COOKIE->value, // cookie | array | file
    'prefix' => 'harbor',
    'ttl_seconds' => 7200,
    'path' => '/',
    'domain' => null,
    'secure' => false,
    'http_only' => true,
    'same_site' => 'Lax',
    'signed' => false,
    'encrypted' => false,
    'key' => null,
    'signing_key' => null,
    'encryption_key' => null,
    'file_path' => __DIR__.'/../storage/session',
    'id_cookie' => 'harbor-session-id',
];</code></pre>
    <h3>What it does</h3>
    <p>Defines session driver and options used by all session helper calls. For cookie driver, signing/encryption options protect cookie payloads. For file driver, payloads are server-side and the browser stores only the session id cookie.</p>
</section>

<section class="docs-section">
    <h2>Security Note</h2>
    <p>Cookie driver stores values in browser cookies. They are plain by default, but Harbor can sign and/or encrypt them when <code>session.signed</code> or <code>session.encrypted</code> is enabled and keys are configured. Encrypted cookies require the PHP OpenSSL extension.</p>
</section>

<section class="docs-section">
    <h2>Flash Data (Next Request Messaging)</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Session\session_flash_get;
use function Harbor\Session\session_flash_set;

// request A (before redirect)
session_flash_set('notice', 'Profile updated');

// request B (after redirect)
$notice = session_flash_get('notice');</code></pre>
    <h3>What it does</h3>
    <p>Flash values are designed for redirect-style messaging between requests. Harbor keeps flash payloads for the next request cycle and then expires them automatically.</p>
    <p>Validation form-flow helpers use this same flash layer under the hood for error bags and old input.</p>
</section>

<section class="docs-section">
    <h2>Use Session Helpers</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Session\session_flash_get;
use function Harbor\Session\session_flash_set;
use function Harbor\Session\session_forget;
use function Harbor\Session\session_get;
use function Harbor\Session\session_set;

session_set('user_id', 42);

$user_id = session_get('user_id');

session_flash_set('notice', 'Profile saved');
$notice = session_flash_get('notice');

session_forget('user_id');</code></pre>
    <h3>What it does</h3>
    <p>Provides one session API that automatically dispatches to the configured driver (<code>cookie</code>, <code>array</code>, or <code>file</code>).</p>

    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Session Helper API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function session_set(string $key, mixed $value, ?int $ttl_seconds = null): bool
// Stores one session value in active session driver.
// Uses config/session.php defaults when ttl_seconds is null.
session_set('user_id', 42);

function session_driver(SessionDriver|string $default_driver = SessionDriver::COOKIE): string
// Returns active driver: cookie, array, or file.
$driver = session_driver();

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

function session_flash_set(string $key, mixed $value): bool
// Stores flash value for current + next request cycle.
session_flash_set('notice', 'Profile updated');

function session_flash_get(string $key, mixed $default = null): mixed
// Reads one flash value.
$notice = session_flash_get('notice', null);

function session_flash_has(string $key): bool
// Checks if one flash value exists.
$has_notice = session_flash_has('notice');

function session_flash_forget(string $key): bool
// Removes one flash value.
session_flash_forget('notice');

function session_flash_pull(string $key, mixed $default = null): mixed
// Reads and removes one flash value in one call.
$notice = session_flash_pull('notice', null);

function session_flash_all(): array
// Returns all active flash values.
$flash_values = session_flash_all();

function session_flash_clear(): bool
// Clears all active flash values.
session_flash_clear();

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

    <h3>Driver-Specific API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Session Driver Helpers API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">// Array driver helpers (in-memory runtime storage)
function session_array_set(string $key, mixed $value, int $ttl_seconds = 0): bool
function session_array_get(string $key, mixed $default = null): mixed
function session_array_has(string $key): bool
function session_array_forget(string $key): bool
function session_array_all(): array
function session_array_clear(): bool

// File driver helpers (server-side payload files + session id cookie)
function session_file_set_path(string $path): void
// Overrides runtime session file root path.

function session_file_reset_path(): void
// Resets runtime path override and falls back to config/session.php.

function session_file_set(string $key, mixed $value, int $ttl_seconds, array $cookie_options = []): bool
function session_file_get(string $key, mixed $default = null, array $cookie_options = []): mixed
function session_file_has(string $key, array $cookie_options = []): bool
function session_file_forget(string $key, array $cookie_options = []): bool
function session_file_all(array $cookie_options = []): array
function session_file_clear(array $cookie_options = []): bool</code></pre>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
