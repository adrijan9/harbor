<?php

declare(strict_types=1);

function harbor_exception_escape(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function harbor_exception_read_file_source(mixed $file_path): ?string
{
    if (! is_string($file_path) || '' === trim($file_path)) {
        return null;
    }

    if (! is_file($file_path) || ! is_readable($file_path)) {
        return null;
    }

    $content = file_get_contents($file_path);
    if (false === $content) {
        return null;
    }

    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $content);
}

$raw_exception_payload = $_EXCEPTION['FULL'] ?? '';
$decoded_exception_payload = [];

if (is_string($raw_exception_payload) && '' !== trim($raw_exception_payload)) {
    $decoded = json_decode($raw_exception_payload, true);
    if (is_array($decoded)) {
        $decoded_exception_payload = $decoded;
    }
}

$exception_type = is_string($decoded_exception_payload['type'] ?? null)
    ? $decoded_exception_payload['type']
    : 'Unhandled Exception';
$exception_message = is_string($decoded_exception_payload['message'] ?? null)
    ? $decoded_exception_payload['message']
    : 'No exception message available.';
$exception_file = is_string($decoded_exception_payload['file'] ?? null)
    ? $decoded_exception_payload['file']
    : 'unknown';
$exception_line = $decoded_exception_payload['line'] ?? 0;
$exception_line = is_int($exception_line) ? $exception_line : (int) $exception_line;
$trace = $decoded_exception_payload['trace'] ?? [];
$trace = is_array($trace) ? $trace : [];

$frames = [];

$frames[] = [
    'title' => basename($exception_file).':'.$exception_line,
    'call' => $exception_type,
    'file' => $exception_file,
    'line' => $exception_line,
    'source' => harbor_exception_read_file_source($exception_file),
];

foreach ($trace as $trace_frame) {
    if (! is_array($trace_frame)) {
        continue;
    }

    $trace_file = is_string($trace_frame['file'] ?? null) ? $trace_frame['file'] : '[internal]';
    $trace_line = $trace_frame['line'] ?? 0;
    $trace_line = is_int($trace_line) ? $trace_line : (int) $trace_line;
    $trace_call = is_string($trace_frame['function'] ?? null) ? $trace_frame['function'] : 'unknown()';

    $frames[] = [
        'title' => basename($trace_file).':'.$trace_line,
        'call' => $trace_call,
        'file' => $trace_file,
        'line' => $trace_line,
        'source' => harbor_exception_read_file_source($trace_file),
    ];
}

$frames_json = json_encode(
    $frames,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
);

if (! is_string($frames_json)) {
    $frames_json = '[]';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Harbor Exception</title>
    <style>
        :root {
            --accent: #1ea4dd;
        }

        body {
            --bg: #1a2b4b;
            --panel: #0f1f3b;
            --panel-2: #12264a;
            --text: #f2f7ff;
            --muted: #b6c9e8;
            --line-bg: #0d1a33;
            --line-border: rgba(255, 255, 255, 0.06);
            --line-number: #7f9ac3;
            margin: 0;
            min-height: 100vh;
            background: radial-gradient(circle at top right, #244677, #1a2b4b 40%);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        body[data-theme='light'] {
            --bg: #edf4fc;
            --panel: #ffffff;
            --panel-2: #f6fbff;
            --text: #10213d;
            --muted: #486288;
            --line-bg: #f3f8ff;
            --line-border: rgba(16, 33, 61, 0.08);
            --line-number: #6d7f9c;
            background: radial-gradient(circle at top right, #d7ecff, #edf4fc 40%);
        }

        * {
            box-sizing: border-box;
        }

        .layout {
            width: min(1280px, 100%);
            margin: 0 auto;
            padding: 20px;
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
        }

        .status-badge {
            display: inline-flex;
            padding: 2px 10px;
            border-radius: 999px;
            border: 1px solid rgba(30, 164, 221, 0.45);
            background: rgba(30, 164, 221, 0.12);
            color: var(--accent);
            font-size: 12px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .theme-buttons {
            display: inline-flex;
            gap: 8px;
            background: var(--panel);
            border: 1px solid rgba(30, 164, 221, 0.35);
            padding: 6px;
            border-radius: 10px;
        }

        .theme-button {
            border: 1px solid transparent;
            background: transparent;
            color: var(--muted);
            font-size: 12px;
            padding: 6px 10px;
            border-radius: 8px;
            cursor: pointer;
        }

        .theme-button.is-active {
            color: var(--text);
            border-color: rgba(30, 164, 221, 0.45);
            background: rgba(30, 164, 221, 0.14);
        }

        .panel {
            background: var(--panel);
            border: 1px solid rgba(30, 164, 221, 0.35);
            border-radius: 14px;
            overflow: hidden;
        }

        .header {
            padding: 18px 20px;
            border-bottom: 1px solid rgba(30, 164, 221, 0.28);
            background: var(--panel-2);
        }

        .header h1 {
            margin: 0;
            font-size: 26px;
            line-height: 1.2;
        }

        .header p {
            margin: 10px 0 0;
            color: var(--muted);
            line-height: 1.5;
            word-break: break-word;
        }

        .grid {
            display: grid;
            grid-template-columns: 340px 1fr;
            min-height: 520px;
        }

        .trace-column {
            border-right: 1px solid rgba(30, 164, 221, 0.26);
            background: var(--panel-2);
            overflow: auto;
            max-height: 70vh;
        }

        .trace-item {
            width: 100%;
            border: 0;
            border-bottom: 1px solid rgba(30, 164, 221, 0.18);
            background: transparent;
            color: inherit;
            text-align: left;
            padding: 12px 14px;
            cursor: pointer;
            display: grid;
            gap: 4px;
        }

        .trace-item:hover {
            background: rgba(30, 164, 221, 0.1);
        }

        .trace-item.is-active {
            background: rgba(30, 164, 221, 0.16);
            outline: 1px solid rgba(30, 164, 221, 0.45);
            outline-offset: -1px;
        }

        .trace-item-title {
            font-weight: 700;
            font-size: 13px;
            color: var(--text);
        }

        .trace-item-file {
            font-size: 12px;
            color: var(--muted);
            word-break: break-word;
        }

        .trace-item-call {
            font-size: 12px;
            color: var(--accent);
            word-break: break-word;
        }

        .code-column {
            padding: 14px;
            display: grid;
            gap: 12px;
        }

        .frame-meta {
            font-size: 13px;
            color: var(--muted);
            word-break: break-word;
            line-height: 1.55;
        }

        .frame-meta strong {
            color: var(--text);
        }

        .code-window {
            border: 1px solid var(--line-border);
            border-radius: 10px;
            background: var(--line-bg);
            overflow: auto;
            max-height: 60vh;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 12px;
            line-height: 1.45;
        }

        .code-line {
            display: grid;
            grid-template-columns: 58px 1fr;
            border-bottom: 1px solid var(--line-border);
        }

        .code-line:last-child {
            border-bottom: 0;
        }

        .code-line.is-highlight {
            background: rgba(30, 164, 221, 0.18);
        }

        .line-number {
            padding: 6px 10px;
            color: var(--line-number);
            border-right: 1px solid var(--line-border);
            text-align: right;
            user-select: none;
        }

        .line-text {
            padding: 6px 10px;
            color: var(--text);
            white-space: pre;
            overflow-x: auto;
        }

        .empty-source {
            padding: 14px;
            color: var(--muted);
        }

        @media (max-width: 960px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .trace-column {
                border-right: 0;
                border-bottom: 1px solid rgba(30, 164, 221, 0.26);
                max-height: 34vh;
            }
        }
    </style>
</head>
<body>
<div class="layout">
    <div class="toolbar">
        <div class="status">
            <span class="status-badge">Harbor</span>
            <span>Exception Debug Page</span>
        </div>
        <div class="theme-buttons">
            <button type="button" class="theme-button" data-theme-mode="system">System</button>
            <button type="button" class="theme-button" data-theme-mode="dark">Dark</button>
            <button type="button" class="theme-button" data-theme-mode="light">Light</button>
        </div>
    </div>

    <section class="panel">
        <header class="header">
            <h1><?php echo harbor_exception_escape($exception_type); ?></h1>
            <p><?php echo harbor_exception_escape($exception_message); ?></p>
            <p><?php echo harbor_exception_escape($exception_file); ?>:<?php echo harbor_exception_escape($exception_line); ?></p>
        </header>

        <div class="grid">
            <aside class="trace-column" id="trace-column"></aside>
            <section class="code-column">
                <div class="frame-meta" id="frame-meta"></div>
                <div class="code-window" id="code-window"></div>
            </section>
        </div>
    </section>
</div>

<script id="exception-frames" type="application/json"><?php echo $frames_json; ?></script>
<script>
    (() => {
        const theme_storage_key = 'harbor_exception_theme_mode';
        const system_query = window.matchMedia('(prefers-color-scheme: dark)');
        const theme_buttons = Array.from(document.querySelectorAll('.theme-button'));

        function resolve_theme(mode) {
            if (mode === 'system') {
                return system_query.matches ? 'dark' : 'light';
            }

            return mode === 'light' ? 'light' : 'dark';
        }

        function apply_theme(mode) {
            const resolved = resolve_theme(mode);
            document.body.setAttribute('data-theme', resolved);

            theme_buttons.forEach((button) => {
                button.classList.toggle('is-active', button.dataset.themeMode === mode);
            });
        }

        function current_theme_mode() {
            const saved = window.localStorage.getItem(theme_storage_key);
            if (saved === 'dark' || saved === 'light' || saved === 'system') {
                return saved;
            }

            return 'system';
        }

        let theme_mode = current_theme_mode();
        apply_theme(theme_mode);

        theme_buttons.forEach((button) => {
            button.addEventListener('click', () => {
                theme_mode = button.dataset.themeMode || 'system';
                window.localStorage.setItem(theme_storage_key, theme_mode);
                apply_theme(theme_mode);
            });
        });

        system_query.addEventListener('change', () => {
            if (theme_mode === 'system') {
                apply_theme('system');
            }
        });

        const trace_column = document.getElementById('trace-column');
        const frame_meta = document.getElementById('frame-meta');
        const code_window = document.getElementById('code-window');
        const frames_node = document.getElementById('exception-frames');

        const frames = (() => {
            if (!frames_node) {
                return [];
            }

            try {
                const parsed = JSON.parse(frames_node.textContent || '[]');
                return Array.isArray(parsed) ? parsed : [];
            } catch (_error) {
                return [];
            }
        })();

        function escape_html(value) {
            return String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#39;');
        }

        function render_trace_list(active_index) {
            trace_column.innerHTML = '';

            frames.forEach((frame, index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'trace-item';
                if (index === active_index) {
                    button.classList.add('is-active');
                }

                button.innerHTML = `
                    <span class="trace-item-title">${escape_html(frame.title || 'frame')}</span>
                    <span class="trace-item-file">${escape_html(frame.file || '[internal]')}</span>
                    <span class="trace-item-call">${escape_html(frame.call || 'unknown()')}</span>
                `;

                button.addEventListener('click', () => {
                    render_frame(index);
                });

                trace_column.appendChild(button);
            });
        }

        function render_source(source, highlight_line) {
            if (typeof source !== 'string') {
                return '<div class="empty-source">Source unavailable for this frame.</div>';
            }

            const normalized = source.replaceAll('\r\n', '\n').replaceAll('\r', '\n');
            const lines = normalized.split('\n');

            return lines
                .map((line, index) => {
                    const line_number = index + 1;
                    const highlight_class = line_number === highlight_line ? ' is-highlight' : '';

                    return `<div class="code-line${highlight_class}">
                        <span class="line-number">${line_number}</span>
                        <span class="line-text">${escape_html(line === '' ? ' ' : line)}</span>
                    </div>`;
                })
                .join('');
        }

        function render_frame(index) {
            const frame = frames[index] || null;
            if (!frame) {
                frame_meta.innerHTML = 'No frame data available.';
                code_window.innerHTML = '<div class="empty-source">No source available.</div>';

                return;
            }

            frame_meta.innerHTML = `
                <strong>File:</strong> ${escape_html(frame.file || '[internal]')}<br>
                <strong>Line:</strong> ${escape_html(frame.line || 0)}<br>
                <strong>Call:</strong> ${escape_html(frame.call || 'unknown()')}
            `;

            code_window.innerHTML = render_source(frame.source, Number(frame.line || 0));
            render_trace_list(index);
        }

        render_frame(0);
    })();
</script>
</body>
</html>
