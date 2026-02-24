# Harbor

![Harbor Logo](documentation/assets/images/harbor_logo.png#gh-light-mode-only)
![Harbor Logo](documentation/assets/images/harbor_dark_logo.png#gh-dark-mode-only)

> ⚠️ Warning: Harbor is under active development and is not production-ready yet.

> Microservice-oriented PHP runtime primitives. No MVC ceremony.

Harbor is a lightweight, microservice-oriented PHP runtime/framework toolkit for building page-first sites with explicit routing and helper modules.

It is not an MVC framework.  
It does not require controllers.  
It does not enforce architecture.

## What Harbor Is

- A route compiler (`.router` -> `public/routes.php` when `public/` exists, otherwise `routes.php`)
- Route include preprocessing via `#include "path/to/file.router"` (recursive)
- A small runtime router that resolves and includes PHP entry files
- An optional helper loader for focused modules
- CLI tools for scaffolding and docs
- A foundation for independent site/service units that share one core

## What Harbor Is Not

- Not MVC
- Not a full-stack framework out of the box
- Not container-driven
- Not dependency-heavy

If you want controllers, ORMs, DI containers, or a frontend stack, you can add them, but Harbor does not require them.

## Installation
```bash
TODO - still not published to packagist, but you can clone and use the `src/` directory as a local package for now.
```

## Documentation
The documentation site is not publish on the server because it is meant to be a local reference for developers using the framework. You can run it locally with:
```bash
./bin/harbor-docs
```
This command will serve the documentation site on `http://localhost:<SOME_PORT>`, where you can explore the core concepts, API references, and examples in detail.

## Core Building Blocks

- `Router` runtime:
  - Loads route arrays from `routes.php`
  - Requires a config file path (for example `global.php`) and loads it into `$_ENV` as top-level keys
  - Matches path segments (supports `$` as a dynamic segment placeholder)
  - Injects matched route data into `$GLOBALS['route']`
  - Requires the matched PHP entry file directly
- `HelperLoader`:
  - `route` for route segment/query access and named route path/current checks
  - `config` for config file loading and typed env reads
  - `value` for shared blank/null checks (`harbor_is_blank`, `harbor_is_null`)
  - `array` for shared array mutation helpers (`array_forget`)
  - `request` for typed request metadata/body/header helpers
  - `lang` for current locale helpers (`lang_get`, `lang_set`, `lang_is`)
  - `translation` for locale translation loading and lookup (`translation_init`, `t`)
  - `filesystem` for explicit file/directory operations
  - `log` for structured logging helpers and levels
- CLI:
  - `bin/harbor` compiles `.router` files into `public/routes.php` when `public/` exists, otherwise `routes.php`
  - `bin/harbor init` scaffolds a site structure
  - `bin/harbor-docs` serves the local docs site

## Project Layout

```text
harbor/
  bin/
    harbor
    harbor-docs
  src/
    Router/
    Request/
    Filesystem/
    Log/
    HelperLoader.php
  templates/
    site/
      .router
      global.php
      config/
      lang/
      public/
        .htaccess
        index.php
        not_found.php
        routes.php
        pages/
          index.php
  documentation/
    index.php
    routes.php
    pages/
  tests/
  serve.sh
```

Example generated site layout:

```text
my-site/
  .router
  global.php
  config/
  lang/
  public/
    routes.php
    index.php
    not_found.php
    pages/
      index.php
```

Include example:

```ini
# file: my-site/.router
#include "./routes/shared.router"
```

## Quick Start

```bash
composer install

# Scaffold a site directory
./bin/harbor init my-site

# Compile my-site/.router -> my-site/public/routes.php (when my-site/public exists)
./bin/harbor my-site/.router

# Serve a site directory (default: public)
./serve.sh my-site
```

Harbor uses strict front-controller routing by default:

- Every request is sent to `index.php`
- Only paths declared in `.router` (compiled to `public/routes.php` when `public/` exists) are reachable
- Static files are not directly accessible unless you expose them through routes/entries

The `init` command copies files from `templates/site/`, so you can customize future scaffolds by editing that directory.

To expose static files explicitly, declare assets at the top of `.router`:

```ini
<assets>/assets</assets>
```

This enables URLs like `/assets/app.css` and `/assets/logo.png`. If the `<assets>` directive is missing, asset files are not served.

You can also run the built-in documentation site:

```bash
./bin/harbor-docs
```

## Runtime Example

Minimal `public/index.php` for a site:

```php
<?php

declare(strict_types=1);

use Harbor\Router\Router;

require __DIR__.'/../vendor/autoload.php';

new Router(__DIR__.'/routes.php', __DIR__.'/../global.php')->render();
```

## Multi-Site Positioning

Harbor can host multiple site directories in one repository that share the same core runtime and helpers.
This makes Harbor a practical microservice-style runtime/framework for teams that want isolated services with shared primitives.

Current model:

- Each site has its own `.router`, compiled `routes.php`, and entry files
- You run one site root at a time via your server target (`./serve.sh <site-dir>`)
- There is no built-in host-based multi-site dispatcher in the runtime layer

## Current Gaps (Code vs Idea)

- Route definitions include `method`, but runtime matching is currently path-based.

## Development

```bash
composer test # run PHPUnit tests
./vendor/bin/php-cs-fixer fix # run PHP CS Fixer
./bin/harbor documentation/.router # compile docs routes
./bin/harbor-docs # serve docs site
```
