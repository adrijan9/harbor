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

- A route compiler (`.router` -> `routes.php`)
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
  - Matches path segments (supports `$` as a dynamic segment placeholder)
  - Injects matched route data into `$GLOBALS['route']`
  - Requires the matched PHP entry file directly
- `HelperLoader`:
  - `route` for route segment/query access helpers
  - `request` for typed request metadata/body/header helpers
  - `filesystem` for explicit file/directory operations
  - `log` for structured logging helpers and levels
- CLI:
  - `bin/harbor` compiles `.router` files into `routes.php` by default
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
  routes/
    shared.router
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

# Compile my-site/.router -> my-site/routes.php
./bin/harbor my-site/.router

# Serve a site directory (default: public)
./serve.sh my-site
```

You can also run the built-in documentation site:

```bash
./bin/harbor-docs
```

## Runtime Example

Minimal `index.php` for a site:

```php
<?php

declare(strict_types=1);

use PhpFramework\Router\Router;

require __DIR__.'/../vendor/autoload.php';

new Router(__DIR__.'/routes.php')->render();
```

## Multi-Site Positioning

Harbor can host multiple site directories in one repository that share the same core runtime and helpers.
This makes Harbor a practical microservice-style runtime/framework for teams that want isolated services with shared primitives.

Current model:

- Each site has its own `.router`, `routes.php`, and entry files
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
