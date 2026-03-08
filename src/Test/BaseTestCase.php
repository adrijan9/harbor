<?php

declare(strict_types=1);

namespace Harbor\Test;

use Harbor\Router\Router;
use ReflectionClass;
use RuntimeException;

abstract class BaseTestCase extends \PHPUnit\Framework\TestCase
{
    private array $original_server = [];
    private array $original_get = [];
    private array $original_post = [];
    private array $original_cookie = [];
    private array $original_files = [];
    private array $original_request = [];
    private ?string $resolved_site_root_path = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->original_server = $_SERVER;
        $this->original_get = $_GET;
        $this->original_post = $_POST;
        $this->original_cookie = $_COOKIE;
        $this->original_files = $_FILES;
        $this->original_request = $_REQUEST;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->original_server;
        $_GET = $this->original_get;
        $_POST = $this->original_post;
        $_COOKIE = $this->original_cookie;
        $_FILES = $this->original_files;
        $_REQUEST = $this->original_request;
        http_response_code(200);

        parent::tearDown();
    }

    protected function site_path(string $path = ''): string
    {
        $site_root = $this->site_root_path();
        $trimmed_path = trim($path, '/\\');

        if ('' === $trimmed_path) {
            return $site_root;
        }

        return $site_root.'/'.$trimmed_path;
    }

    /**
     * @return array{status: int, content: string}
     */
    protected function get(string $uri): array
    {
        return $this->request('GET', $uri);
    }

    /**
     * @return array{status: int, content: string}
     */
    protected function request(string $method, string $uri): array
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $query = parse_url($uri, PHP_URL_QUERY);
        $normalized_path = is_string($path) && '' !== $path ? $path : '/';
        $normalized_uri = $normalized_path.(is_string($query) && '' !== $query ? '?'.$query : '');
        $query_data = [];

        if (is_string($query) && '' !== $query) {
            parse_str($query, $query_data);
        }

        $_SERVER = array_merge($this->original_server, [
            'REQUEST_METHOD' => strtoupper($method),
            'REQUEST_URI' => $normalized_uri,
        ]);
        $_GET = is_array($query_data) ? $query_data : [];
        $_POST = [];
        $_COOKIE = [];
        $_FILES = [];
        $_REQUEST = $_GET;

        http_response_code(200);

        ob_start();
        (new Router(
            $this->site_path('public/routes.php'),
            $this->site_path('global.php'),
        ))->render();
        $content = ob_get_clean();

        return [
            'status' => http_response_code(),
            'content' => is_string($content) ? $content : '',
        ];
    }

    protected function site_root_path(): string
    {
        if (is_string($this->resolved_site_root_path) && '' !== trim($this->resolved_site_root_path)) {
            return $this->resolved_site_root_path;
        }

        $reflection_class = new ReflectionClass(static::class);
        $test_file_path = $reflection_class->getFileName();

        if (is_string($test_file_path) && '' !== trim($test_file_path)) {
            $search_path = dirname($test_file_path);

            for ($level = 0; $level < 8; $level++) {
                if (is_file($search_path.'/.router')) {
                    $this->resolved_site_root_path = $search_path;

                    return $search_path;
                }

                $parent_search_path = dirname($search_path);
                if ($parent_search_path === $search_path) {
                    break;
                }

                $search_path = $parent_search_path;
            }
        }

        $working_directory = getcwd();
        if (false !== $working_directory && '' !== trim($working_directory) && is_file($working_directory.'/.router')) {
            $this->resolved_site_root_path = $working_directory;

            return $working_directory;
        }

        throw new RuntimeException('Unable to resolve site root path for tests.');
    }
}
