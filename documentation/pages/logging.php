<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Logging';
$page_description = 'Logging helpers with levels, context, and reusable entries.';
$page_id = 'logging';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Helpers</span>
    <h1>Logging Helpers</h1>
    <p>Write structured logs with levels, channels, and context.</p>
</section>

<section class="docs-section">
    <h2>Load and Init</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use PhpFramework\HelperLoader;
use function PhpFramework\Log\log_init;

HelperLoader::load('log');
log_init(__DIR__.'/storage/app.log', 'app');</code></pre>
    <h3>What it does</h3>
    <p>Loads log helpers and initializes file output.</p>
    <h3>API</h3>
    <ul>
        <li>Setup: <code>log_init()</code>, <code>log_reset()</code>, <code>log_is_initialized()</code>, <code>log_file_path()</code>, <code>log_set_channel()</code>.</li>
    </ul>
</section>

<section class="docs-section">
    <h2>Write Logs</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use PhpFramework\Log\LogLevel;
use function PhpFramework\Log\log_info;
use function PhpFramework\Log\log_write;

log_info('User {user} signed in', ['user' => 'ada']);
log_write(LogLevel::WARNING, 'Disk usage high', ['usage' => 88], 'infra');</code></pre>
    <h3>What it does</h3>
    <p>Writes level-based log lines with context data.</p>
    <h3>API</h3>
    <ul>
        <li>Writers: <code>log_write()</code>, <code>log_debug()</code>, <code>log_info()</code>, <code>log_notice()</code>, <code>log_warning()</code>, <code>log_error()</code>, <code>log_critical()</code>, <code>log_alert()</code>, <code>log_emergency()</code>.</li>
    </ul>
</section>

<section class="docs-section">
    <h2>Reusable Entries</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use PhpFramework\Log\LogLevel;
use function PhpFramework\Log\log_create_content;
use function PhpFramework\Log\log_write_content;

$content = log_create_content(LogLevel::NOTICE, 'Health check for {service}', ['service' => 'api'], 'cli');

fwrite(STDOUT, $content.PHP_EOL); // custom output
log_write_content($content);      // file output</code></pre>
    <h3>What it does</h3>
    <p>Builds one formatted log entry and reuses it across outputs.</p>
    <h3>API</h3>
    <ul>
        <li>Entry helpers: <code>log_create_content()</code>, <code>log_write_content()</code>.</li>
    </ul>
</section>

<section class="docs-section">
    <h2>Exception Logging</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function PhpFramework\Log\log_exception;

try {
    risky_operation();
} catch (\Throwable $exception) {
    log_exception($exception, ['request_id' => 'req-42']);
}</code></pre>
    <h3>What it does</h3>
    <p>Logs exception metadata with request context.</p>
    <h3>API</h3>
    <ul>
        <li>Exception helper: <code>log_exception()</code>.</li>
        <li>Levels: <code>PhpFramework\Log\LogLevel</code>, <code>log_levels()</code>.</li>
    </ul>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
