<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Model Pagination';
$page_description = 'Standalone pagination module with class mode and helper mode.';
$page_id = 'model_pagination';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">module: pagination</span>
    <h1>Model Pagination</h1>
    <p>Use standalone pagination with QueryBuilder. No ORM required and no model lock-in.</p>
</section>

<section class="docs-section">
    <h2>Why Separate Module</h2>
    <p>Pagination is implemented as <code>Harbor\Pagination</code>, separate from Database and separate from model classes.</p>
    <p>You define one base query. The module resolves total count and paginated rows internally.</p>
</section>

<section class="docs-section">
    <h2>Load Helpers</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\HelperLoader;

HelperLoader::load('db', 'pagination');</code></pre>
</section>

<section class="docs-section">
    <h2>Class Mode</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\Database\QueryBuilder\QueryBuilder;
use Harbor\Pagination\Pagination;
use Harbor\Pagination\PaginationOptionsBag;

final class UsersPagination extends Pagination
{
    private string $status = 'active';

    public function __construct()
    {
        // You can set connection here with: $this->set_connection($connection);
        // You can set options here with: $this->set_options(PaginationOptionsBag::make()->set_max_per_page(100));
    }

    protected function base_query(): QueryBuilder
    {
        return QueryBuilder::select()
            ->from('users')
            ->columns('id', 'email', 'status')
            ->where('status', '=', $this->status)
            ->order_by('id', 'desc');
    }
}

$result = (new UsersPagination())
    ->set_connection($connection)
    ->set_options(
        PaginationOptionsBag::make()
            ->set_base_path('/model/pagination')
            ->set_query(['status' => 'active'])
            ->set_max_per_page(100)
    )
    ->paginate(page: 2, per_page: 20);</code></pre>
    <h3>What it does</h3>
    <p>Lets you keep reusable filtering/state in a small paginator class, control connection with <code>set_connection()</code>, and configure links/per-page caps with <code>set_options()</code>.</p>
</section>

<section class="docs-section">
    <h2>Helper Mode (No Class)</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\Database\QueryBuilder\QueryBuilder;
use Harbor\Pagination\PaginationOptionsBag;
use function Harbor\Pagination\pagination_paginate;

$base_query = QueryBuilder::select()
    ->from('users')
    ->columns('id', 'email')
    ->where('status', '=', 'active')
    ->order_by('id', 'desc');

$result = pagination_paginate(
    base_query: $base_query,
    page: 2,
    per_page: 20,
    connection: $connection,
    options: PaginationOptionsBag::make()
        ->set_base_path('/model/pagination')
        ->set_query(['status' => 'active'])
        ->set_max_per_page(100)
);</code></pre>
    <h3>What it does</h3>
    <p>Gives the same internal pagination flow without creating a class, useful for one-off routes or scripts.</p>
</section>

<section class="docs-section">
    <h2>PaginationOptionsBag</h2>
    <p>Class mode and helper mode both use a typed options bag so each option is explicit and discoverable.</p>
    <ul>
        <li><code>set_base_path('/users')</code>: base URL used for generated links.</li>
        <li><code>set_query(['status' => 'active'])</code>: query params preserved in each generated link.</li>
        <li><code>set_max_per_page(100)</code>: caps the runtime <code>per_page</code> value.</li>
    </ul>
</section>

<section class="docs-section">
    <h2>Response Shape</h2>
    <pre><code class="language-php">[
    'data' => [...],
    'meta' => [
        'total' => 120,
        'per_page' => 20,
        'current_page' => 2,
        'last_page' => 6,
        'from' => 21,
        'to' => 40,
        'has_more' => true,
    ],
    'links' => [
        'first' => '/model/pagination?status=active&page=1',
        'prev' => '/model/pagination?status=active&page=1',
        'next' => '/model/pagination?status=active&page=3',
        'last' => '/model/pagination?status=active&page=6',
    ],
]</code></pre>
</section>

<section class="docs-section">
    <h2>API</h2>
    <details class="api-details">
        <summary class="api-summary">
            <span>Pagination API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">abstract class Pagination
{
    public function set_connection(PDO|mysqli $connection): static
    public function set_options(PaginationOptionsBag $options): static
    public function paginate(
        int $page = 1,
        int $per_page = 15
    ): array

    abstract protected function base_query(): QueryBuilder
}

function pagination_paginate(
    QueryBuilder $base_query,
    int $page = 1,
    int $per_page = 15,
    PDO|mysqli|null $connection = null,
    ?PaginationOptionsBag $options = null
): array</code></pre>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
