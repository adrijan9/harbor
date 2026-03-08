<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Response Helpers';
$page_description = 'Convenience response helpers for status codes, headers, JSON, text, and files.';
$page_id = 'response';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">namespace: response</span>
    <h1>Response Helpers</h1>
    <p>Convenience helpers for HTTP status codes, headers, JSON/text output, and file responses.</p>
</section>

<section class="docs-section">
    <h2>Send Responses</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\Response\ResponseStatus;
use function Harbor\Response\abort;
use function Harbor\Response\response_download;
use function Harbor\Response\response_file;
use function Harbor\Response\response_json;
use function Harbor\Response\response_status;
use function Harbor\Response\response_text;
use function Harbor\Response\response_validation;
use function Harbor\Validation\validation_rule;
use function Harbor\Validation\validation_validate;

response_json(['status' => 'ok'], 200);
response_text('Created', 201);
response_status(ResponseStatus::NO_CONTENT);
response_file(__DIR__.'/../../storage/report.pdf', 'report.pdf');
response_download(__DIR__.'/../../storage/export.csv');

$validation = validation_validate($payload, [
    validation_rule('email')->required()->email(),
]);

if (! $validation->is_ok()) {
    response_validation($validation);
}

if (! $is_authorized) {
    abort(ResponseStatus::FORBIDDEN);
}</code></pre>
    <h3>What it does</h3>
    <p>Provides simple helpers for common response tasks and immediate request aborts without a full response object layer.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Response Helper API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">enum ResponseStatus: int
{
    case NOT_FOUND = 404;

    public function message(): string;
    public static function message_for(int|ResponseStatus $status, string $default = 'Error'): string;
    public static function map(): array; // [status_code => message]
}

function response_status(int|ResponseStatus $status): void
// Sets HTTP status code when headers are not sent.
response_status(204);

function response_header(string $name, string $value, bool $replace = true): void
// Adds one response header when headers are not sent.
response_header('X-Request-Id', 'abc-123');

function response_json(array $payload, int|ResponseStatus $status = ResponseStatus::OK, array $headers = []): void
// Writes JSON response body and status code.
// Adds JSON content type by default.
response_json(['ok' => true], 200);

function response_text(string $content, int|ResponseStatus $status = ResponseStatus::OK, array $headers = []): void
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
response_download(__DIR__.'/report.csv');

function response_validation(\Harbor\Validation\ValidationResult $result, int|ResponseStatus $status = ResponseStatus::UNPROCESSABLE_CONTENT, array $headers = []): void
// Returns validation errors as JSON for JSON clients.
// Falls back to plain text 422 response when JSON is not preferred.
response_validation($validation_result);

function abort(int|ResponseStatus $status, ?string $content = null): never
// Sets status code and stops execution immediately.
// Uses status-based default content when no custom content is provided.
abort(ResponseStatus::NOT_FOUND);</code></pre>
        </div>
    </details>
    <p><code>ResponseStatus</code> includes the full official HTTP status set (100-511). Use <code>ResponseStatus::map()</code> to get <code>[status_code => message]</code>.</p>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
