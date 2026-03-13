# Harbor

![Harbor Logo](documentation/public/assets/images/harbor_logo.png#gh-light-mode-only)
![Harbor Logo](documentation/public/assets/images/harbor_dark_logo.png#gh-dark-mode-only)

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
- CLI tools for scaffolding, config publishing, testing, migrations/seeders, and docs
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

Docs search index is generated locally:
```bash
./bin/harbor-docs-index
```
Run this after every documentation content change so search results stay in sync.

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
  - `cookie` for cookie set/get/forget helpers (`cookie_set`, `cookie_get`, `cookie_forget`) with optional signed/encrypted payload support
  - `session` for simplified session helpers (`session_set`, `session_get`, `session_forget`) with `cookie` / `array` / `file` drivers and config-driven optional signed/encrypted cookie payloads
  - `password` for password hashing wrappers (`password_hash`, `password_verify`, `password_needs_rehash`, `password_info`), `PasswordAlgorithm` enum support, and direct helpers (`bcrypt`, `argon2i`, `argon2id`)
  - `auth` for custom Bearer token helpers (`auth_token_issue`, `auth_token_verify`, `auth_token_revoke`, `auth_logout`) with automatic revocation tracking
  - `response` for response convenience helpers (`response_status`, `response_json`, `response_file`, `response_download`, `response_validation`)
  - `validation` for fluent validation rules and object-based validation results (`validation_rule`, `validation_validate`, `validation_errors`, `validation_has_errors`)
  - `performance` for explicit timing/memory markers (`performance_begin`, `performance_end`, `performance_end_log`) with tracking logs in `current_site_directory/logs/performance_Y-m-d-H-s-i_tracking.log`
  - `units` for byte/time unit conversions and human-readable format helpers (`unit_*`)
  - `lang` for current locale helpers (`lang_get`, `lang_set`, `lang_is`)
  - `translation` for locale translation loading and lookup (`translation_init`, `t`)
  - `filesystem` for explicit file/directory operations
  - `cache` for driver-based and explicit cache helpers (`cache_*`, `cache_array_*`, `cache_file_*`, `cache_apc_*`)
  - `log` for structured logging helpers and levels
- CLI:
  - `bin/harbor` compiles `.router` files into `public/routes.php` when `public/` exists, otherwise `routes.php`
  - `bin/harbor-init` scaffolds a site structure
  - `bin/harbor-test` runs a site's PHPUnit suite using `phpunit.xml`
  - `bin/harbor-config` interactively publishes runtime config templates (`cache`, `database`, `migration`, `session`) into `config/`
  - `bin/harbor-fixer` publishes Harbor's `.php-cs-fixer.dist.php` into the current site root (overwrites without prompt)
  - `bin/harbor-migration` creates/runs/rolls back migration files tracked in `migrations` table
  - `bin/harbor-seed` creates/runs/rolls back seeder files tracked in `seeders` table
  - `bin/harbor-docs` serves the local docs site
  - `bin/harbor-docs-index` rebuilds `documentation/public/assets/search-index.json`

## Project Layout

```text
harbor/
  bin/
    harbor
    harbor-init
    harbor-test
    harbor-config
    harbor-fixer
    harbor-migration
    harbor-seed
    harbor-docs
  src/
    Router/
    Request/
    Response/
    Validation/
    Performance/
    Units/
    Filesystem/
    Cache/
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
        routes.php
        pages/
          error/
            404.php
            405.php
            500.php
          index.php
  documentation/
    .router
    global.php
    public/
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
  phpunit.xml
  serve.sh
  config/
  lang/
  tests/
    TestCase.php
    Feature/
      HomePageTest.php
    Unit/
      ExampleTest.php
  public/
    routes.php
    index.php
    pages/
      error/
        404.php
        405.php
        500.php
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
./bin/harbor-init my-site

# Compile my-site/.router -> my-site/public/routes.php (when my-site/public exists)
./bin/harbor my-site/.router

# Publish config templates for my-site/config (run inside that site directory)
cd my-site
../bin/harbor-test
../bin/harbor-config
# Publish Harbor .php-cs-fixer.dist.php preset into current site root
../bin/harbor-fixer

# Run pending migrations/seeders (uses config/migration.php)
../bin/harbor-migration
../bin/harbor-seed
cd ..

# Serve the current site/public (optional start port, default 8000)
cd my-site
./serve.sh
./serve.sh 8080
cd ..
```

If the requested port is already in use, `serve.sh` automatically increments (`+1`) until it finds an available port.

Harbor uses strict front-controller routing by default:

- Every request is sent to `index.php`
- Only paths declared in `.router` (compiled to `public/routes.php` when `public/` exists) are reachable
- Static files are not directly accessible unless you expose them through routes/entries

The `harbor-init` command copies files from `bin/stubs/site/`, so you can customize future scaffolds by editing that directory.

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
- Each scaffolded site includes its own `serve.sh` that always serves that site's `public/` directory
- There is no built-in host-based multi-site dispatcher in the runtime layer

## Current Gaps (Code vs Idea)

- No production-focused exception sanitization yet (errors currently expose stack details for debugging).

## Development

```bash
composer test # run PHPUnit tests
./vendor/bin/php-cs-fixer fix # run PHP CS Fixer
./bin/harbor documentation/.router # compile docs routes
./bin/harbor-docs-index # rebuild docs search index after docs changes
cd my-site && ../bin/harbor-config # publish runtime config templates
cd my-site && ../bin/harbor-test # run site feature/unit tests from phpunit.xml
cd my-site && ../bin/harbor-fixer # publish .php-cs-fixer.dist.php preset from Harbor root config
cd my-site && ../bin/harbor-migration # run pending migrations
cd my-site && ../bin/harbor-seed # run pending seeders
./bin/harbor-docs # serve docs site
```
