<?php

declare(strict_types=1);

$page_title ??= 'Harbor Documentation';
$page_description ??= 'Documentation for the lightweight Harbor toolkit.';
$page_id ??= 'home';

$docs_navigation = [
    ['id' => 'home', 'label' => 'Overview', 'href' => '/'],
    ['id' => 'installation', 'label' => 'Installation', 'href' => '/installation'],
    ['id' => 'routing', 'label' => 'Routing', 'href' => '/routing'],
    ['id' => 'request', 'label' => 'Request', 'href' => '/request'],
    ['id' => 'filesystem', 'label' => 'Filesystem', 'href' => '/filesystem'],
    ['id' => 'logging', 'label' => 'Logging', 'href' => '/logging'],
    ['id' => 'cli', 'label' => 'CLI', 'href' => '/cli'],
];
?>
<!doctype html>
<html lang="en" data-theme="dark" data-theme-mode="system">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script>
        (() => {
            const root = document.documentElement;
            const system_dark_query = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
            const resolve_theme = (mode) => {
                if ('light' === mode || 'dark' === mode) {
                    return mode;
                }

                return system_dark_query && system_dark_query.matches ? 'dark' : 'light';
            };

            let theme_mode = 'system';

            try {
                const saved_theme_mode = window.localStorage.getItem('harbor_docs_theme_mode');
                if ('light' === saved_theme_mode || 'dark' === saved_theme_mode || 'system' === saved_theme_mode) {
                    theme_mode = saved_theme_mode;
                } else {
                    const legacy_theme = window.localStorage.getItem('harbor_docs_theme');
                    if ('light' === legacy_theme || 'dark' === legacy_theme) {
                        theme_mode = legacy_theme;
                    }
                }
            } catch (error) {
                // Ignore storage access errors and keep default theme.
            }

            root.setAttribute('data-theme-mode', theme_mode);
            root.setAttribute('data-theme', resolve_theme(theme_mode));
        })();
    </script>
    <link rel="stylesheet" href="/assets/css/docs.css">
    <link
        id="hljs_theme_light"
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.10.0/styles/github.min.css"
    >
    <link
        id="hljs_theme_dark"
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.10.0/styles/github-dark.min.css"
        disabled
    >
</head>
<body data-page-id="<?php echo htmlspecialchars($page_id, ENT_QUOTES, 'UTF-8'); ?>">
<div class="bg-shape bg-shape-left"></div>
<div class="bg-shape bg-shape-right"></div>

<header class="topbar">
    <div class="topbar-inner">
        <a href="/" class="brand">
            <img src="/assets/images/harbor_logo.png" alt="Harbor" class="brand-logo brand-logo-light">
            <img src="/assets/images/harbor_dark_logo.png" alt="Harbor" class="brand-logo brand-logo-dark">
            <span class="brand-text">Docs</span>
        </a>

        <div class="topbar-actions">
            <div id="theme_switcher" class="theme-switcher" role="group" aria-label="Theme Mode">
                <button class="theme-option" type="button" data-theme-choice="light" aria-pressed="false" title="Light theme">
                    <svg class="theme-option-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 18a6 6 0 1 1 0-12 6 6 0 0 1 0 12Zm0-16a1 1 0 0 1 1 1v1a1 1 0 1 1-2 0V3a1 1 0 0 1 1-1Zm0 18a1 1 0 0 1 1 1v1a1 1 0 1 1-2 0v-1a1 1 0 0 1 1-1ZM4.93 6.34a1 1 0 0 1 1.41 0l.7.7a1 1 0 0 1-1.41 1.42l-.7-.71a1 1 0 0 1 0-1.41Zm12.03 12.02a1 1 0 0 1 1.41 0l.7.71a1 1 0 0 1-1.41 1.41l-.7-.7a1 1 0 0 1 0-1.42ZM2 13a1 1 0 1 1 0-2h1a1 1 0 1 1 0 2H2Zm19 0a1 1 0 1 1 0-2h1a1 1 0 1 1 0 2h-1ZM5.63 18.36a1 1 0 0 1 1.41 1.42l-.7.7a1 1 0 1 1-1.41-1.41l.7-.71Zm12.74-12.73a1 1 0 0 1 1.41 1.41l-.7.71a1 1 0 0 1-1.41-1.42l.7-.7Z"/>
                    </svg>
                    <span class="theme-option-label">Light</span>
                </button>

                <button class="theme-option" type="button" data-theme-choice="system" aria-pressed="false" title="System theme">
                    <svg class="theme-option-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M4 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-5v2h2a1 1 0 1 1 0 2H9a1 1 0 0 1 0-2h2v-2H6a2 2 0 0 1-2-2V4Zm2 0v9h12V4H6Z"/>
                    </svg>
                    <span class="theme-option-label">System</span>
                </button>

                <button class="theme-option" type="button" data-theme-choice="dark" aria-pressed="false" title="Dark theme">
                    <svg class="theme-option-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M21 15.5A9 9 0 1 1 12.5 3a.8.8 0 0 1 .79 1.07 7 7 0 0 0 8.64 8.64.8.8 0 0 1 1.07.79Z"/>
                    </svg>
                    <span class="theme-option-label">Dark</span>
                </button>
            </div>

            <button id="mobile_nav_toggle" class="mobile-nav-toggle" type="button" aria-expanded="false" aria-controls="docs_sidebar">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>
</header>

<div class="docs-layout">
    <aside id="docs_sidebar" class="docs-sidebar">
        <div class="sidebar-block">
            <p class="sidebar-kicker">Documentation</p>
            <h2 class="sidebar-title">Reference</h2>
        </div>

        <nav class="sidebar-nav" aria-label="Documentation Navigation">
            <?php foreach ($docs_navigation as $navigation_item) { ?>
                <?php $is_active = $page_id === $navigation_item['id']; ?>
                <a
                    class="sidebar-link<?php echo $is_active ? ' is-active' : ''; ?>"
                    href="<?php echo htmlspecialchars($navigation_item['href'], ENT_QUOTES, 'UTF-8'); ?>"
                >
                    <?php echo htmlspecialchars($navigation_item['label'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php } ?>
        </nav>

        <div class="sidebar-block sidebar-note">
            <p class="sidebar-note-label">Quick Command</p>
            <code>./bin/harbor-docs</code>
        </div>
    </aside>

    <main class="docs-main">
