<?php

declare(strict_types=1);

namespace Harbor\Tests\Router;

use Harbor\Router\Router;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private array $original_server = [];
    private array $original_env = [];
    private mixed $original_global_env = null;
    private bool $had_global_env = false;
    private mixed $original_route = null;
    private bool $had_route = false;
    private mixed $original_routes = null;
    private bool $had_routes = false;
    private mixed $original_route_assets_path = null;
    private bool $had_route_assets_path = false;
    private string $fixtures_dir;

    public function test_get_uri_returns_path_without_query_string(): void
    {
        $_SERVER['REQUEST_URI'] = '/posts/42?draft=1';

        $router = $this->create_router();

        self::assertSame('/posts/42', $router->get_uri());
    }

    public function test_current_matches_dynamic_route_and_extracts_query(): void
    {
        $_SERVER['REQUEST_URI'] = '/posts/42?draft=1&tag=php';

        $current_route = $this->create_router()->current();

        self::assertSame('/posts/$', $current_route['path']);
        self::assertSame(['42'], $current_route['segments']);
        self::assertSame(['draft' => '1', 'tag' => 'php'], $current_route['query']);
    }

    public function test_current_falls_back_to_not_found_route_when_method_does_not_match(): void
    {
        $_SERVER['REQUEST_URI'] = '/forms';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $current_route = $this->create_router('routes_with_method_restrictions.php')->current();

        self::assertSame('/404', $current_route['path']);
        self::assertSame([], $current_route['segments']);
        self::assertSame(405, $current_route['status']);
        self::assertSame(['POST'], $current_route['allowed_methods']);
    }

    public function test_current_matches_route_when_request_method_matches_case_insensitively(): void
    {
        $_SERVER['REQUEST_URI'] = '/forms';
        $_SERVER['REQUEST_METHOD'] = 'post';

        $current_route = $this->create_router('routes_with_method_restrictions.php')->current();

        self::assertSame('/forms', $current_route['path']);
        self::assertSame([], $current_route['segments']);
    }

    public function test_current_falls_back_to_not_found_route_when_no_match_exists(): void
    {
        $_SERVER['REQUEST_URI'] = '/missing?foo=bar';

        $current_route = $this->create_router()->current();

        self::assertSame('/404', $current_route['path']);
        self::assertSame([], $current_route['segments']);
        self::assertSame(['foo' => 'bar'], $current_route['query']);
    }

    public function test_render_includes_entry_and_extracted_variables(): void
    {
        $_SERVER['REQUEST_URI'] = '/';

        $router = $this->create_router();
        ob_start();

        try {
            $router->render(['name' => 'Ada']);
            $output = ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }

        self::assertSame('Hello Ada from /', $output);
    }

    public function test_render_throws_when_entry_is_invalid(): void
    {
        $_SERVER['REQUEST_URI'] = '/';

        $router = $this->create_router('invalid_entry_routes.php');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Current route entry is invalid.');

        $router->render();
    }

    public function test_render_throws_when_entry_file_cannot_be_found(): void
    {
        $_SERVER['REQUEST_URI'] = '/';

        $router = $this->create_router('missing_entry_routes.php');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Route entry "entries/missing.php" not found.');

        $router->render();
    }

    public function test_render_serves_asset_file_when_assets_are_configured(): void
    {
        $_SERVER['REQUEST_URI'] = '/assets/site.css';

        $router = $this->create_router('routes_with_assets.php');
        ob_start();

        try {
            $router->render();
            $output = ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }

        $expected_asset_content = file_get_contents($this->fixtures_dir.'/assets/site.css');
        self::assertIsString($expected_asset_content);
        self::assertSame($expected_asset_content, $output);
    }

    public function test_render_does_not_serve_assets_when_assets_are_not_configured(): void
    {
        $_SERVER['REQUEST_URI'] = '/assets/site.css';

        $router = $this->create_router('routes.php');
        ob_start();

        try {
            $router->render();
            $output = ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }

        self::assertSame('Not Found', $output);
    }

    public function test_render_returns_method_not_allowed_when_path_exists_with_different_method(): void
    {
        $_SERVER['REQUEST_URI'] = '/forms';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $router = $this->create_router('routes_with_method_restrictions.php');
        http_response_code(200);
        ob_start();

        try {
            $router->render();
            $output = ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }

        self::assertSame('Method Not Allowed', $output);
        self::assertSame(405, http_response_code());
        http_response_code(200);
    }

    public function test_render_returns_json_for_method_not_allowed_when_accept_header_requests_json(): void
    {
        $_SERVER['REQUEST_URI'] = '/forms';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_ACCEPT'] = 'application/json';

        $router = $this->create_router('routes_with_method_restrictions.php');
        http_response_code(200);
        ob_start();

        try {
            $router->render();
            $output = ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }

        self::assertSame('{"message":"Method Not Allowed","status":405,"allowed_methods":["POST"]}', $output);
        self::assertSame(405, http_response_code());
        http_response_code(200);
    }

    public function test_render_returns_json_for_method_not_allowed_when_accept_header_uses_json_suffix(): void
    {
        $_SERVER['REQUEST_URI'] = '/forms';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_ACCEPT'] = 'application/problem+json';

        $router = $this->create_router('routes_with_method_restrictions.php');
        http_response_code(200);
        ob_start();

        try {
            $router->render();
            $output = ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }

        self::assertSame('{"message":"Method Not Allowed","status":405,"allowed_methods":["POST"]}', $output);
        self::assertSame(405, http_response_code());
        http_response_code(200);
    }

    public function test_render_blocks_asset_directory_traversal_attempts(): void
    {
        $_SERVER['REQUEST_URI'] = '/assets/../config.php';

        $router = $this->create_router('routes_with_assets.php');
        ob_start();

        try {
            $router->render();
            $output = ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }

        self::assertSame('Not Found', $output);
    }

    public function test_constructor_loads_config_file_into_env_as_global_values(): void
    {
        $_SERVER['REQUEST_URI'] = '/';

        $this->create_router(config_file: 'config.php');

        self::assertSame('fixture-site', $_ENV['app_name'] ?? null);
        self::assertSame('true', $_ENV['feature_enabled'] ?? null);
        self::assertSame('3306', $_ENV['db']['port'] ?? null);
        self::assertSame('fixture-site', \Harbor\Config\config('app_name'));
        self::assertSame('true', \Harbor\Config\config('feature_enabled'));
        self::assertSame('3306', \Harbor\Config\config('db.port'));
    }

    #[Before]
    protected function prepare_globals(): void
    {
        $this->original_server = $_SERVER;
        $this->original_env = is_array($_ENV) ? $_ENV : [];
        $this->had_global_env = array_key_exists('_ENV', $GLOBALS);
        $this->original_global_env = $this->had_global_env ? $GLOBALS['_ENV'] : null;
        $this->had_route = array_key_exists('route', $GLOBALS);
        $this->original_route = $this->had_route ? $GLOBALS['route'] : null;
        $this->had_routes = array_key_exists('routes', $GLOBALS);
        $this->original_routes = $this->had_routes ? $GLOBALS['routes'] : null;
        $this->had_route_assets_path = array_key_exists('route_assets_path', $GLOBALS);
        $this->original_route_assets_path = $this->had_route_assets_path ? $GLOBALS['route_assets_path'] : null;
        $this->fixtures_dir = dirname(__DIR__).'/Fixtures/router';

        $_SERVER = [];
        $_ENV = [];
        $GLOBALS['_ENV'] = $_ENV;
        unset($GLOBALS['route']);
        unset($GLOBALS['route_assets_path']);
    }

    #[After]
    protected function restore_globals(): void
    {
        $_SERVER = $this->original_server;
        $_ENV = $this->original_env;

        if ($this->had_route) {
            $GLOBALS['route'] = $this->original_route;
        } else {
            unset($GLOBALS['route']);
        }

        if ($this->had_routes) {
            $GLOBALS['routes'] = $this->original_routes;
        } else {
            unset($GLOBALS['routes']);
        }

        if ($this->had_route_assets_path) {
            $GLOBALS['route_assets_path'] = $this->original_route_assets_path;
        } else {
            unset($GLOBALS['route_assets_path']);
        }

        if ($this->had_global_env) {
            $GLOBALS['_ENV'] = $this->original_global_env;
        } else {
            unset($GLOBALS['_ENV']);
        }
    }

    private function create_router(string $routes_file = 'routes.php', string $config_file = 'config.php'): Router
    {
        $config_path = $this->fixtures_dir.'/'.$config_file;

        return new Router($this->fixtures_dir.'/'.$routes_file, $config_path);
    }
}
