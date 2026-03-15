<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Auth Helpers';
$page_description = 'Split auth helpers for web sessions and API tokens with config/auth.php.';
$page_id = 'auth';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">namespace: auth</span>
    <h1>Auth Helpers</h1>
    <p>Use <code>auth_web_*</code> for session auth and <code>auth_api_*</code> for token auth. Configure both in <code>config/auth.php</code>.</p>
</section>

<section class="docs-section">
    <h2>Publish Auth Config</h2>
    <h3>Example</h3>
    <pre><code class="language-bash">cd my-site
../bin/harbor-config</code></pre>
    <h3>What it does</h3>
    <p>Choose <code>auth.php</code> from the prompt to publish <code>./config/auth.php</code>. This file has two keys: <code>web</code> and <code>api</code>.</p>
    <pre><code class="language-php">// public/index.php
use Harbor\Helper;
use function Harbor\Config\config_init;

Helper::load_many('config');
config_init(__DIR__.'/../config/auth.php');</code></pre>
</section>

<section class="docs-section">
    <h2>Web Guard (<code>auth.web</code>)</h2>
    <h3>Config</h3>
    <pre><code class="language-php">// config/auth.php
return [
    'web' => [
        'session_key' => 'auth_web_user',
        'attempt_resolver' => static function (array $credentials): ?array {
            // Verify credentials and return user payload or null.
            return null;
        },
    ],
    'api' => [
        // ...
    ],
];</code></pre>
    <h3>Login + Protected Route</h3>
    <pre><code class="language-php">use Harbor\Middleware\WebAuthMiddleware;
use function Harbor\Auth\auth_attempt;
use function Harbor\Auth\auth_web_exists;
use function Harbor\Auth\auth_web_login;
use function Harbor\Auth\auth_web_logout;
use function Harbor\Middleware\middleware;
use function Harbor\Response\response_header;
use function Harbor\Response\response_status;

// load only web helpers for this file
Helper::load_many('auth_web', 'middleware', 'response');

// login handler
$user = auth_attempt([
    'email' => $_POST['email'] ?? '',
    'password' => $_POST['password'] ?? '',
]);

if (null === $user) {
    response_status(302);
    response_header('Location', '/login?error=invalid');
    exit;
}

if (! auth_web_exists()) {
    auth_web_login($user);
}

response_status(302);
response_header('Location', '/dashboard');
exit;

// protected pages
middleware(new WebAuthMiddleware(login_path: '/login'));

// logout handler
auth_web_logout();
response_status(302);
response_header('Location', '/login');
exit;</code></pre>
</section>

<section class="docs-section">
    <h2>API Guard (<code>auth.api</code>)</h2>
    <h3>Config</h3>
    <pre><code class="language-php">// config/auth.php
return [
    'web' => [
        // ...
    ],
    'api' => [
        'secret' => 'replace-with-strong-secret-at-least-32-bytes',
        'issuer' => 'https://api.example.com',
        'audience' => 'example-api',
        'ttl_seconds' => 3600,
        'leeway_seconds' => 0,
        'revoke_store_path' => __DIR__.'/../storage/auth/revoked_tokens.json',
        'attempt_resolver' => static fn (array $credentials): ?array => null,
        'user_resolver' => static fn (array $payload): ?array => [
            'id' => (int) ($payload['sub'] ?? 0),
        ],
    ],
];</code></pre>
    <h3>Login + Protected Endpoint</h3>
    <pre><code class="language-php">use Harbor\Middleware\ApiAuthMiddleware;
use function Harbor\Auth\auth_api_exists;
use function Harbor\Auth\auth_api_get;
use function Harbor\Auth\auth_api_login;
use function Harbor\Auth\auth_api_logout;
use function Harbor\Auth\auth_attempt;
use function Harbor\Middleware\middleware;
use function Harbor\Response\response_json;

// load only api helpers for this file
Helper::load_many('auth_api', 'middleware', 'response');

// login endpoint
$user = auth_attempt([
    'email' => $request['body']['email'] ?? '',
    'password' => $request['body']['password'] ?? '',
]);

if (null === $user) {
    response_json(['message' => 'Invalid credentials'], 401);
}

$token_payload = auth_api_login($user, [
    'scope' => ['orders:read'],
]);

response_json($token_payload, 200);

// protected endpoint
middleware(new ApiAuthMiddleware());

if (! auth_api_exists($request)) {
    response_json(['message' => 'Unauthorized'], 401);
}

response_json([
    'user' => auth_api_get($request),
], 200);

// logout endpoint
auth_api_logout(null, $request);
response_json(['message' => 'Logged out'], 200);</code></pre>
</section>

<section class="docs-section">
    <h2>API</h2>
    <details class="api-details">
        <summary class="api-summary">
            <span>Auth Helper API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function auth_attempt(array $credentials): ?array
// Uses auth.web.attempt_resolver, then auth.api.attempt_resolver.

// Web helpers (auth.web.* config)
function auth_web_exists(): bool
function auth_web_get(): ?array
function auth_web_login(array $user): bool
function auth_web_logout(): bool

// API helpers (auth.api.* config)
function auth_api_token(?array $request = null): ?string
function auth_api_exists(?array $request = null): bool
function auth_api_get(?array $request = null): ?array
function auth_api_login(array $user, array $claims = [], ?int $ttl_seconds = null): array
function auth_api_logout(?string $token = null, ?array $request = null): bool

// Low-level token helpers
function auth_token_issue(string|int $subject, array $claims = [], ?int $ttl_seconds = null): string
function auth_token_verify(string $token): bool
function auth_token_payload(string $token, bool $verify = true): array
function auth_token_revoke(string $token): bool
function auth_token_revoked(string $token): bool</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Notes</h2>
    <ul class="api-method-list">
        <li>Load helpers by context: <code>Helper::load_many('auth_web')</code> for web, <code>Helper::load_many('auth_api')</code> for api, or <code>auth</code> for both.</li>
        <li>Use <code>WebAuthMiddleware</code> for web routes and <code>ApiAuthMiddleware</code> for api routes.</li>
        <li><code>auth_api_login()</code> requires one of <code>id</code>, <code>sub</code>, <code>subject</code>, or <code>uuid</code> in user payload.</li>
    </ul>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
