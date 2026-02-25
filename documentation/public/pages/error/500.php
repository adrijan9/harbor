<?php

declare(strict_types=1);

http_response_code(500);

$error_data = isset($error) && is_array($error) ? $error : [];
$trace = $error_data['trace'] ?? [];
$trace = is_array($trace) ? $trace : [];

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 Internal Server Error</title>
    <style>
        :root { --bg: #1a2b4b; --accent: #1ea4dd; --text: #f5f9ff; --muted: #b7c8e6; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: var(--bg);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            padding: 24px;
        }
        main {
            width: min(1000px, 100%);
            border: 1px solid rgba(30, 164, 221, 0.42);
            border-radius: 12px;
            padding: 24px;
            background: rgba(6, 17, 38, 0.82);
        }
        h1 { margin: 0 0 14px; font-size: 30px; }
        .accent { color: var(--accent); }
        .meta { margin: 8px 0; color: #dce8ff; line-height: 1.5; }
        .label { color: var(--muted); }
        pre {
            margin: 14px 0 0;
            padding: 14px;
            border-radius: 10px;
            background: #0d1830;
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #dce8ff;
            overflow: auto;
            white-space: pre-wrap;
            word-break: break-word;
            line-height: 1.5;
            font-size: 13px;
        }
    </style>
</head>
<body>
<main>
    <h1><span class="accent">500</span> Internal Server Error</h1>
    <p class="meta"><span class="label">Message:</span> <?= $escape($error_data['message'] ?? 'Unknown error'); ?></p>
    <p class="meta"><span class="label">File:</span> <?= $escape($error_data['file'] ?? 'unknown'); ?></p>
    <p class="meta"><span class="label">Line:</span> <?= $escape($error_data['line'] ?? 0); ?></p>
    <pre><?php foreach ($trace as $frame) : ?>#<?= $escape($frame['index'] ?? 0); ?> <?= $escape($frame['file'] ?? '[internal]'); ?>:<?= $escape($frame['line'] ?? 0); ?> <?= $escape($frame['call'] ?? '{main}'); . PHP_EOL; ?><?php endforeach; ?></pre>
</main>
</body>
</html>
