<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Lang and Translation Helpers';
$page_description = 'Language and translation helpers for locale-aware messages.';
$page_id = 'lang';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Helpers</span>
    <h1>Lang and Translation Helpers</h1>
    <p>Set locale, load translation files, and translate keys with replacements.</p>
</section>

<section class="docs-section">
    <h2>Load Helpers</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\HelperLoader;

HelperLoader::load('translation');
// "translation" includes locale and translation functions.</code></pre>
    <h3>What it does</h3>
    <p>Loads language and translation functions into the <code>Harbor\Lang</code> namespace.</p>
</section>

<section class="docs-section">
    <h2>Locale Helpers</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Lang\lang_get;
use function Harbor\Lang\lang_is;
use function Harbor\Lang\lang_set;

$current = lang_get(); // reads config('lang', 'en')
lang_set('es');
$is_spanish = lang_is('es');</code></pre>
    <h3>What it does</h3>
    <p>Reads and updates the active application language from runtime config.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Locale API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function lang_get(string $default = 'en'): string
// Returns current locale from config('lang') or default.
$locale = lang_get();

function lang_set(string $locale): void
// Writes current locale into $_ENV['lang'].
lang_set('en');

function lang_is(string $locale): bool
// Checks whether current locale matches the provided locale.
$is_english = lang_is('en');</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Init Translation Files</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Lang\translation_init;

translation_init([
    'en' => [__DIR__.'/lang/en.php', __DIR__.'/lang/en_auth.php'],
    'es' => [__DIR__.'/lang/es.php'],
]);</code></pre>
    <h3>What it does</h3>
    <p>Loads file arrays per locale and stores them globally. Later files override earlier keys.</p>
    <h3>File Format</h3>
    <pre><code class="language-php">// lang/en.php
&lt;?php

return [
    'home' => [
        'welcome' => 'Welcome :name',
    ],
];</code></pre>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Translation Init API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function translation_init(array $translations): void
// Loads translation files grouped by locale and writes global map.
translation_init([
    'en' => [__DIR__.'/lang/en.php'],
    'es' => [__DIR__.'/lang/es.php'],
]);

function translations_all(): array
// Returns all loaded translations by locale.
$translations = translations_all();</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Translate Keys</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Lang\t;

echo t('home.welcome', ['name' => 'Ada']); // Uses current locale
echo t('home.welcome', ['name' => 'Ada'], 'es'); // Force locale</code></pre>
    <h3>What it does</h3>
    <p>Translates dot-notation keys with Laravel-style signature and placeholder replacement.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Translate API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function t(string $key, array $replace = [], ?string $locale = null): string
// Translate helper (Laravel-style args: key, replacements, locale).
// Returns key when translation is missing.
$title = t('home.welcome', ['name' => 'Ada'], 'en');

function translation_get(string $key, array $replace = [], ?string $locale = null): string
// Same behavior as t().
$title = translation_get('home.welcome', ['name' => 'Ada']);

function translation_exists(string $key, ?string $locale = null): bool
// Checks if translation key exists.
$has_key = translation_exists('home.welcome', 'en');</code></pre>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
