<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Creating New Site';
$page_description = 'Scaffold and run a new Harbor site quickly.';
$page_id = 'creating_new_site';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Getting Started</span>
    <h1>Creating New Site</h1>
    <p>Use this page if you want to go directly from install to a runnable Harbor site.</p>
</section>

<section class="docs-section">
    <h2>Before You Start</h2>
    <p>These steps assume you already finished dependency setup from <a href="/installation"><code>Installation</code></a>.</p>
</section>

<section class="docs-section">
    <h2>Scaffold a Site</h2>
    <h3>Example</h3>
    <pre><code class="language-bash">./vendor/bin/harbor-init my-site</code></pre>
    <h3>What it does</h3>
    <p>Creates a new Harbor site scaffold in <code>./my-site</code>.</p>
</section>

<section class="docs-section">
    <h2>Compile Routes</h2>
    <h3>Example</h3>
    <pre><code class="language-bash">./vendor/bin/harbor my-site</code></pre>
    <h3>What it does</h3>
    <p>Compiles <code>my-site/.router</code> into <code>my-site/public/routes.php</code>.</p>
</section>

<section class="docs-section">
    <h2>Run the Site</h2>
    <h3>Example</h3>
    <pre><code class="language-bash">cd my-site
./serve.sh</code></pre>
    <h3>What it does</h3>
    <p>Starts the local site server. Open the printed URL in your browser.</p>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
