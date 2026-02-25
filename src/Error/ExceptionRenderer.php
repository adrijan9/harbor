<?php

declare(strict_types=1);

namespace Harbor\Error;

final class ExceptionRenderer
{
    public function exception_template_payload(\Throwable $exception): array
    {
        return [
            'type' => $exception::class,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $this->template_trace($exception),
        ];
    }

    public function exception_payload(\Throwable $exception): array
    {
        return [
            'class' => $exception::class,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $this->formatted_trace($exception),
        ];
    }

    public function render_fallback(array $error): void
    {
        $message = htmlspecialchars((string) ($error['message'] ?? 'Unknown error'), ENT_QUOTES, 'UTF-8');
        $file = htmlspecialchars((string) ($error['file'] ?? 'unknown'), ENT_QUOTES, 'UTF-8');
        $line = htmlspecialchars((string) ($error['line'] ?? 0), ENT_QUOTES, 'UTF-8');
        $trace = $error['trace'] ?? [];
        $trace_json = json_encode($trace, JSON_PRETTY_PRINT);
        $trace_output = is_string($trace_json) ? $trace_json : '[]';
        $trace_output = htmlspecialchars($trace_output, ENT_QUOTES, 'UTF-8');

        echo <<<HTML
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
                        padding: 24px;
                        background: var(--bg);
                        color: var(--text);
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                    }
                    .panel {
                        width: min(960px, 100%);
                        background: rgba(6, 17, 38, 0.82);
                        border: 1px solid rgba(30, 164, 221, 0.4);
                        border-radius: 14px;
                        padding: 24px;
                    }
                    h1 { margin: 0 0 10px; font-size: 28px; }
                    .accent { color: var(--accent); }
                    p { margin: 6px 0; color: var(--muted); }
                    pre {
                        margin: 12px 0 0;
                        padding: 14px;
                        border-radius: 10px;
                        background: #0d1830;
                        border: 1px solid rgba(255, 255, 255, 0.08);
                        overflow: auto;
                        white-space: pre-wrap;
                        word-break: break-word;
                        color: #dce8ff;
                        font-size: 13px;
                        line-height: 1.45;
                    }
                </style>
            </head>
            <body>
                <main class="panel">
                    <h1><span class="accent">500</span> Internal Server Error</h1>
                    <p><strong>Message:</strong> {$message}</p>
                    <p><strong>File:</strong> {$file}</p>
                    <p><strong>Line:</strong> {$line}</p>
                    <pre>{$trace_output}</pre>
                </main>
            </body>
            </html>
            HTML;
    }

    private function template_trace(\Throwable $exception): array
    {
        $trace = [];

        foreach ($exception->getTrace() as $frame) {
            if (! is_array($frame)) {
                continue;
            }

            $file = is_string($frame['file'] ?? null) ? $frame['file'] : '[internal]';
            $line = is_int($frame['line'] ?? null) ? $frame['line'] : 0;
            $trace[] = [
                'function' => $this->trace_call($frame),
                'file' => $file,
                'line' => $line,
            ];
        }

        return $trace;
    }

    private function formatted_trace(\Throwable $exception): array
    {
        $trace = [];

        foreach ($exception->getTrace() as $index => $frame) {
            if (! is_array($frame)) {
                continue;
            }

            $file = is_string($frame['file'] ?? null) ? $frame['file'] : '[internal]';
            $line = is_int($frame['line'] ?? null) ? $frame['line'] : 0;
            $trace[] = [
                'index' => $index,
                'file' => $file,
                'line' => $line,
                'call' => $this->trace_call($frame),
            ];
        }

        if (empty($trace)) {
            $trace[] = [
                'index' => 0,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'call' => '{main}',
            ];
        }

        return $trace;
    }

    private function trace_call(array $frame): string
    {
        $class = is_string($frame['class'] ?? null) ? $frame['class'] : '';
        $type = is_string($frame['type'] ?? null) ? $frame['type'] : '';
        $function = is_string($frame['function'] ?? null) ? $frame['function'] : 'unknown';

        return $class.$type.$function.'()';
    }
}
