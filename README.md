# Harbor

![Harbor Logo](documentation/public/assets/images/harbor_logo.png#gh-light-mode-only)
![Harbor Logo](documentation/public/assets/images/harbor_dark_logo.png#gh-dark-mode-only)

> Warning: Harbor is under active development and is not production-ready yet.
>
> Microservice-oriented PHP runtime primitives. No MVC ceremony.

## What Is Harbor

Harbor is a lightweight PHP runtime/framework toolkit for page-first projects with explicit routing and helper modules.

- It is not an MVC framework.
- It does not require controllers.
- It does not enforce architecture.

## Getting Started

Requirement: PHP 8.5 or higher.

1. Create an empty project directory.

```bash
mkdir my-harbor-app
cd my-harbor-app
```

2. Initialize Composer.

```bash
composer init
```

3. Install Harbor.

```bash
composer require harbor/harbor
```

If your environment cannot resolve `harbor/harbor` from Packagist yet, use a VCS source:

```bash
composer config repositories.harbor vcs https://github.com/adrijan991/harbor
composer require harbor/harbor:dev-main
```

4. Serve Harbor documentation locally.

```bash
./vendor/bin/harbor-docs
```

5. Open the printed local URL (for example `http://localhost:8080`) and continue from the docs site.

## Documentation

Harbor documentation is intended to run locally:

```bash
./vendor/bin/harbor-docs
```

Search index rebuild command (only when docs content changes):

```bash
./vendor/bin/harbor-docs-index
```

## Contributing

1. Choose your workflow:
- If you do not have write access, fork the repo first, then clone your fork.
- If you have write access, work from the main repository.

```bash
# Fork flow (recommended for external contributors)
git clone https://github.com/<your-username>/harbor.git
cd harbor
git remote add upstream https://github.com/adrijan9/harbor.git

# Write-access flow
# git clone https://github.com/adrijan9/harbor.git
# cd harbor
```

2. Create a branch for your change.

```bash
git checkout -b feat/short-description
```

3. Make your changes, then run validation commands.

```bash
composer rector:check
composer test
./vendor/bin/php-cs-fixer fix
```

To auto-apply safe refactors before formatting, run `composer rector`.

4. If you changed documentation pages or docs routing, also regenerate docs artifacts.

```bash
./bin/harbor documentation/.router
./vendor/bin/harbor-docs-index
```

5. Push your branch and open a pull request against <code>adrijan9/harbor</code> <code>main</code> with:
- what changed and why
- exact verification commands and expected result
- route/config compatibility notes when applicable

This README is intentionally kept short. Detailed usage and updates continue in the documentation site.
