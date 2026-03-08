<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Middleware Helpers';
$page_description = 'Run middleware callbacks with request payload defaults using the pipeline engine.';
$page_id = 'middleware';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">namespace: middleware</span>
    <h1>Middleware Helpers</h1>
    <p>Run middleware callbacks without manually building pipeline state.</p>
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
    <h2>API</h2>
    <details class="api-details">
        <summary class="api-summary">
            <span>Middleware API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function middleware(callable ...$actions): void</code></pre>
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
