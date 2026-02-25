<?php

declare(strict_types=1);

http_response_code(405);

$allowed_methods = $GLOBALS['route']['allowed_methods'] ?? [];
$allowed_methods = is_array($allowed_methods) ? array_values(array_filter($allowed_methods, 'is_string')) : [];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>405 Method Not Allowed</title>
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
            width: min(680px, 100%);
            border: 1px solid rgba(30, 164, 221, 0.42);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            background: rgba(6, 17, 38, 0.82);
        }
        h1 { margin: 0 0 12px; font-size: 30px; }
        .accent { color: var(--accent); }
        p { margin: 0; color: var(--muted); line-height: 1.6; }
        .meta { margin-top: 12px; font-size: 14px; color: #dce8ff; }
    </style>
</head>
<body>
<main>
    <h1><span class="accent">405</span> Method Not Allowed</h1>
    <p>The route exists, but this HTTP method is not allowed.</p>
</main>
</body>
</html>
