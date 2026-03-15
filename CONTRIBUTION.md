# Contribution

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

5. Push your branch and open a pull request against `adrijan9/harbor` `main` with:
- what changed and why
- exact verification commands and expected result
- route/config compatibility notes when applicable
