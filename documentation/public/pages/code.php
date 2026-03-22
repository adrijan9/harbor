<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Code';
$page_description = 'Coding style workflow and private helper method conventions.';
$page_id = 'code';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Conventions</span>
    <h1>Code</h1>
    <p>Use these conventions in your Harbor site project after Harbor is installed.</p>
</section>

<section class="docs-section">
    <h2>Style</h2>
    <h3>Publish Fixer Preset</h3>
    <pre><code class="language-bash">cd my-site
../bin/harbor-fixer</code></pre>
    <p>From your site root, publish Harbor's <code>.php-cs-fixer.dist.php</code> preset into that same project.</p>
    <p>Existing <code>.php-cs-fixer.dist.php</code> content is replaced without a confirmation prompt.</p>

    <h3>Run Fixer For Project Files</h3>
    <pre><code class="language-bash">./vendor/bin/php-cs-fixer fix --using-cache=no --sequential</code></pre>
    <p>Run this from your site root to apply style fixes across the project files that match the active preset.</p>

    <h3>Editor Integration</h3>
    <p>VS Code and PhpStorm setup guidance can be added later in this section, including watcher configuration such as a downloadable <code>.xml</code> profile.</p>
</section>

<section class="docs-section">
    <h2>Private Methods</h2>
    <p>Private methods are helper functions Harbor uses internally.</p>
    <p>You can find them in each helper module under the <code>/** Private */</code> section.</p>
    <p>Private method names use the <code>module_internal_*</code> pattern (for example, <code>config_internal_*</code>).</p>
    <div class="notice-box">
        <strong>Warning</strong>
        <p>You can call private methods, but they are not considered stable public API.</p>
        <p>They may change, be renamed, or be removed in future updates.</p>
        <p>If you need stable behavior in your project, it is better to copy the logic into your own helper and rename it for your use case.</p>
    </div>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
