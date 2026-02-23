<?php

declare(strict_types=1);

namespace Harbor\Tests\Bin;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2).'/bin/harbor_compile.php';

final class HarborCompileTest extends TestCase
{
    /** @var array<int, string> */
    private array $workspace_paths = [];

    public function test_pre_process_routes_file_expands_nested_include_files(): void
    {
        $workspace_path = $this->create_workspace();

        $shared_routes_path = $workspace_path.'/shared.router';
        $extra_routes_path = $workspace_path.'/extra.router';
        $main_routes_path = $workspace_path.'/.router';

        file_put_contents($shared_routes_path, <<<'ROUTER'
<route>
  path: /posts/$
  method: GET
  entry: pages/post.php
</route>
ROUTER
        );

        file_put_contents($extra_routes_path, <<<'ROUTER'
#include "shared.router"
<route>
  path: /health
  method: GET
  entry: pages/health.php
</route>
ROUTER
        );

        file_put_contents($main_routes_path, <<<'ROUTER'
<route>
  path: /
  method: GET
  entry: pages/home.php
</route>

#include "extra.router"
ROUTER
        );

        $preprocessed = harbor_pre_process_routes_file($main_routes_path);

        self::assertStringNotContainsString('#include', $preprocessed);
        self::assertStringContainsString('path: /posts/$', $preprocessed);
        self::assertStringContainsString('path: /health', $preprocessed);

        $compiled_routes = harbor_compile_routes_from_content($preprocessed);

        self::assertCount(4, $compiled_routes);
        self::assertSame('/', $compiled_routes[0]['path']);
        self::assertSame('/posts/$', $compiled_routes[1]['path']);
        self::assertSame('/health', $compiled_routes[2]['path']);
        self::assertSame('/404', $compiled_routes[3]['path']);
    }

    public function test_pre_process_routes_file_supports_absolute_include_paths(): void
    {
        $workspace_path = $this->create_workspace();

        $included_routes_path = $workspace_path.'/included.router';
        $main_routes_path = $workspace_path.'/.router';

        file_put_contents($included_routes_path, <<<'ROUTER'
<route>
  path: /about
  method: GET
  entry: pages/about.php
</route>
ROUTER
        );

        file_put_contents($main_routes_path, sprintf('#include "%s"', $included_routes_path));

        $preprocessed = harbor_pre_process_routes_file($main_routes_path);
        $compiled_routes = harbor_compile_routes_from_content($preprocessed);

        self::assertSame('/about', $compiled_routes[0]['path']);
        self::assertSame('/404', $compiled_routes[1]['path']);
    }

    public function test_compile_router_from_content_extracts_assets_path_and_routes(): void
    {
        $compiled_router = harbor_compile_router_from_content(<<<'ROUTER'
<assets>/assets</assets>
<route>
  path: /
  method: GET
  entry: pages/home.php
</route>
ROUTER
        );

        self::assertSame('/assets', $compiled_router['assets']);
        self::assertIsArray($compiled_router['routes']);
        self::assertSame('/', $compiled_router['routes'][0]['path']);
        self::assertSame('/404', $compiled_router['routes'][1]['path']);
    }

    public function test_run_compile_writes_assets_configuration_to_routes_file(): void
    {
        $workspace_path = $this->create_workspace();
        $router_path = $workspace_path.'/.router';

        file_put_contents($router_path, <<<'ROUTER'
<assets>/assets</assets>
<route>
  path: /
  method: GET
  entry: pages/home.php
</route>
ROUTER
        );

        harbor_run_compile($router_path);

        $compiled_router = require $workspace_path.'/routes.php';

        self::assertIsArray($compiled_router);
        self::assertSame('/assets', $compiled_router['assets'] ?? null);
        self::assertIsArray($compiled_router['routes'] ?? null);
        self::assertSame('/', $compiled_router['routes'][0]['path'] ?? null);
        self::assertSame('/404', $compiled_router['routes'][1]['path'] ?? null);
    }

    public function test_run_compile_from_non_root_working_directory(): void
    {
        $workspace_path = $this->create_workspace();
        $router_path = $workspace_path.'/.router';

        file_put_contents($router_path, <<<'ROUTER'
<route>
  path: /
  method: GET
  entry: pages/home.php
</route>
ROUTER
        );

        $original_working_directory = getcwd();
        if (false === $original_working_directory) {
            throw new \RuntimeException('Failed to get current working directory.');
        }

        chdir($workspace_path);

        try {
            harbor_run_compile('./.router');
        } finally {
            chdir($original_working_directory);
        }

        $compiled_router = require $workspace_path.'/routes.php';

        self::assertIsArray($compiled_router);
        self::assertIsArray($compiled_router['routes'] ?? null);
        self::assertSame('/', $compiled_router['routes'][0]['path'] ?? null);
    }

    #[After]
    protected function cleanup_workspaces(): void
    {
        foreach ($this->workspace_paths as $workspace_path) {
            if (! is_dir($workspace_path)) {
                continue;
            }

            $this->delete_directory_tree($workspace_path);
        }

        $this->workspace_paths = [];
    }

    private function create_workspace(): string
    {
        $workspace_path = sys_get_temp_dir().'/harbor_compile_'.bin2hex(random_bytes(8));

        if (! mkdir($workspace_path, 0o777, true) && ! is_dir($workspace_path)) {
            throw new \RuntimeException(sprintf('Failed to create test workspace "%s".', $workspace_path));
        }

        $this->workspace_paths[] = $workspace_path;

        return $workspace_path;
    }

    private function delete_directory_tree(string $directory_path): void
    {
        $entries = scandir($directory_path);
        if (false === $entries) {
            return;
        }

        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }

            $entry_path = $directory_path.'/'.$entry;
            if (is_dir($entry_path)) {
                $this->delete_directory_tree($entry_path);

                continue;
            }

            unlink($entry_path);
        }

        rmdir($directory_path);
    }
}
