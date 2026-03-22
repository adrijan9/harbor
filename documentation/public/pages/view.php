<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - View Helpers';
$page_description = 'Native PHP view rendering with layouts, partials, and shared data.';
$page_id = 'view';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">namespace: view</span>
    <h1>View Helpers</h1>
    <p>Render native PHP views with optional layouts, reusable partials, and request-local shared data.</p>
</section>

<section class="docs-section">
    <h2>Render Views</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\Helper;
use function Harbor\View\view;

Helper::load_many('view');

view('pages/home', [
    'title' => 'Dashboard',
    'message' => 'Welcome back.',
]);</code></pre>
    <h3>What it does</h3>
    <p>Renders one template from <code>views/</code> using native PHP variables.</p>
</section>

<section class="docs-section">
    <h2>Use Layouts And Partials</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\View\view;

view('pages/home', [
    'title' => 'Dashboard',
    'message' => 'Main content',
], layout: 'layouts/app', layout_data: [
    'is_production' => true,
    'right_partial' => 'partials/right_box',
    'right_data' => ['label' => 'Insights'],
]);</code></pre>
    <h3>What it does</h3>
    <p>Renders the page first, then injects it into layout <code>$content</code>. Additional regions (header/sidebar/etc.) are controlled by normal PHP conditionals and <code>layout_data</code>.</p>
    <p><strong>Important:</strong> <code>$content</code> is reserved by Harbor when using a layout.</p>
</section>

<section class="docs-section">
    <h2>Dynamic Regions In One Layout</h2>
    <h3>Example layout</h3>
    <pre><code class="language-php">&lt;?php

use function Harbor\View\view_partial;
?&gt;
&lt;header&gt;
    &lt;?php if (($is_production ?? false) === true): ?&gt;
        &lt;?php view_partial('partials/header_prod'); ?&gt;
    &lt;?php else: ?&gt;
        &lt;?php view_partial('partials/header_dev'); ?&gt;
    &lt;?php endif; ?&gt;
&lt;/header&gt;

&lt;div class="layout-grid"&gt;
    &lt;aside class="left"&gt;
        &lt;?php if (! empty($left_partial ?? '')): ?&gt;
            &lt;?php view_partial($left_partial, $left_data ?? []); ?&gt;
        &lt;?php endif; ?&gt;
    &lt;/aside&gt;

    &lt;main&gt;&lt;?= $content ?&gt;&lt;/main&gt;

    &lt;aside class="right"&gt;
        &lt;?php if (! empty($right_partial ?? '')): ?&gt;
            &lt;?php view_partial($right_partial, $right_data ?? []); ?&gt;
        &lt;?php endif; ?&gt;
    &lt;/aside&gt;
&lt;/div&gt;</code></pre>
    <h3>What it does</h3>
    <p>Harbor keeps layout control in plain PHP. Pass region selectors via <code>layout_data</code> and branch in layout with normal <code>if</code> blocks.</p>
</section>

<section class="docs-section">
    <h2>Share Data</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\View\view;
use function Harbor\View\view_share;

view_share('app_name', 'Harbor Site');
view_share('environment', 'production');

view('pages/home', [
    'title' => 'Home',
    'message' => 'Shared values are available in view and layout.',
], layout: 'layouts/app');</code></pre>
    <h3>What it does</h3>
    <p>Stores shared values for the current request lifecycle and merges them into view/layout/partial render data.</p>
</section>

<section class="docs-section">
    <h2>Set Runtime View Path</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\View\view_render;
use function Harbor\View\view_reset_path;
use function Harbor\View\view_set_path;

view_set_path(__DIR__.'/../views');

$html = view_render('pages/home', [
    'title' => 'Home',
    'message' => 'Rendered from custom path',
]);

view_reset_path();</code></pre>
    <h3>What it does</h3>
    <p>Overrides the base view directory for runtime scenarios such as tests or custom bootstraps.</p>
</section>

<section class="docs-section">
    <h2>Core Simplicity Policy</h2>
    <p>Harbor core intentionally does <strong>not</strong> include template macros such as <code>@section</code>/<code>@yield</code> or a built-in template compiler.</p>
    <p>If needed later, a <em>separate optional package</em> can compile custom template files into PHP, but core rendering remains native PHP for speed and simplicity.</p>
</section>

<section class="docs-section">
    <h2>API</h2>
    <details class="api-details">
        <summary class="api-summary">
            <span>View Helper API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function view(string $view, array $data = [], ?string $layout = null, array $layout_data = []): void
// Renders one view and outputs HTML directly.
view('pages/home', ['title' => 'Home']);

function view_render(string $view, array $data = [], ?string $layout = null, array $layout_data = []): string
// Renders one view and returns HTML string.
$html = view_render('pages/home', ['title' => 'Home']);

function view_partial(string $partial, array $data = []): void
// Renders one partial and outputs HTML directly.
view_partial('partials/header');

function view_partial_render(string $partial, array $data = []): string
// Renders one partial and returns HTML string.
$html = view_partial_render('partials/header');

function view_exists(string $view): bool
// Checks if template exists under resolved views path.
$exists = view_exists('pages/home');

function view_share(string $key, mixed $value): void
// Shares one value for later renders in current request.
view_share('app_name', 'Harbor');

function view_share_many(array $data): void
// Shares many values for later renders in current request.
view_share_many(['app_name' => 'Harbor', 'environment' => 'production']);

function view_shared(?string $key = null, mixed $default = null): mixed
// Gets one shared value or all shared values when key is null.
$app_name = view_shared('app_name', 'Default');
$all_shared = view_shared();

function view_clear_shared(): void
// Clears all request-local shared values.
view_clear_shared();

function view_set_path(string $path): void
// Sets runtime base views path.
view_set_path(__DIR__.'/../views');

function view_reset_path(): void
// Clears runtime path override.
view_reset_path();

function view_path(): string
// Returns current resolved base views path.
$path = view_path();

function view_e(mixed $value): string
// Escapes output for HTML contexts.
echo view_e('&lt;script&gt;');</code></pre>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
