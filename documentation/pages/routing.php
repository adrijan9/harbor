<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Routing';
$page_description = 'Define routes with .router files and compile to routes.php.';
$page_id = 'routing';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Routing</span>
    <h1>Routing</h1>
    <p>Write routes in <code>.router</code>, compile to <code>routes.php</code>, then match by path segments.</p>
</section>

<section class="docs-section">
    <h2>Define Routes</h2>

    <h3>Example</h3>
    <pre><code class="language-ini">#route
  path: /
  method: GET
  name: docs.home
  entry: pages/index.php
#endroute

#route
  path: /guides/$
  method: GET
  name: docs.guide
  entry: pages/routing.php
#endroute</code></pre>
    <pre><code class="language-bash">./bin/harbor documentation/.router
./bin/harbor .</code></pre>

    <h3>What it does</h3>
    <p>Compiles <code>.router</code> entries into <code>routes.php</code> for runtime matching.</p>

    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Route Definition API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>path</code> Route path pattern.</li>
                <li><code>method</code> HTTP method value in route file.</li>
                <li><code>name</code> Optional route identifier.</li>
                <li><code>entry</code> PHP file to execute for matched route.</li>
                <li><code>$</code> Dynamic segment placeholder in paths.</li>
                <li><code>/404</code> Fallback route appended by compiler.</li>
            </ul>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Read Route Data</h2>

    <h3>Example</h3>
    <pre><code class="language-php">use PhpFramework\HelperLoader;
use function PhpFramework\Router\route_segment;
use function PhpFramework\Router\route_query;

HelperLoader::load('route');

$guide_slug = route_segment(0, 'overview');
$tab = route_query('tab', 'general');</code></pre>

    <h3>What it does</h3>
    <p>Reads matched path segments and query values from the current route context.</p>

    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Route Helper API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <div class="api-group">
                <p class="api-group-title">Segment Helpers</p>
                <ul class="api-method-list">
                    <li><code>route_segment()</code> Read a segment by index.</li>
                    <li><code>route_segment_int()</code> Segment as integer.</li>
                    <li><code>route_segment_float()</code> Segment as float.</li>
                    <li><code>route_segment_str()</code> Segment as string.</li>
                    <li><code>route_segment_bool()</code> Segment as boolean.</li>
                    <li><code>route_segment_arr()</code> Segment as array.</li>
                    <li><code>route_segment_obj()</code> Segment as object.</li>
                    <li><code>route_segment_json()</code> Segment decoded as JSON.</li>
                    <li><code>route_segments()</code> All matched segments.</li>
                    <li><code>route_segments_count()</code> Segment count.</li>
                    <li><code>route_segment_exists()</code> Segment existence check.</li>
                </ul>
            </div>
            <div class="api-group">
                <p class="api-group-title">Query Helpers</p>
                <ul class="api-method-list">
                    <li><code>route_query()</code> Read query value by key.</li>
                    <li><code>route_query_int()</code> Query value as integer.</li>
                    <li><code>route_query_float()</code> Query value as float.</li>
                    <li><code>route_query_str()</code> Query value as string.</li>
                    <li><code>route_query_bool()</code> Query value as boolean.</li>
                    <li><code>route_query_arr()</code> Query value as array.</li>
                    <li><code>route_query_obj()</code> Query value as object.</li>
                    <li><code>route_query_json()</code> Query value decoded as JSON.</li>
                    <li><code>route_queries()</code> All query values.</li>
                    <li><code>route_queries_count()</code> Query count.</li>
                    <li><code>route_query_exists()</code> Query key existence check.</li>
                </ul>
            </div>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Router Runtime</h2>

    <h3>Example</h3>
    <pre><code class="language-php">use PhpFramework\Router\Router;

require __DIR__.'/../vendor/autoload.php';

new Router(__DIR__.'/routes.php')->render();</code></pre>

    <h3>What it does</h3>
    <p>Loads routes, resolves the current route, then includes the matched entry file.</p>

    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Router Class API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>Router::__construct(string $router_path)</code> Load routes from file.</li>
                <li><code>Router::get_uri()</code> Resolve request path.</li>
                <li><code>Router::current()</code> Return matched route or fallback.</li>
                <li><code>Router::render(array $variables = [])</code> Render current route entry.</li>
            </ul>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Fallback</h2>
    <h3>Example</h3>
    <pre><code class="language-ini">#route
  method: GET
  path: /404
  entry: not_found.php
#endroute</code></pre>
    <h3>What it does</h3>
    <p>If no route matches, runtime falls back to the final <code>/404</code> route entry.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Fallback API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>/404</code> Default fallback path.</li>
                <li><code>not_found.php</code> Default fallback entry.</li>
                <li><code>Router::current()</code> Returns fallback when no route matches.</li>
            </ul>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Compile Command</h2>
    <h3>Example</h3>
    <pre><code class="language-bash">./bin/harbor documentation/.router</code></pre>
    <h3>What it does</h3>
    <p>Compiles your route file into executable route arrays.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Compile Command API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>./bin/harbor .</code> Compile <code>./.router</code>.</li>
                <li><code>./bin/harbor &lt;project-dir&gt;</code> Compile <code>&lt;project-dir&gt;/.router</code>.</li>
                <li><code>./bin/harbor &lt;path-to-.router&gt;</code> Compile specific route file.</li>
            </ul>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
