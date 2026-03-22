# View Renderer Plan

## Scope

This document tracks implementation for:

- `[x] View renderer (native PHP views with layouts/partials at minimum)`

Core decision:

- Harbor core view rendering stays native PHP and intentionally simple.

## Implemented Core API

Namespace: `Harbor\View`

```php
function view(string $view, array $data = [], ?string $layout = null, array $layout_data = []): void
function view_render(string $view, array $data = [], ?string $layout = null, array $layout_data = []): string
function view_partial(string $partial, array $data = []): void
function view_partial_render(string $partial, array $data = []): string
function view_exists(string $view): bool
function view_share(string $key, mixed $value): void
function view_share_many(array $data): void
function view_shared(?string $key = null, mixed $default = null): mixed
function view_clear_shared(): void
function view_set_path(string $path): void
function view_reset_path(): void
function view_path(): string
function view_e(mixed $value): string
```

## Implemented Classes

- `Harbor\View\ViewRenderer`
- `Harbor\View\ViewException`

## Data Rules

When layout is used:

1. Shared data
2. View data
3. Layout data
4. Reserved `$content` injected by renderer

Rule:

- `content` key is reserved when a layout is active.

## Usage Design

### Entry file

```php
use Harbor\Helper;
use function Harbor\View\view;
use function Harbor\View\view_share;

Helper::load_many('view');

view_share('app_name', 'Harbor Site');

view('pages/home', [
    'title' => 'Home',
    'message' => 'Welcome',
], layout: 'layouts/app');
```

### Layout with conditional header + multi-region

```php
<?php

use function Harbor\View\view_partial;

?>
<div class="layout-app">
    <header>
        <?php if (($is_production ?? false) === true): ?>
            <?php view_partial('partials/header_prod'); ?>
        <?php else: ?>
            <?php view_partial('partials/header_dev'); ?>
        <?php endif; ?>
    </header>

    <main><?= $content ?></main>

    <aside>
        <?php if (! empty($right_partial ?? '')): ?>
            <?php view_partial($right_partial, $right_data ?? []); ?>
        <?php endif; ?>
    </aside>
</div>
```

### Escaping in templates

```php
<?php

use function Harbor\View\view_e;

?>
<h1><?= view_e($title) ?></h1>
```

## Out Of Scope (Core Policy)

These are intentionally out of Harbor core scope:

- Blade-like directives/macros
- `@section` / `@yield` stack abstraction
- Component class system
- Template compiler pipeline in core (`*.harbor -> *.php`)
- Async/streaming rendering

Rationale:

- Harbor core must stay simple and native-PHP-first.

## Separate Optional Idea (Non-Core)

Your alternative concept is valid as a separate optional layer:

- `mypage.harbor` source templates
- compiler command producing `.php`
- optional directives (`@section`, `@yield`, ...)

Boundary:

- This belongs in optional package/plugin/tooling, not Harbor core.

## Checklist

- [x] Add `src/View/ViewException.php`
- [x] Add `src/View/ViewRenderer.php`
- [x] Add `src/View/view.php`
- [x] Register `view` in `src/Helper.php`
- [x] Add tests for class + helpers + helper registration
- [x] Add docs page: `documentation/public/pages/view.php`
- [x] Add docs route/navigation integration
- [x] Regenerate docs search index (`./bin/harbor-docs-index`)
