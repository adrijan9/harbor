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
    <details class="api-details">
        <summary class="api-summary">
            <span>Logging Setup API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>log_init()</code> Initialize log file and channel.</li>
                <li><code>log_reset()</code> Reset logger state.</li>
                <li><code>log_is_initialized()</code> Check logger initialization.</li>
                <li><code>log_file_path()</code> Read active log file path.</li>
                <li><code>log_set_channel()</code> Set default channel name.</li>
            </ul>
        </div>
    </details>
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
    <details class="api-details">
        <summary class="api-summary">
            <span>Log Write API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>log_write()</code> Write log entry with level and context.</li>
                <li><code>log_debug()</code> Write debug-level entry.</li>
                <li><code>log_info()</code> Write info-level entry.</li>
                <li><code>log_notice()</code> Write notice-level entry.</li>
                <li><code>log_warning()</code> Write warning-level entry.</li>
                <li><code>log_error()</code> Write error-level entry.</li>
                <li><code>log_critical()</code> Write critical-level entry.</li>
                <li><code>log_alert()</code> Write alert-level entry.</li>
                <li><code>log_emergency()</code> Write emergency-level entry.</li>
            </ul>
        </div>
    </details>
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
    <details class="api-details">
        <summary class="api-summary">
            <span>Reusable Entry API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>log_create_content()</code> Build formatted log content string.</li>
                <li><code>log_write_content()</code> Write prebuilt log content.</li>
            </ul>
        </div>
    </details>
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
    <details class="api-details">
        <summary class="api-summary">
            <span>Exception API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>log_exception()</code> Log throwable with normalized payload.</li>
                <li><code>PhpFramework\Log\LogLevel</code> Enum of supported levels.</li>
                <li><code>log_levels()</code> Return all level string values.</li>
            </ul>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
