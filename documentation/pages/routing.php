<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Routing';
$page_description = 'Define routes with static and dynamic segments using .router files.';
$page_id = 'routing';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Routing</span>
    <h1>Route Definitions and Matching</h1>
    <p>
        Routes are loaded from <code>routes.php</code> and matched by path segment count and values.
        Use <code>$</code> as a dynamic segment placeholder.
    </p>
</section>

<section class="docs-section">
    <h2>Author Routes in <code>.router</code></h2>
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
    <pre><code class="language-bash">./bin/router documentation/.router</code></pre>
</section>

<section class="docs-section">
    <h2>Dynamic Segments and Query Parameters</h2>
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
