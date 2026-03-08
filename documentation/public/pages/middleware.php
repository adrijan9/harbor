<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Middleware Helpers';
$page_description = 'Run middleware callbacks and reusable first-class middleware classes.';
$page_id = 'middleware';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">namespace: middleware</span>
    <h1>Middleware Helpers</h1>
    <p>Run middleware callbacks and reusable middleware classes without manual pipeline wiring.</p>
</section>

<section class="docs-section">
    <h2>Basic Usage</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Middleware\middleware;
use function Harbor\Response\abort;

middleware(
    function (array $request, callable $next): mixed {
        if (empty($request['headers']['x-auth-token'])) {
            abort(401);
        }

        return $next($request);
    },
    function (array $request, callable $next): mixed {
        $request['middleware'][] = 'trace';

        return $next($request);
    }
);
</code></pre>
    <h3>Extract Middleware As Class</h3>
    <pre><code class="language-php">final class EnsureApiKey
{
    public function __invoke(): callable
    {
        return function (array $request, callable $next): mixed {
            if (empty($request['headers']['x-api-key'])) {
                abort(401);
            }

            return $next($request);
        };
    }
}

middleware(new EnsureApiKey());
</code></pre>
    <h3>What it does</h3>
    <p><code>middleware(...$actions)</code> always starts with the current <code>request()</code> snapshot and runs actions in pipeline order.</p>
</section>

<section class="docs-section">
    <h2>First-Class Middleware</h2>
    <h3>Built-In Classes</h3>
    <pre><code class="language-php">use Harbor\Middleware\AuthMiddleware;
use Harbor\Middleware\CorsMiddleware;
use Harbor\Middleware\CsrfMiddleware;
use Harbor\Middleware\ThrottleMiddleware;
use function Harbor\Middleware\middleware;

middleware(
    new CorsMiddleware(allowed_origins: ['https://app.example.com']),
    new ThrottleMiddleware(max_attempts: 60, decay_seconds: 60),
    new AuthMiddleware(),
    new CsrfMiddleware()
);</code></pre>
    <h3>Class Purpose</h3>
    <ul class="api-method-list">
        <li><code>AuthMiddleware</code>: validates auth headers or custom auth resolver.</li>
        <li><code>CsrfMiddleware</code>: validates unsafe request methods against CSRF token sources.</li>
        <li><code>ThrottleMiddleware</code>: applies per-key request rate limiting with retry-after support.</li>
        <li><code>CorsMiddleware</code>: handles origin checks and CORS response headers (including preflight).</li>
    </ul>
</section>

<section class="docs-section">
    <h2>API</h2>
    <details class="api-details">
        <summary class="api-summary">
            <span>Middleware API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function middleware(callable ...$actions): void

final class AuthMiddleware
final class CsrfMiddleware
final class ThrottleMiddleware
final class CorsMiddleware</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Notes</h2>
    <ul class="api-method-list">
        <li>Load with <code>HelperLoader::load('middleware')</code>.</li>
        <li>Each middleware callback receives request payload and <code>$next</code>.</li>
        <li>You can pass closures or invokable class instances.</li>
        <li>Invokable class factories are supported: <code>__invoke(): callable</code>.</li>
        <li>Middleware is for request checks/guards; abort or continue with <code>$next($request)</code>.</li>
    </ul>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
