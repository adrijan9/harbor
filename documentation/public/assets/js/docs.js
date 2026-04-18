(() => {
    const body = document.body;
    const root = document.documentElement;
    const nav_toggle_button = document.getElementById('mobile_nav_toggle');
    const sidebar = document.getElementById('docs_sidebar');
    const docs_main = document.querySelector('.docs-main');
    const toc_container = document.getElementById('docs_toc');
    const toc_nav = document.getElementById('docs_toc_nav');
    const back_to_top_button = document.getElementById('back_to_top');
    const repo_star_count = document.getElementById('repo_star_count');
    const search_form = document.getElementById('docs_search_form');
    const search_input = document.getElementById('docs_search_input');
    const search_results = document.getElementById('docs_search_results');
    const search_status = document.getElementById('docs_search_status');
    const search_results_list = document.getElementById('docs_search_results_list');
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

    const setup_repo_star_count = () => {
        if (!repo_star_count) {
            return;
        }

        const format_count = (count) => {
            if (!Number.isFinite(count) || count < 0) {
                return '--';
            }

            if (count >= 1_000_000) {
                return `${(count / 1_000_000).toFixed(1).replace(/\\.0$/, '')}m`;
            }

            if (count >= 1_000) {
                return `${(count / 1_000).toFixed(1).replace(/\\.0$/, '')}k`;
            }

            return String(count);
        };

        fetch('https://api.github.com/repos/adrijan9/harbor', {
            headers: {
                Accept: 'application/vnd.github+json',
            },
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`GitHub API response: ${response.status}`);
                }

                return response.json();
            })
            .then((data) => {
                const stars = Number(data && data.stargazers_count);
                repo_star_count.textContent = format_count(stars);
            })
            .catch(() => {
                // Keep fallback value when API request fails.
            });
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
    setup_repo_star_count();
    setup_docs_search();
    setup_robots_txt_generator();

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

    function setup_docs_search() {
        if (!search_form || !search_input || !search_results || !search_status || !search_results_list) {
            return;
        }

        let search_items = [];
        let search_index_loaded = false;
        let search_index_failed = false;
        let last_query = '';
        let active_result_index = -1;

        const normalize_text = (value) => String(value || '').toLowerCase().trim();
        const result_links = () => Array.from(search_results_list.querySelectorAll('.docs-search-result-link'));

        const clear_active_result = () => {
            active_result_index = -1;

            result_links().forEach((link) => {
                link.classList.remove('is-active');
                link.setAttribute('aria-selected', 'false');
            });
        };

        const set_active_result = (index, should_scroll = false) => {
            const links = result_links();

            if (0 === links.length) {
                clear_active_result();

                return;
            }

            const normalized_index = Math.max(0, Math.min(index, links.length - 1));
            active_result_index = normalized_index;

            links.forEach((link, link_index) => {
                const is_active = link_index === normalized_index;
                link.classList.toggle('is-active', is_active);
                link.setAttribute('aria-selected', is_active ? 'true' : 'false');

                if (is_active && should_scroll) {
                    link.scrollIntoView({
                        block: 'nearest',
                        inline: 'nearest',
                    });
                }
            });
        };

        const show_results = () => {
            search_results.hidden = false;
        };

        const hide_results = () => {
            search_results.hidden = true;
            search_results_list.innerHTML = '';
            search_status.textContent = 'Type to search docs.';
            search_status.hidden = false;
            clear_active_result();
        };

        const set_status = (message) => {
            search_results_list.innerHTML = '';
            search_status.textContent = message;
            search_status.hidden = false;
            clear_active_result();
            show_results();
        };

        const result_snippet = (item, query_tokens) => {
            const content = String(item.content || '');
            if ('' === content) {
                return '';
            }

            const lower_content = content.toLowerCase();
            let first_match_index = -1;

            query_tokens.forEach((token) => {
                const index = lower_content.indexOf(token);
                if (index >= 0 && (-1 === first_match_index || index < first_match_index)) {
                    first_match_index = index;
                }
            });

            if (-1 === first_match_index) {
                return content.slice(0, 130);
            }

            const snippet_start = Math.max(0, first_match_index - 38);
            const snippet = content.slice(snippet_start, snippet_start + 150).trim();

            return snippet_start > 0 ? `...${snippet}` : snippet;
        };

        const score_item = (item, query_tokens) => {
            const title_text = normalize_text(item.title);
            const path_text = normalize_text(item.path);
            const description_text = normalize_text(item.description);
            const headings_text = normalize_text(Array.isArray(item.headings) ? item.headings.join(' ') : '');
            const content_text = normalize_text(item.content);
            const searchable = `${title_text} ${path_text} ${description_text} ${headings_text} ${content_text}`;

            let score = 0;

            for (const token of query_tokens) {
                if (!searchable.includes(token)) {
                    return null;
                }

                if (title_text.includes(token)) {
                    score += 55;
                }

                if (path_text.includes(token)) {
                    score += 30;
                }

                if (headings_text.includes(token)) {
                    score += 20;
                }

                if (description_text.includes(token)) {
                    score += 12;
                }

                if (content_text.includes(token)) {
                    score += 4;
                }
            }

            if (title_text.startsWith(query_tokens[0])) {
                score += 18;
            }

            return {
                item,
                score,
            };
        };

        const render_results = (query) => {
            const normalized_query = normalize_text(query);
            const query_tokens = normalized_query.split(/\s+/).filter((token) => '' !== token);

            if (0 === query_tokens.length) {
                hide_results();

                return;
            }

            if (!search_index_loaded) {
                set_status(search_index_failed ? 'Search index failed to load.' : 'Loading search index...');

                return;
            }

            const scored_results = search_items
                .map((item) => score_item(item, query_tokens))
                .filter((candidate) => candidate && Number.isFinite(candidate.score))
                .sort((left, right) => {
                    if (right.score !== left.score) {
                        return right.score - left.score;
                    }

                    return String(left.item.title || '').localeCompare(String(right.item.title || ''));
                })
                .slice(0, 8);

            if (0 === scored_results.length) {
                set_status(`No results for "${query}".`);

                return;
            }

            search_results_list.innerHTML = '';
            search_status.hidden = true;

            scored_results.forEach((result, result_index) => {
                const list_item = document.createElement('li');
                list_item.className = 'docs-search-result-item';

                const link = document.createElement('a');
                link.className = 'docs-search-result-link';
                link.href = String(result.item.path || '/');
                link.dataset.resultIndex = String(result_index);
                link.setAttribute('aria-selected', 'false');

                const title = document.createElement('p');
                title.className = 'docs-search-result-title';
                title.textContent = String(result.item.title || result.item.path || '/');

                const path = document.createElement('p');
                path.className = 'docs-search-result-path';
                path.textContent = String(result.item.path || '/');

                const snippet = document.createElement('p');
                snippet.className = 'docs-search-result-snippet';
                snippet.textContent = result_snippet(result.item, query_tokens);

                link.appendChild(title);
                link.appendChild(path);
                if ('' !== String(snippet.textContent || '').trim()) {
                    link.appendChild(snippet);
                }

                list_item.appendChild(link);
                search_results_list.appendChild(list_item);
            });

            clear_active_result();
            show_results();
        };

        fetch('/assets/search-index.json', { cache: 'no-cache' })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`Search index response: ${response.status}`);
                }

                return response.json();
            })
            .then((payload) => {
                const items = payload && Array.isArray(payload.items) ? payload.items : [];
                search_items = items
                    .filter((item) => item && 'object' === typeof item)
                    .map((item) => ({
                        path: String(item.path || '/'),
                        title: String(item.title || item.path || '/'),
                        description: String(item.description || ''),
                        headings: Array.isArray(item.headings) ? item.headings.map((heading) => String(heading || '')) : [],
                        content: String(item.content || ''),
                    }));
                search_index_loaded = true;

                if ('' !== last_query.trim()) {
                    render_results(last_query);
                }
            })
            .catch(() => {
                search_index_loaded = true;
                search_index_failed = true;

                if ('' !== last_query.trim()) {
                    set_status('Search index failed to load.');
                }
            });

        search_form.addEventListener('submit', (event) => {
            event.preventDefault();
            render_results(search_input.value || '');
        });

        search_input.addEventListener('input', () => {
            last_query = search_input.value || '';
            render_results(last_query);
        });

        search_input.addEventListener('focus', () => {
            if ('' !== (search_input.value || '').trim()) {
                render_results(search_input.value || '');
            }
        });

        search_input.addEventListener('keydown', (event) => {
            const links = result_links();

            if ('ArrowDown' === event.key || 'ArrowUp' === event.key) {
                if (0 === links.length) {
                    return;
                }

                event.preventDefault();

                if ('ArrowDown' === event.key) {
                    const next_index = -1 === active_result_index ? 0 : (active_result_index + 1) % links.length;
                    set_active_result(next_index, true);

                    return;
                }

                const previous_index = -1 === active_result_index
                    ? links.length - 1
                    : (active_result_index - 1 + links.length) % links.length;
                set_active_result(previous_index, true);

                return;
            }

            if ('Enter' === event.key && active_result_index >= 0) {
                const selected_link = links[active_result_index] || null;
                if (!selected_link) {
                    return;
                }

                event.preventDefault();
                window.location.assign(selected_link.href);
            }
        });

        search_results_list.addEventListener('mousemove', (event) => {
            const target = event.target;
            const hovered_link = target instanceof HTMLElement ? target.closest('.docs-search-result-link') : null;

            if (!hovered_link) {
                return;
            }

            const hovered_index = Number.parseInt(hovered_link.dataset.resultIndex || '-1', 10);
            if (Number.isInteger(hovered_index) && hovered_index >= 0) {
                set_active_result(hovered_index, false);
            }
        });

        search_results_list.addEventListener('click', () => {
            hide_results();
        });

        document.addEventListener('click', (event) => {
            if (!search_form.contains(event.target)) {
                hide_results();
            }
        });

        document.addEventListener('keydown', (event) => {
            if ('/' === event.key) {
                const target = event.target;
                const tag_name = target && target.tagName ? target.tagName.toLowerCase() : '';
                const is_form_element = ['input', 'textarea', 'select'].includes(tag_name);
                const is_editable = Boolean(target && target.isContentEditable);

                if (is_form_element || is_editable) {
                    return;
                }

                event.preventDefault();
                search_input.focus();
                search_input.select();

                return;
            }

            if ('Escape' === event.key && search_form.contains(document.activeElement)) {
                hide_results();
                search_input.blur();
            }
        });
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

    function setup_robots_txt_generator() {
        if ('robots_generator' !== (body.dataset.pageId || '')) {
            return;
        }

        const form = document.getElementById('robots_generator_form');
        const default_policy_input = document.getElementById('robots_default_policy');
        const sitemap_url_input = document.getElementById('robots_sitemap_url');
        const crawl_delay_input = document.getElementById('robots_crawl_delay');
        const disallow_paths_input = document.getElementById('robots_disallow_paths');
        const allow_paths_input = document.getElementById('robots_allow_paths');
        const output = document.getElementById('robots_output');
        const copy_button = document.getElementById('robots_copy_button');
        const download_button = document.getElementById('robots_download_button');
        const reset_button = document.getElementById('robots_reset_button');
        const status = document.getElementById('robots_generator_status');
        const bot_override_inputs = Array.from(document.querySelectorAll('[data-robots-bot]'));

        if (
            !(form instanceof HTMLFormElement) ||
            !(default_policy_input instanceof HTMLSelectElement) ||
            !(sitemap_url_input instanceof HTMLInputElement) ||
            !(crawl_delay_input instanceof HTMLInputElement) ||
            !(disallow_paths_input instanceof HTMLTextAreaElement) ||
            !(allow_paths_input instanceof HTMLTextAreaElement) ||
            !(output instanceof HTMLTextAreaElement) ||
            !(copy_button instanceof HTMLButtonElement) ||
            !(download_button instanceof HTMLButtonElement) ||
            !(reset_button instanceof HTMLButtonElement)
        ) {
            return;
        }

        let status_timeout_id = 0;

        const set_status = (message) => {
            if (!(status instanceof HTMLElement)) {
                return;
            }

            status.textContent = message;

            if (0 !== status_timeout_id) {
                window.clearTimeout(status_timeout_id);
            }

            if ('' === message) {
                status_timeout_id = 0;

                return;
            }

            status_timeout_id = window.setTimeout(() => {
                status.textContent = '';
                status_timeout_id = 0;
            }, 2400);
        };

        const normalize_path = (value) => {
            const trimmed_value = String(value || '').trim();
            if ('' === trimmed_value) {
                return '';
            }

            if ('/' === trimmed_value || trimmed_value.startsWith('/')) {
                return trimmed_value;
            }

            return `/${trimmed_value.replace(/^\/+/, '')}`;
        };

        const unique_values = (values) => Array.from(new Set(values));

        const parse_paths = (value) => unique_values(
            String(value || '')
                .split(/\r?\n/u)
                .map(normalize_path)
                .filter((path) => '' !== path)
        );

        const resolve_crawl_delay = () => {
            const parsed_value = Number.parseInt(crawl_delay_input.value || '', 10);
            if (!Number.isFinite(parsed_value) || parsed_value < 1) {
                return null;
            }

            return Math.min(parsed_value, 120);
        };

        const render_output = () => {
            const default_policy = default_policy_input.value || 'allow';
            const sitemap_url = (sitemap_url_input.value || '').trim();
            const crawl_delay = resolve_crawl_delay();
            const disallow_paths = parse_paths(disallow_paths_input.value);
            const allow_paths = parse_paths(allow_paths_input.value);
            const lines = ['User-agent: *'];

            if ('block' === default_policy) {
                lines.push('Disallow: /');
                allow_paths.forEach((path) => {
                    lines.push(`Allow: ${path}`);
                });
            } else if (0 === disallow_paths.length && 0 === allow_paths.length) {
                lines.push('Allow: /');
            } else {
                disallow_paths.forEach((path) => {
                    lines.push(`Disallow: ${path}`);
                });
                allow_paths.forEach((path) => {
                    lines.push(`Allow: ${path}`);
                });
            }

            if (Number.isInteger(crawl_delay) && crawl_delay > 0) {
                lines.push(`Crawl-delay: ${crawl_delay}`);
            }

            bot_override_inputs.forEach((input) => {
                if (!(input instanceof HTMLSelectElement)) {
                    return;
                }

                const bot_name = (input.dataset.robotsBot || '').trim();
                const bot_policy = (input.value || 'inherit').trim();
                if ('' === bot_name || 'inherit' === bot_policy) {
                    return;
                }

                lines.push('');
                lines.push(`User-agent: ${bot_name}`);
                lines.push('allow' === bot_policy ? 'Allow: /' : 'Disallow: /');
            });

            if ('' !== sitemap_url) {
                lines.push('');
                lines.push(`Sitemap: ${sitemap_url}`);
            }

            output.value = lines.join('\n');
        };

        const copy_output = async () => {
            const text = output.value || '';
            if ('' === text.trim()) {
                set_status('Nothing to copy.');

                return;
            }

            output.focus();
            output.select();

            if (navigator.clipboard && 'function' === typeof navigator.clipboard.writeText) {
                try {
                    await navigator.clipboard.writeText(text);
                    set_status('Copied to clipboard.');

                    return;
                } catch (error) {
                    // Fall back to document.execCommand when clipboard access fails.
                }
            }

            try {
                const copied = document.execCommand('copy');
                set_status(copied ? 'Copied to clipboard.' : 'Copy failed. Copy the text manually.');
            } catch (error) {
                set_status('Copy failed. Copy the text manually.');
            }
        };

        const download_output = () => {
            const text = output.value || '';
            if ('' === text.trim()) {
                set_status('Nothing to download.');

                return;
            }

            const blob = new Blob([text], {
                type: 'text/plain;charset=utf-8',
            });
            const object_url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');

            link.href = object_url;
            link.download = 'robots.txt';
            document.body.appendChild(link);
            link.click();
            link.remove();

            window.setTimeout(() => {
                window.URL.revokeObjectURL(object_url);
            }, 1000);

            set_status('Downloaded robots.txt.');
        };

        const reset_generator = () => {
            form.reset();
            set_status('');
            render_output();
        };

        form.addEventListener('input', render_output);
        form.addEventListener('change', render_output);
        copy_button.addEventListener('click', () => {
            void copy_output();
        });
        download_button.addEventListener('click', download_output);
        reset_button.addEventListener('click', reset_generator);

        render_output();
    }

    const scroll_sidebar_to_active_link = () => {
        if (!sidebar) {
            return;
        }

        const active_sidebar_link = sidebar.querySelector('.sidebar-link.is-active');
        if (!(active_sidebar_link instanceof HTMLElement)) {
            return;
        }

        const sidebar_rect = sidebar.getBoundingClientRect();
        const link_rect = active_sidebar_link.getBoundingClientRect();
        const top_offset = 84;
        const target_scroll_top = sidebar.scrollTop + (link_rect.top - sidebar_rect.top) - top_offset;

        sidebar.scrollTo({
            top: Math.max(0, target_scroll_top),
            behavior: 'auto',
        });
    };

    window.requestAnimationFrame(scroll_sidebar_to_active_link);
    window.addEventListener('load', scroll_sidebar_to_active_link, { once: true });

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
        scroll_sidebar_to_active_link();
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
