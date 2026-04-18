<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Robots.txt Generator';
$page_description = 'Generate a starter robots.txt file locally, then paste it into public/robots.txt in your Harbor site.';
$page_id = 'robots_generator';

$robots_generator_bots = [
    [
        'label' => 'Google Search',
        'agent' => 'Googlebot',
        'description' => 'Google search crawler.',
    ],
    [
        'label' => 'Bing Search',
        'agent' => 'Bingbot',
        'description' => 'Microsoft Bing crawler.',
    ],
    [
        'label' => 'Apple Search',
        'agent' => 'Applebot',
        'description' => 'Apple crawler for Siri and Spotlight.',
    ],
    [
        'label' => 'DuckDuckGo',
        'agent' => 'DuckDuckBot',
        'description' => 'DuckDuckGo search crawler.',
    ],
    [
        'label' => 'OpenAI Training',
        'agent' => 'GPTBot',
        'description' => 'OpenAI crawler for training-related access.',
    ],
    [
        'label' => 'ChatGPT Fetcher',
        'agent' => 'ChatGPT-User',
        'description' => 'User-triggered fetching from ChatGPT sessions.',
    ],
    [
        'label' => 'Anthropic',
        'agent' => 'ClaudeBot',
        'description' => 'Anthropic crawler.',
    ],
    [
        'label' => 'Perplexity',
        'agent' => 'PerplexityBot',
        'description' => 'Perplexity crawler.',
    ],
];

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Tooling</span>
    <h1>Robots.txt Generator</h1>
    <p>Generate a starter <code>robots.txt</code> locally, then paste it into <code>public/robots.txt</code> in your Harbor project.</p>
    <div class="button-row">
        <a class="button button-primary" href="/routing">Open Routing Docs</a>
        <a class="button button-ghost" href="/creating-new-site">Open Creating New Site</a>
    </div>
</section>

<section class="docs-section">
    <h2>How To Use It</h2>
    <p>Pick a default crawl policy, add any path rules you need, optionally override specific crawlers, then copy the generated text into <code>public/robots.txt</code>.</p>
    <div class="notice-box">
        <p><strong>Important:</strong> <code>robots.txt</code> is a crawl hint, not access control. Do not rely on it to protect private or sensitive URLs.</p>
        <p>Once your Harbor site is scaffolded, you can edit <code>public/robots.txt</code> at any time. You do not need to recompile routes just to change the file contents.</p>
    </div>
</section>

<section class="docs-section">
    <h2>Generator</h2>
    <p>Start with the generated output, then adjust the final text if your site needs custom directives.</p>

    <div class="robots-generator-shell">
        <form id="robots_generator_form" class="robots-generator-panel" onsubmit="return false;">
            <div class="robots-generator-field-grid">
                <label class="robots-generator-field">
                    <span class="robots-generator-label">Default Policy</span>
                    <select id="robots_default_policy" class="robots-generator-select" name="default_policy">
                        <option value="allow" selected>Allow crawlers by default</option>
                        <option value="block">Block crawlers by default</option>
                    </select>
                    <span class="robots-generator-help">Use path rules below to narrow or reopen access.</span>
                </label>

                <label class="robots-generator-field">
                    <span class="robots-generator-label">Sitemap URL</span>
                    <input
                        id="robots_sitemap_url"
                        class="robots-generator-input"
                        type="url"
                        name="sitemap_url"
                        placeholder="https://example.com/sitemap.xml"
                        inputmode="url"
                    >
                    <span class="robots-generator-help">Optional. Leave blank if you do not have a sitemap yet.</span>
                </label>

                <label class="robots-generator-field">
                    <span class="robots-generator-label">Crawl Delay</span>
                    <input
                        id="robots_crawl_delay"
                        class="robots-generator-input"
                        type="number"
                        name="crawl_delay"
                        min="1"
                        max="120"
                        step="1"
                        placeholder="10"
                        inputmode="numeric"
                    >
                    <span class="robots-generator-help">Optional. Added only to the default <code>User-agent: *</code> block.</span>
                </label>
            </div>

            <div class="robots-generator-field-grid robots-generator-path-grid">
                <label class="robots-generator-field">
                    <span class="robots-generator-label">Disallow Paths</span>
                    <textarea
                        id="robots_disallow_paths"
                        class="robots-generator-textarea"
                        name="disallow_paths"
                        rows="7"
                        placeholder="/admin/&#10;/drafts/&#10;/storage/"
                    ></textarea>
                    <span class="robots-generator-help">One path per line. Start each path with <code>/</code>.</span>
                </label>

                <label class="robots-generator-field">
                    <span class="robots-generator-label">Allow Paths</span>
                    <textarea
                        id="robots_allow_paths"
                        class="robots-generator-textarea"
                        name="allow_paths"
                        rows="7"
                        placeholder="/assets/&#10;/public-api/"
                    ></textarea>
                    <span class="robots-generator-help">Useful when your default policy blocks everything and you want a few exceptions.</span>
                </label>
            </div>

            <div class="robots-generator-bot-list">
                <div class="robots-generator-bot-list-head">
                    <h3>Crawler Overrides</h3>
                    <p>Use these when one crawler should behave differently from the default block above.</p>
                </div>

                <?php foreach ($robots_generator_bots as $robots_generator_bot) { ?>
                    <div class="robots-generator-bot-row">
                        <div class="robots-generator-bot-meta">
                            <p class="robots-generator-bot-title"><?php echo htmlspecialchars($robots_generator_bot['label'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <p class="robots-generator-bot-description">
                                <code><?php echo htmlspecialchars($robots_generator_bot['agent'], ENT_QUOTES, 'UTF-8'); ?></code>
                                <?php echo htmlspecialchars($robots_generator_bot['description'], ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>

                        <label class="robots-generator-field robots-generator-bot-field">
                            <span class="robots-generator-label">Override</span>
                            <select
                                class="robots-generator-select"
                                data-robots-bot="<?php echo htmlspecialchars($robots_generator_bot['agent'], ENT_QUOTES, 'UTF-8'); ?>"
                            >
                                <option value="inherit" selected>Same as default</option>
                                <option value="allow">Allow all</option>
                                <option value="block">Block all</option>
                            </select>
                        </label>
                    </div>
                <?php } ?>
            </div>

            <div class="button-row">
                <button id="robots_copy_button" class="button button-primary" type="button">Copy Text</button>
                <button id="robots_download_button" class="button button-ghost" type="button">Download robots.txt</button>
                <button id="robots_reset_button" class="button button-ghost" type="button">Start Over</button>
            </div>

            <p id="robots_generator_status" class="robots-generator-status" aria-live="polite"></p>
        </form>

        <div class="robots-generator-preview">
            <div class="robots-generator-preview-head">
                <div>
                    <p class="robots-generator-preview-title">Generated robots.txt</p>
                    <p class="robots-generator-preview-copy">Copy this into <code>public/robots.txt</code>.</p>
                </div>
            </div>

            <textarea
                id="robots_output"
                class="robots-generator-output"
                readonly
                spellcheck="false"
                aria-label="Generated robots.txt output"
            ></textarea>
        </div>
    </div>
</section>

<section class="docs-section">
    <h2>Recommended Harbor Workflow</h2>
    <ol>
        <li>Create your Harbor site with <code>./bin/harbor-init my-site</code>.</li>
        <li>Use this generator to build a starting file.</li>
        <li>Paste the result into <code>my-site/public/robots.txt</code>.</li>
        <li>Edit that file any time as your crawl policy changes.</li>
    </ol>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
