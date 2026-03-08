<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Pipeline Helpers';
$page_description = 'Function-based pipeline helpers for before/after action flows.';
$page_id = 'pipeline';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">namespace: pipeline</span>
    <h1>Pipeline Helpers</h1>
    <p>Build pipeline flows with plain functions.</p>
</section>

<section class="docs-section">
    <h2>Basic Usage</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Pipeline\pipeline_new;
use function Harbor\Pipeline\pipeline_send;
use function Harbor\Pipeline\pipeline_through;
use function Harbor\Pipeline\pipeline_clog;
use function Harbor\Pipeline\pipeline_get;

$request_pipeline = pipeline_new();

pipeline_send($request_pipeline, $request);
pipeline_through(
    $request_pipeline,
    function (array $req, callable $next): mixed {
        $req['started_at'] = microtime(true); // before
        $response = $next($req);
        $response['trace'] = 'completed'; // after

        return $response;
    },
    function (array $req, callable $next): mixed {
        $req['path'] = strtolower($req['path']);

        return $next($req);
    }
);

pipeline_clog($request_pipeline);
$result = pipeline_get();
</code></pre>
    <h3>What it does</h3>
    <p>Chains actions in order and lets each action run logic before and after calling <code>$next(...)</code>.</p>
</section>

<section class="docs-section">
    <h2>API</h2>
    <details class="api-details">
        <summary class="api-summary">
            <span>Pipeline API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function pipeline_new(): array
function pipeline_send(array &$pipeline, mixed ...$passable): void
function pipeline_through(array &$pipeline, callable ...$actions): void
function pipeline_clog(array &$pipeline): void
function pipeline_get(): mixed</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Notes</h2>
    <ul class="api-method-list">
        <li>Load with <code>HelperLoader::load('pipeline')</code>.</li>
        <li>Each action receives passable arguments plus <code>callable $next</code> as the last parameter.</li>
        <li>You can pass closures or invokable class instances.</li>
        <li>Invokable class factories are supported: <code>__invoke(): callable</code>.</li>
        <li>If <code>$next()</code> is called without arguments, current passable values continue unchanged.</li>
    </ul>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
