<?php

declare(strict_types=1);

function harbor_run_init(?string $site_name_from_argument = null): void
{
    $site_name = is_string($site_name_from_argument) ? trim($site_name_from_argument) : '';

    if ('' === $site_name) {
        fwrite(STDOUT, 'Site name (default: public): ');
        $input = fgets(STDIN);
        $site_name = trim(false === $input ? '' : $input);
    }

    if ('' === $site_name) {
        $site_name = 'public';
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
    $pages_path = $site_path.'/pages';

    if (file_exists($site_path) && ! is_dir($site_path)) {
        fwrite(STDERR, sprintf('Cannot create site in "%s": path exists and is not a directory.%s', $site_path, PHP_EOL));

        exit(1);
    }

    if (is_dir($site_path) && ! harbor_is_directory_empty($site_path)) {
        fwrite(STDERR, sprintf('Directory "%s" already exists and is not empty.%s', $site_path, PHP_EOL));

        exit(1);
    }

    harbor_create_directory($site_path);
    harbor_create_directory($pages_path);

    harbor_write_file($site_path.'/.htaccess', harbor_default_htaccess_template());
    harbor_write_file($site_path.'/config.php', harbor_default_config_template());
    harbor_write_file($site_path.'/index.php', harbor_default_site_index_template());
    harbor_write_file($site_path.'/.router', harbor_default_router_template());
    harbor_write_file($pages_path.'/index.php', harbor_default_home_page_template());
    harbor_write_file($site_path.'/not_found.php', harbor_default_not_found_template());
    harbor_write_file($site_path.'/routes.php', harbor_default_routes_template());
    harbor_ensure_parent_serve_script($working_directory);

    fwrite(STDOUT, sprintf('Site initialized: %s%s', $site_path, PHP_EOL));
    fwrite(STDOUT, sprintf('Next step: run "./bin/harbor %s" after editing .router.%s', $site_name, PHP_EOL));
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

function harbor_copy_template(string $source_path, string $destination_path): void
{
    if (! is_file($source_path)) {
        fwrite(STDERR, sprintf('Template not found: %s%s', $source_path, PHP_EOL));

        exit(1);
    }

    if (! copy($source_path, $destination_path)) {
        fwrite(STDERR, sprintf('Failed to copy template to: %s%s', $destination_path, PHP_EOL));

        exit(1);
    }
}

function harbor_write_file(string $path, string $contents): void
{
    $written = file_put_contents($path, $contents);
    if (false === $written) {
        fwrite(STDERR, sprintf('Failed to write file: %s%s', $path, PHP_EOL));

        exit(1);
    }
}

function harbor_ensure_parent_serve_script(string $working_directory): void
{
    $serve_script_path = $working_directory.'/serve.sh';
    if (file_exists($serve_script_path)) {
        return;
    }

    harbor_copy_template(__DIR__.'/../serve.sh', $serve_script_path);

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

function harbor_default_router_template(): string
{
    return <<<'ROUTER'
#route
  path: /
  method: GET
  name: home
  entry: pages/index.php
#endroute

ROUTER;
}

function harbor_default_home_page_template(): string
{
    return <<<'PHP'
<?php

echo 'Home page';

PHP;
}

function harbor_default_site_index_template(): string
{
    return <<<'PHP'
<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Harbor\Router\Router;

$config = require __DIR__.'/config.php';

if (! is_array($config)) {
    throw new RuntimeException('Site config.php must return an array.');
}

$GLOBALS['config'] = $config;


new Router(__DIR__.'/routes.php')->render();

PHP;
}

function harbor_default_config_template(): string
{
    return <<<'PHP'
<?php

declare(strict_types=1);

use Harbor\Environment;

return [
    'app_name' => 'Harbor Site',
    'environment' => Environment::LOCAL,
];

PHP;
}

function harbor_default_htaccess_template(): string
{
    return <<<'HTACCESS'
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L,QSA]

HTACCESS;
}

function harbor_default_not_found_template(): string
{
    return <<<'PHP'
<?php

http_response_code(404);

echo 'Page not found';

PHP;
}

function harbor_default_routes_template(): string
{
    $routes = [
        [
            'path' => '/',
            'method' => 'GET',
            'name' => 'home',
            'entry' => 'pages/index.php',
        ],
        [
            'method' => 'GET',
            'path' => '/404',
            'entry' => 'not_found.php',
        ],
    ];

    return '<?php return '.var_export($routes, true).';';
}
