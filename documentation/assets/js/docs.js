(() => {
    const body = document.body;
    const root = document.documentElement;
    const nav_toggle_button = document.getElementById('mobile_nav_toggle');
    const sidebar = document.getElementById('docs_sidebar');
    const docs_main = document.querySelector('.docs-main');
    const toc_container = document.getElementById('docs_toc');
    const toc_nav = document.getElementById('docs_toc_nav');
    const back_to_top_button = document.getElementById('back_to_top');
    const hljs_theme_light = document.getElementById('hljs_theme_light');
    const hljs_theme_dark = document.getElementById('hljs_theme_dark');
    const system_theme_query = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    const reduced_motion_query = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
    const theme_option_buttons = Array.from(document.querySelectorAll('[data-theme-choice]'));

    let theme_mode = 'system';
    const smooth_scroll_behavior = reduced_motion_query && reduced_motion_query.matches ? 'auto' : 'smooth';

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

    const sync_highlight_theme = (resolved_theme) => {
        if (!hljs_theme_light || !hljs_theme_dark) {
            return;
        }

        const is_dark_theme = 'dark' === resolved_theme;
        hljs_theme_dark.disabled = !is_dark_theme;
        hljs_theme_light.disabled = is_dark_theme;
    };

    const init_code_highlight = () => {
        if (window.hljs && 'function' === typeof window.hljs.highlightAll) {
            window.hljs.highlightAll();
        }
    };

    const apply_theme_mode = (mode, persist_selection) => {
        theme_mode = is_valid_theme_mode(mode) ? mode : 'system';

        const resolved_theme = resolve_theme(theme_mode);
        root.setAttribute('data-theme-mode', theme_mode);
        root.setAttribute('data-theme', resolved_theme);
        update_theme_options(theme_mode);
        sync_highlight_theme(resolved_theme);

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
    init_code_highlight();

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

    const build_page_toc = () => {
        if (!docs_main || !toc_container || !toc_nav) {
            return;
        }

        const heading_elements = Array.from(docs_main.querySelectorAll('.docs-section > h2, .docs-section > h3'));
        if (0 === heading_elements.length) {
            toc_container.setAttribute('hidden', 'hidden');

            return;
        }

        toc_container.removeAttribute('hidden');
        toc_nav.innerHTML = '';

        const heading_slug_counts = new Map();
        const toc_links_by_heading_id = new Map();

        const slugify = (value) => {
            const normalized_value = value
                .toLowerCase()
                .trim()
                .replace(/<[^>]*>/g, '')
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');

            return '' !== normalized_value ? normalized_value : 'section';
        };

        const ensure_heading_id = (heading_element) => {
            const existing_id = (heading_element.id || '').trim();
            if ('' !== existing_id) {
                return existing_id;
            }

            const slug = slugify(heading_element.textContent || '');
            const next_count = (heading_slug_counts.get(slug) || 0) + 1;
            heading_slug_counts.set(slug, next_count);

            const generated_id = 1 === next_count ? slug : `${slug}-${next_count}`;
            heading_element.id = generated_id;

            return generated_id;
        };

        heading_elements.forEach((heading_element) => {
            const heading_id = ensure_heading_id(heading_element);
            const toc_link = document.createElement('a');
            const heading_text = (heading_element.textContent || '').trim();

            toc_link.className = `toc-link${'H3' === heading_element.tagName ? ' is-subsection' : ''}`;
            toc_link.href = `#${heading_id}`;
            toc_link.textContent = heading_text;
            toc_link.dataset.targetId = heading_id;
            toc_nav.appendChild(toc_link);

            toc_links_by_heading_id.set(heading_id, toc_link);
        });

        const activate_toc_link = (heading_id) => {
            toc_links_by_heading_id.forEach((toc_link, target_heading_id) => {
                toc_link.classList.toggle('is-active', target_heading_id === heading_id);
            });
        };

        const update_active_link_from_scroll = () => {
            let active_heading_id = heading_elements[0].id;
            const offset = 128;

            heading_elements.forEach((heading_element) => {
                const heading_top = heading_element.getBoundingClientRect().top;
                if (heading_top <= offset) {
                    active_heading_id = heading_element.id;
                }
            });

            activate_toc_link(active_heading_id);
        };

        let scroll_tick_scheduled = false;
        window.addEventListener('scroll', () => {
            if (scroll_tick_scheduled) {
                return;
            }

            scroll_tick_scheduled = true;
            window.requestAnimationFrame(() => {
                update_active_link_from_scroll();
                scroll_tick_scheduled = false;
            });
        }, { passive: true });

        toc_nav.querySelectorAll('.toc-link').forEach((toc_link) => {
            toc_link.addEventListener('click', (event) => {
                const target_heading_id = toc_link.dataset.targetId || '';
                if ('' !== target_heading_id) {
                    const target_heading = document.getElementById(target_heading_id);
                    if (target_heading) {
                        event.preventDefault();

                        const topbar_element = document.querySelector('.topbar');
                        const topbar_height = topbar_element instanceof HTMLElement ? topbar_element.offsetHeight : 0;
                        const target_scroll_top = Math.max(
                            0,
                            window.scrollY + target_heading.getBoundingClientRect().top - topbar_height - 16
                        );

                        window.scrollTo({
                            top: target_scroll_top,
                            behavior: smooth_scroll_behavior,
                        });

                        window.history.replaceState(null, '', `#${target_heading_id}`);
                    }

                    activate_toc_link(target_heading_id);
                }
            });
        });

        const hash_heading_id = window.location.hash.replace('#', '').trim();
        if ('' !== hash_heading_id && toc_links_by_heading_id.has(hash_heading_id)) {
            activate_toc_link(hash_heading_id);
        } else {
            update_active_link_from_scroll();
        }
    };

    build_page_toc();

    const setup_back_to_top = () => {
        if (!back_to_top_button) {
            return;
        }

        const update_visibility = () => {
            back_to_top_button.classList.toggle('is-visible', window.scrollY > 100);
        };

        let scroll_tick_scheduled = false;
        window.addEventListener('scroll', () => {
            if (scroll_tick_scheduled) {
                return;
            }

            scroll_tick_scheduled = true;
            window.requestAnimationFrame(() => {
                update_visibility();
                scroll_tick_scheduled = false;
            });
        }, { passive: true });

        back_to_top_button.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: smooth_scroll_behavior,
            });
        });

        update_visibility();
    };

    setup_back_to_top();

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
