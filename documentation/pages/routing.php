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
    <ul>
        <li>Route keys: <code>path</code>, <code>method</code>, <code>name</code>, <code>entry</code>.</li>
        <li>Dynamic path placeholder: <code>$</code> (for example <code>/posts/$</code>).</li>
        <li>Compile commands: <code>./bin/harbor &lt;path-to-.router&gt;</code> or <code>./bin/harbor &lt;directory&gt;</code>.</li>
        <li>Compiler output: writes <code>routes.php</code> and appends <code>/404</code> fallback route.</li>
    </ul>
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
    <ul>
        <li>Segment access: <code>route_segment()</code>, <code>route_segments()</code>, <code>route_segments_count()</code>, <code>route_segment_exists()</code>.</li>
        <li>Typed segment helpers: <code>route_segment_int()</code>, <code>route_segment_float()</code>, <code>route_segment_str()</code>, <code>route_segment_bool()</code>, <code>route_segment_arr()</code>, <code>route_segment_obj()</code>, <code>route_segment_json()</code>.</li>
        <li>Query access: <code>route_query()</code>, <code>route_queries()</code>, <code>route_queries_count()</code>, <code>route_query_exists()</code>.</li>
        <li>Typed query helpers: <code>route_query_int()</code>, <code>route_query_float()</code>, <code>route_query_str()</code>, <code>route_query_bool()</code>, <code>route_query_arr()</code>, <code>route_query_obj()</code>, <code>route_query_json()</code>.</li>
    </ul>
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
    <ul>
        <li><code>Router::__construct(string $router_path)</code>.</li>
        <li><code>Router::get_uri()</code>.</li>
        <li><code>Router::current()</code> (returns matched route or fallback).</li>
        <li><code>Router::render(array $variables = [])</code>.</li>
    </ul>
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
    <ul>
        <li>Fallback route path: <code>/404</code>.</li>
        <li>Fallback route entry: <code>not_found.php</code> by default.</li>
        <li>Runtime behavior: <code>Router::current()</code> returns fallback when no match exists.</li>
    </ul>
</section>

<section class="docs-section">
    <h2>Compile Command</h2>
    <h3>Example</h3>
    <pre><code class="language-bash">./bin/harbor documentation/.router</code></pre>
    <h3>What it does</h3>
    <p>Compiles your route file into executable route arrays.</p>
    <h3>API</h3>
    <ul>
        <li><code>./bin/harbor .</code> compiles <code>./.router</code>.</li>
        <li><code>./bin/harbor &lt;project-dir&gt;</code> compiles <code>&lt;project-dir&gt;/.router</code>.</li>
        <li><code>./bin/harbor &lt;path-to-.router&gt;</code> compiles that specific file.</li>
    </ul>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
