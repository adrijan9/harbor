<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Environment';
$page_description = 'Set and check local, development, stage, and production runtime environment values.';
$page_id = 'environment';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">namespace: environment</span>
    <h1>Environment</h1>
    <p>Define your runtime environment once and branch behavior with explicit checks.</p>
</section>

<section class="docs-section">
    <h2>Set Environment In Global Config</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\Environment;

return [
    'app_name' => 'Harbor Site',
    'environment' => Environment::LOCAL,
];</code></pre>
    <h3>What it does</h3>
    <p>Stores the environment value under <code>$_ENV['environment']</code> when your global config is loaded.</p>
</section>

<section class="docs-section">
    <h2>Read Current Environment</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\Environment;
use function Harbor\Config\config_get;

$current_environment = config_get('environment', Environment::LOCAL);

$is_production = Environment::PRODUCTION === $current_environment;
$is_stage = Environment::STAGE === $current_environment;
$is_development = Environment::DEVELOPMENT === $current_environment;
$is_local = Environment::LOCAL === $current_environment;</code></pre>
    <h3>What it does</h3>
    <p>Reads the current environment from config and compares against the canonical <code>Environment</code> enum cases.</p>
</section>

<section class="docs-section">
    <h2>Built-in Helpers</h2>
    <p>Harbor does <strong>not</strong> currently provide public helpers named <code>is_production()</code>, <code>is_development()</code>, <code>is_stage()</code>, or <code>is_environment()</code>.</p>
    <p>Use <code>config_get('environment')</code> with <code>Harbor\Environment</code> comparisons, or define project-level helper wrappers.</p>
</section>

<section class="docs-section">
    <h2>Project-Level Helper API</h2>
    <details class="api-details" open>
        <summary class="api-summary">
            <span>Environment Helper Functions</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function is_environment(Environment|string $target): bool
function is_production(): bool
function is_development(): bool
function is_stage(): bool
function is_local(): bool</code></pre>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
