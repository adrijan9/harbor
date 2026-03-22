<?php

declare(strict_types=1);

namespace Harbor\View;

require_once __DIR__.'/ViewException.php';

require_once __DIR__.'/ViewRenderer.php';

/** @var null|ViewRenderer $view_renderer_instance */
$view_renderer_instance = null;

/** Public */
function view(string $view, array $data = [], ?string $layout = null, array $layout_data = []): void
{
    echo view_render($view, $data, $layout, $layout_data);
}

function view_render(string $view, array $data = [], ?string $layout = null, array $layout_data = []): string
{
    return view_internal_renderer()->render($view, $data, $layout, $layout_data);
}

function view_partial(string $partial, array $data = []): void
{
    echo view_partial_render($partial, $data);
}

function view_partial_render(string $partial, array $data = []): string
{
    return view_internal_renderer()->render_partial($partial, $data);
}

function view_exists(string $view): bool
{
    return view_internal_renderer()->exists($view);
}

function view_share(string $key, mixed $value): void
{
    view_internal_renderer()->share($key, $value);
}

function view_share_many(array $data): void
{
    view_internal_renderer()->share_many($data);
}

function view_shared(?string $key = null, mixed $default = null): mixed
{
    return view_internal_renderer()->shared($key, $default);
}

function view_clear_shared(): void
{
    view_internal_renderer()->clear_shared();
}

function view_set_path(string $path): void
{
    view_internal_renderer()->set_base_path($path);
}

function view_reset_path(): void
{
    view_internal_renderer()->reset_base_path();
}

function view_path(): string
{
    return view_internal_renderer()->base_path();
}

function view_e(mixed $value): string
{
    if (is_string($value)) {
        $resolved_value = $value;
    } elseif (is_scalar($value)) {
        $resolved_value = (string) $value;
    } elseif (is_object($value) && method_exists($value, '__toString')) {
        $resolved_value = (string) $value;
    } elseif (null === $value) {
        $resolved_value = '';
    } else {
        $json_value = json_encode(
            $value,
            JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );

        $resolved_value = is_string($json_value) ? $json_value : '';
    }

    return htmlspecialchars($resolved_value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Private */
function view_internal_renderer(): ViewRenderer
{
    global $view_renderer_instance;

    if (! $view_renderer_instance instanceof ViewRenderer) {
        $view_renderer_instance = new ViewRenderer();
    }

    return $view_renderer_instance;
}
