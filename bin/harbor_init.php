<?php

declare(strict_types=1);

require_once __DIR__.'/../src/Support/value.php';

use function Harbor\Support\harbor_is_blank;

function harbor_run_init(?string $site_name_from_argument = null): void
{
    $site_name = is_string($site_name_from_argument) ? trim($site_name_from_argument) : '';

    if (harbor_is_blank($site_name)) {
        fwrite(STDOUT, 'Site name (default: example.site): ');
        $input = fgets(STDIN);
        $site_name = trim(false === $input ? '' : $input);
    }

    if (harbor_is_blank($site_name)) {
        $site_name = 'example.site';
    }

    if (! preg_match('/^[A-Za-z0-9._-]+$/', $site_name)) {
        fwrite(STDERR, 'Invalid site name. Use only letters, numbers, ".", "_" and "-".'.PHP_EOL);

        exit(1);
    }

    $working_directory = getcwd();
    if (false === $working_directory) {
        fwrite(STDERR, 'Unable to resolve current working directory.'.PHP_EOL);

        exit(1);
    }

    $site_path = $working_directory.'/'.$site_name;

    if (file_exists($site_path) && ! is_dir($site_path)) {
        fwrite(STDERR, sprintf('Cannot create site in "%s": path exists and is not a directory.%s', $site_path, PHP_EOL));

        exit(1);
    }

    if (is_dir($site_path) && ! harbor_is_directory_empty($site_path)) {
        fwrite(STDERR, sprintf('Directory "%s" already exists and is not empty.%s', $site_path, PHP_EOL));

        exit(1);
    }

    $template_directory_path = harbor_resolve_site_template_directory_path();
    harbor_copy_directory($template_directory_path, $site_path);
    harbor_ensure_parent_serve_script($working_directory);

    fwrite(STDOUT, sprintf('Site initialized: %s%s', $site_path, PHP_EOL));
    fwrite(STDOUT, sprintf('Next step: run "./bin/harbor %s" after editing .router.%s', $site_name, PHP_EOL));
}

function harbor_resolve_site_template_directory_path(): string
{
    $template_directory_path = realpath(__DIR__.'/../templates/site');

    if (false === $template_directory_path || ! is_dir($template_directory_path)) {
        fwrite(STDERR, sprintf('Site template directory not found: %s%s', __DIR__.'/../templates/site', PHP_EOL));

        exit(1);
    }

    return $template_directory_path;
}

function harbor_create_directory(string $path): void
{
    if (is_dir($path)) {
        return;
    }

    if (! mkdir($path, 0o777, true) && ! is_dir($path)) {
        fwrite(STDERR, sprintf('Failed to create directory: %s%s', $path, PHP_EOL));

        exit(1);
    }
}

function harbor_copy_directory(string $source_directory_path, string $destination_directory_path): void
{
    if (! is_dir($source_directory_path)) {
        fwrite(STDERR, sprintf('Source template directory not found: %s%s', $source_directory_path, PHP_EOL));

        exit(1);
    }

    harbor_create_directory($destination_directory_path);

    $entries = scandir($source_directory_path);
    if (false === $entries) {
        fwrite(STDERR, sprintf('Failed to read template directory: %s%s', $source_directory_path, PHP_EOL));

        exit(1);
    }

    foreach ($entries as $entry) {
        if ('.' === $entry || '..' === $entry) {
            continue;
        }

        $source_entry_path = $source_directory_path.'/'.$entry;
        $destination_entry_path = $destination_directory_path.'/'.$entry;

        if (is_dir($source_entry_path)) {
            harbor_copy_directory($source_entry_path, $destination_entry_path);

            continue;
        }

        harbor_copy_template_file($source_entry_path, $destination_entry_path);
    }
}

function harbor_copy_template_file(string $source_path, string $destination_path): void
{
    if (! is_file($source_path)) {
        fwrite(STDERR, sprintf('Template file not found: %s%s', $source_path, PHP_EOL));

        exit(1);
    }

    if (! copy($source_path, $destination_path)) {
        fwrite(STDERR, sprintf('Failed to copy template file to: %s%s', $destination_path, PHP_EOL));

        exit(1);
    }
}

function harbor_ensure_parent_serve_script(string $working_directory): void
{
    $serve_script_path = $working_directory.'/serve.sh';
    if (file_exists($serve_script_path)) {
        return;
    }

    harbor_copy_template_file(__DIR__.'/../serve.sh', $serve_script_path);

    if (! chmod($serve_script_path, 0o755)) {
        fwrite(STDERR, sprintf('Warning: Failed to set executable permissions for: %s%s', $serve_script_path, PHP_EOL));
    }

    fwrite(STDOUT, sprintf('Serve script created: %s%s', $serve_script_path, PHP_EOL));
}

function harbor_is_directory_empty(string $directory): bool
{
    $items = scandir($directory);
    if (false === $items) {
        return false;
    }

    return 2 === count($items);
}
