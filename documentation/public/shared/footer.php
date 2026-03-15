<?php

declare(strict_types=1);

$next_navigation_item = null;

if (isset($page_id, $docs_lifecycle) && is_string($page_id) && is_array($docs_lifecycle)) {
    $lifecycle_count = count($docs_lifecycle);

    for ($index = 0; $index < $lifecycle_count; ++$index) {
        $navigation_item = $docs_lifecycle[$index] ?? null;
        if (! is_array($navigation_item)) {
            continue;
        }

        $navigation_item_id = $navigation_item['id'] ?? null;
        if (! is_string($navigation_item_id) || $navigation_item_id !== $page_id) {
            continue;
        }

        $next_navigation_item = $docs_lifecycle[$index + 1] ?? null;
        if (! is_array($next_navigation_item)) {
            $next_navigation_item = null;
        }

        break;
    }
}

$current_year = (int) date('Y');
?>

<?php if (is_array($next_navigation_item)) { ?>
        <section class="docs-section">
            <div class="docs-section-line">
                <p class="docs-section-line-text">Next:</p>
                <a
                    class="button button-primary"
                    href="<?php echo htmlspecialchars((string) ($next_navigation_item['href'] ?? '#'), ENT_QUOTES, 'UTF-8'); ?>"
                >
                    <?php echo htmlspecialchars((string) ($next_navigation_item['label'] ?? 'Next'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>
        </section>
<?php } ?>

        <footer class="site-footer">
            <div class="site-footer-inner">
                <p>Harbor Documentation</p>
                <p>&copy; <?php echo $current_year; ?> Built for practical PHP applications.</p>
            </div>
        </footer>
    </main>

    <aside id="docs_toc" class="docs-toc" aria-label="On this page">
        <p class="toc-kicker">On this page</p>
        <nav id="docs_toc_nav" class="toc-nav" aria-label="Page sections"></nav>
    </aside>
</div>

<button id="back_to_top" class="back-to-top" type="button" aria-label="Back to top" title="Back to top">
    <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12.7 5.3a1 1 0 0 0-1.4 0l-6 6a1 1 0 0 0 1.4 1.4L11 8.41V19a1 1 0 1 0 2 0V8.41l4.3 4.29a1 1 0 1 0 1.4-1.4l-6-6Z"/>
    </svg>
</button>

<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.10.0/highlight.min.js"></script>
<script src="/assets/js/docs.js"></script>
</body>
</html>
