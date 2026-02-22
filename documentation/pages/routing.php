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
    <h2>Write <code>.router</code> Routes</h2>
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
    <p>Compile with:</p>
    <pre><code class="language-bash">./bin/harbor documentation/.router</code></pre>
    <p>Or if you in the project directory:</p>
    <pre><code class="language-bash">./bin/harbor .</code></pre>
</section>

<section class="docs-section">
    <h2>Segments and Query</h2>
    <p>
        The router stores matched dynamic values in <code>$GLOBALS['route']['segments']</code> and parsed query values in
        <code>$GLOBALS['route']['query']</code>.
    </p>
    <pre><code class="language-php">use PhpFramework\HelperLoader;
use function PhpFramework\Router\route_segment;
use function PhpFramework\Router\route_query;

HelperLoader::load('route');

$guide_slug = route_segment(0, 'overview');
$tab = route_query('tab', 'general');</code></pre>
</section>

<section class="docs-section">
    <h2>404 Fallback</h2>
    <p>
        The route compiler appends a final <code>/404</code> route automatically. If nothing matches, the router returns
        that fallback entry.
    </p>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
