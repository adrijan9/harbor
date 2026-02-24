<?php

declare(strict_types=1);

http_response_code(404);

$page_title = 'Harbor Docs - Page Not Found';
$page_description = 'The requested documentation page could not be found.';
$page_id = 'home';

require __DIR__.'/shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">404</span>
    <h1>Documentation page not found</h1>
    <p>The route did not match any docs entry. Use the sidebar or return to the overview page.</p>
    <div class="button-row">
        <a class="button button-primary" href="/">Back to Overview</a>
    </div>
</section>

<section class="docs-section">
    <div class="notice-box">
        <strong>Tip:</strong> regenerate routes after editing <code>documentation/.router</code>:
        <code>./bin/harbor documentation/.router</code>
    </div>
</section>

<?php require __DIR__.'/shared/footer.php'; ?>
