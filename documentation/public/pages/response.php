<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Response Helpers';
$page_description = 'Convenience response helpers for status codes, headers, JSON, text, and files.';
$page_id = 'response';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Helpers</span>
    <h1>Response Helpers</h1>
    <p>Convenience helpers for HTTP status codes, headers, JSON/text output, and file responses.</p>
</section>

<section class="docs-section">
    <h2>Load Helper</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\HelperLoader;

HelperLoader::load('response');</code></pre>
    <h3>What it does</h3>
    <p>Loads response helper functions into the <code>Harbor\Response</code> namespace.</p>
</section>

<section class="docs-section">
    <h2>Send Responses</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Response\response_download;
use function Harbor\Response\response_file;
use function Harbor\Response\response_json;
use function Harbor\Response\response_text;

response_json(['status' => 'ok'], 200);
response_text('Created', 201);
response_file(__DIR__.'/../../storage/report.pdf', 'report.pdf');
response_download(__DIR__.'/../../storage/export.csv');</code></pre>
    <h3>What it does</h3>
    <p>Provides simple helpers for common response tasks without building a full response object layer.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Response Helper API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function response_status(int $status): void
// Sets HTTP status code when headers are not sent.
response_status(204);

function response_header(string $name, string $value, bool $replace = true): void
// Adds one response header when headers are not sent.
response_header('X-Request-Id', 'abc-123');

function response_json(array $payload, int $status = 200, array $headers = []): void
// Writes JSON response body and status code.
// Adds JSON content type by default.
response_json(['ok' => true], 200);

function response_text(string $content, int $status = 200, array $headers = []): void
// Writes plain text response body and status code.
// Adds text/plain content type by default.
response_text('Done', 200);

function response_file(string $file_path, ?string $download_name = null, array $headers = []): void
// Streams a file as response body.
// Uses detected mime type and optional download filename.
response_file(__DIR__.'/report.csv', 'report.csv');

function response_download(string $file_path, ?string $download_name = null, array $headers = []): void
// Forces file download response (attachment disposition).
// Defaults filename to basename($file_path) when not provided.
response_download(__DIR__.'/report.csv');</code></pre>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
