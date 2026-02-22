<?php

declare(strict_types=1);

$current_year = (int) date('Y');
?>
    </main>

    <aside id="docs_toc" class="docs-toc" aria-label="On this page">
        <p class="toc-kicker">On this page</p>
        <nav id="docs_toc_nav" class="toc-nav" aria-label="Page sections"></nav>
    </aside>
</div>

<footer class="site-footer">
    <div class="site-footer-inner">
        <p>Harbor Documentation</p>
        <p>&copy; <?php echo $current_year; ?> Built for practical PHP applications.</p>
    </div>
</footer>

<button id="back_to_top" class="back-to-top" type="button" aria-label="Back to top" title="Back to top">
    <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12.7 5.3a1 1 0 0 0-1.4 0l-6 6a1 1 0 0 0 1.4 1.4L11 8.41V19a1 1 0 1 0 2 0V8.41l4.3 4.29a1 1 0 1 0 1.4-1.4l-6-6Z"/>
    </svg>
</button>

<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.10.0/highlight.min.js"></script>
<script src="/assets/js/docs.js"></script>
</body>
</html>
