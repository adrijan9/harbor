<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Logging';
$page_description = 'Logging helpers with levels, context, and reusable entries.';
$page_id = 'logging';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">namespace: log</span>
    <h1>Logging Helpers</h1>
    <p>Write structured logs with levels, channels, and context.</p>
</section>

<section class="docs-section">
    <h2>Init Logger</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Log\log_init;

log_init(__DIR__.'/storage/app.log', 'app');</code></pre>
    <h3>What it does</h3>
    <p>Initializes the logger file path and default channel for log output.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Logging Setup API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function log_init(string $file_path, string $channel = 'app'): void
// Initializes logger file path and default channel.
// Creates missing log directory/file automatically.
log_init(__DIR__.'/storage/app.log', 'app');

function log_reset(): void
// Resets logger state.
// Clears initialized file path and default channel.
log_reset();

function log_is_initialized(): bool
// Checks logger initialization state.
// Returns true after successful log_init().
$ready = log_is_initialized();

function log_file_path(): ?string
// Returns active log file path.
// Returns null when logger is not initialized.
$path = log_file_path();

function log_set_channel(string $channel): void
// Updates default channel name.
// Channel must match logger channel format rules.
log_set_channel('infra');</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Write Logs</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\Log\LogLevel;
use function Harbor\Log\log_info;
use function Harbor\Log\log_write;

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
            <pre><code class="language-php">function log_write(LogLevel|string $level, string $message, array $context = [], ?string $channel = null): void
// Writes one formatted log entry to file.
// Supports enum or string level and optional channel override.
log_write(LogLevel::WARNING, 'Disk usage high', ['usage' => 88], 'infra');

function log_debug(string $message, array $context = [], ?string $channel = null): void
// Shortcut for debug level logs.
// Delegates to log_write() with LogLevel::DEBUG.
log_debug('Cache warmed', ['items' => 42]);

function log_info(string $message, array $context = [], ?string $channel = null): void
// Shortcut for info level logs.
// Delegates to log_write() with LogLevel::INFO.
log_info('User {user} signed in', ['user' => 'ada']);

function log_notice(string $message, array $context = [], ?string $channel = null): void
// Shortcut for notice level logs.
// Delegates to log_write() with LogLevel::NOTICE.
log_notice('Billing sync completed');

function log_warning(string $message, array $context = [], ?string $channel = null): void
// Shortcut for warning level logs.
// Delegates to log_write() with LogLevel::WARNING.
log_warning('Queue delay high', ['seconds' => 12]);

function log_error(string $message, array $context = [], ?string $channel = null): void
// Shortcut for error level logs.
// Delegates to log_write() with LogLevel::ERROR.
log_error('Payment request failed', ['order_id' => 1024]);

function log_critical(string $message, array $context = [], ?string $channel = null): void
// Shortcut for critical level logs.
// Delegates to log_write() with LogLevel::CRITICAL.
log_critical('Database unavailable');

function log_alert(string $message, array $context = [], ?string $channel = null): void
// Shortcut for alert level logs.
// Delegates to log_write() with LogLevel::ALERT.
log_alert('Primary node unhealthy');

function log_emergency(string $message, array $context = [], ?string $channel = null): void
// Shortcut for emergency level logs.
// Delegates to log_write() with LogLevel::EMERGENCY.
log_emergency('System outage');</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Reusable Entries</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\Log\LogLevel;
use function Harbor\Log\log_create_content;
use function Harbor\Log\log_write_content;

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
            <pre><code class="language-php">function log_create_content(LogLevel|string $level, string $message, array $context = [], ?string $channel = null): string
// Builds one formatted log line string.
// Interpolates context placeholders before output.
$content = log_create_content(LogLevel::NOTICE, 'Health check for {service}', ['service' => 'api'], 'cli');

function log_write_content(string $log_content): void
// Writes prebuilt log content to active log file.
// Appends newline when content does not end with PHP_EOL.
log_write_content($content);</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Exception Logging</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Log\log_exception;

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
            <pre><code class="language-php">function log_exception(\Throwable $exception, array $context = [], LogLevel|string $level = LogLevel::ERROR, string $message = 'Unhandled exception', ?string $channel = null): void
// Logs throwable data with merged custom context.
// Adds class/message/code/file/line/trace fields automatically.
log_exception($exception, ['request_id' => 'req-42']);

function log_levels(): array
// Returns supported log level values.
// Values are sourced from the LogLevel enum.
$levels = log_levels();

enum LogLevel: string
// Defines available levels: debug, info, notice, warning, error, critical, alert, emergency.
// Use enum cases for strict level selection.
$level = LogLevel::WARNING;</code></pre>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
