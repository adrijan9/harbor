(() => {
    const body = document.body;
    const root = document.documentElement;
    const nav_toggle_button = document.getElementById('mobile_nav_toggle');
    const sidebar = document.getElementById('docs_sidebar');
    const system_theme_query = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    const theme_option_buttons = Array.from(document.querySelectorAll('[data-theme-choice]'));

    let theme_mode = 'system';

    const is_valid_theme_mode = (mode) => 'light' === mode || 'dark' === mode || 'system' === mode;

    const resolve_theme = (mode) => {
        if ('light' === mode || 'dark' === mode) {
            return mode;
        }

        return system_theme_query && system_theme_query.matches ? 'dark' : 'light';
    };

    const update_theme_options = (mode) => {
        theme_option_buttons.forEach((button) => {
            const option_mode = button.getAttribute('data-theme-choice') || '';
            const is_active = option_mode === mode;
            button.classList.toggle('is-active', is_active);
            button.setAttribute('aria-pressed', is_active ? 'true' : 'false');
        });
    };

    const apply_theme_mode = (mode, persist_selection) => {
        theme_mode = is_valid_theme_mode(mode) ? mode : 'system';

        const resolved_theme = resolve_theme(theme_mode);
        root.setAttribute('data-theme-mode', theme_mode);
        root.setAttribute('data-theme', resolved_theme);
        update_theme_options(theme_mode);

        if (!persist_selection) {
            return;
        }

        try {
            window.localStorage.setItem('harbor_docs_theme_mode', theme_mode);
            window.localStorage.setItem('harbor_docs_theme', resolved_theme);
        } catch (error) {
            // Ignore storage write errors.
        }
    };

    const load_theme_mode = () => {
        let saved_theme_mode = null;

        try {
            saved_theme_mode = window.localStorage.getItem('harbor_docs_theme_mode');
            if (!is_valid_theme_mode(saved_theme_mode || '')) {
                const legacy_theme = window.localStorage.getItem('harbor_docs_theme');
                if ('light' === legacy_theme || 'dark' === legacy_theme) {
                    saved_theme_mode = legacy_theme;
                } else {
                    saved_theme_mode = 'system';
                }
            }
        } catch (error) {
            saved_theme_mode = 'system';
        }

        apply_theme_mode(saved_theme_mode || 'system', false);
    };

    load_theme_mode();

    theme_option_buttons.forEach((button) => {
        button.addEventListener('click', () => {
            const next_theme_mode = button.getAttribute('data-theme-choice') || 'system';
            apply_theme_mode(next_theme_mode, true);
        });
    });

    const on_system_theme_change = () => {
        if ('system' === theme_mode) {
            apply_theme_mode('system', false);
        }
    };

    if (system_theme_query) {
        if ('function' === typeof system_theme_query.addEventListener) {
            system_theme_query.addEventListener('change', on_system_theme_change);
        } else if ('function' === typeof system_theme_query.addListener) {
            system_theme_query.addListener(on_system_theme_change);
        }
    }

    if (!nav_toggle_button || !sidebar) {
        return;
    }

    const close_nav = () => {
        body.classList.remove('nav-open');
        nav_toggle_button.setAttribute('aria-expanded', 'false');
    };

    const open_nav = () => {
        body.classList.add('nav-open');
        nav_toggle_button.setAttribute('aria-expanded', 'true');
    };

    nav_toggle_button.addEventListener('click', () => {
        if (body.classList.contains('nav-open')) {
            close_nav();

            return;
        }

        open_nav();
    });

    sidebar.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 980) {
                close_nav();
            }
        });
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 980) {
            close_nav();
        }
    });
})();
