<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Logging';
$page_description = 'Structured logging helpers with enum levels, context, and reusable content generation.';
$page_id = 'logging';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Helpers</span>
    <h1>Logging and Structured Context</h1>
    <p>
        Logging helpers support enum levels, channel tagging, interpolation, and exception payload capture.
        You can generate content once and write it to file or other outputs.
    </p>
</section>

<section class="docs-section">
    <h2>Load and Initialize</h2>
    <pre><code class="language-php">use PhpFramework\HelperLoader;
use function PhpFramework\Log\log_init;

HelperLoader::load('log');
log_init(__DIR__.'/storage/app.log', 'app');</code></pre>
</section>

<section class="docs-section">
    <h2>Standard Logging</h2>
    <pre><code class="language-php">use PhpFramework\Log\LogLevel;
use function PhpFramework\Log\log_info;
use function PhpFramework\Log\log_write;

log_info('User {user} signed in', ['user' => 'ada']);
log_write(LogLevel::WARNING, 'Disk usage high', ['usage' => 88], 'infra');</code></pre>
</section>

<section class="docs-section">
    <h2>Reusable Log Content</h2>
    <p>Create once, reuse anywhere (file, stdout, stderr, queues).</p>
    <pre><code class="language-php">use PhpFramework\Log\LogLevel;
use function PhpFramework\Log\log_create_content;
use function PhpFramework\Log\log_write_content;

$content = log_create_content(LogLevel::NOTICE, 'Health check for {service}', ['service' => 'api'], 'cli');

fwrite(STDOUT, $content.PHP_EOL); // custom output
log_write_content($content);      // file output</code></pre>
</section>

<section class="docs-section">
    <h2>Exception Logging</h2>
    <pre><code class="language-php">use function PhpFramework\Log\log_exception;

try {
    risky_operation();
} catch (\Throwable $exception) {
    log_exception($exception, ['request_id' => 'req-42']);
}</code></pre>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
