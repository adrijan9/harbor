<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Support Helpers';
$page_description = 'Shared support helpers for value checks and array mutation.';
$page_id = 'support';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Helpers</span>
    <h1>Support Helpers</h1>
    <p>Reusable helpers for value checks and array key mutation.</p>
</section>

<section class="docs-section">
    <h2>Load Helper</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\HelperLoader;

HelperLoader::load('value');
HelperLoader::load('array');</code></pre>
    <h3>What it does</h3>
    <p>Loads support helper functions into the <code>Harbor\Support</code> namespace.</p>
</section>

<section class="docs-section">
    <h2>Blank and Null Checks</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Support\harbor_is_blank;
use function Harbor\Support\harbor_is_null;

$blank_title = harbor_is_blank('');
$blank_array = harbor_is_blank([]);
$zero_string_is_blank = harbor_is_blank('0');
$only_null = harbor_is_null(null);</code></pre>
    <h3>What it does</h3>
    <p>Provides one shared rule for empty checks so all modules use the same behavior.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Value Helper API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function harbor_is_blank(mixed $value): bool
// Returns true for null, empty string, and empty array.
// Keeps string "0" and int 0 as non-blank values.
$is_blank = harbor_is_blank($value);

function harbor_is_null(mixed $value): bool
// Returns true only when the value is null.
// Useful when default fallback should trigger only for null.
$is_null = harbor_is_null($value);</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Array Mutation</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Support\array_forget;

$filters = [
    'owner' => ['id' => '44', 'name' => 'Ada'],
    'active' => true,
];

array_forget($filters, 'owner.id');
// $filters is now: ['owner' => ['name' => 'Ada'], 'active' => true]</code></pre>
    <h3>What it does</h3>
    <p>Removes one key by exact name or dot notation path from an array.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Array Helper API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function array_forget(array &$array, string $key): void
// Removes one key from array by exact key or dot notation path.
// Does nothing when key/path is missing.
array_forget($payload, 'filters.owner.id');</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Route Query Example</h2>
    <h3>Example</h3>
    <pre><code class="language-php">// URL: /products?page=0&tags=%5B%22new%22%5D

use function Harbor\Router\route_query;

// "0" is not considered blank, so key lookup still works.
$page = route_query('page', 1); // "0"
$tags = route_query('tags', '[]');</code></pre>
    <h3>What it does</h3>
    <p>Shows why the shared blank rule keeps <code>'0'</code> valid for dynamic URL/query data.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Related Route API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function route_query(?string $key = null, mixed $default = null): mixed
// Reads one query key or all query params when key is blank.
// Dot notation is supported for nested query arrays.
$value = route_query('filters.status', 'all');</code></pre>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
