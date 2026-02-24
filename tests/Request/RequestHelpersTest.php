<?php

declare(strict_types=1);

namespace Harbor\Tests\Request;

require_once dirname(__DIR__, 2).'/src/Support/value.php';

use Harbor\HelperLoader;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

use function Harbor\Request\request;
use function Harbor\Request\request_body;
use function Harbor\Request\request_body_all;
use function Harbor\Request\request_body_arr;
use function Harbor\Request\request_body_bool;
use function Harbor\Request\request_body_count;
use function Harbor\Request\request_body_exists;
use function Harbor\Request\request_body_float;
use function Harbor\Request\request_body_int;
use function Harbor\Request\request_body_json;
use function Harbor\Request\request_body_obj;
use function Harbor\Request\request_body_str;
use function Harbor\Request\request_cookie;
use function Harbor\Request\request_cookie_exists;
use function Harbor\Request\request_files;
use function Harbor\Request\request_full_url;
use function Harbor\Request\request_has_file;
use function Harbor\Request\request_header;
use function Harbor\Request\request_header_arr;
use function Harbor\Request\request_header_bool;
use function Harbor\Request\request_header_exists;
use function Harbor\Request\request_header_float;
use function Harbor\Request\request_header_int;
use function Harbor\Request\request_header_json;
use function Harbor\Request\request_header_obj;
use function Harbor\Request\request_host;
use function Harbor\Request\request_input_str;
use function Harbor\Request\request_ip;
use function Harbor\Request\request_is_ajax;
use function Harbor\Request\request_is_json;
use function Harbor\Request\request_is_post;
use function Harbor\Request\request_is_secure;
use function Harbor\Request\request_method;
use function Harbor\Request\request_only;
use function Harbor\Request\request_path;
use function Harbor\Request\request_port;
use function Harbor\Request\request_query_string;
use function Harbor\Request\request_referer;
use function Harbor\Request\request_scheme;
use function Harbor\Request\request_server;
use function Harbor\Request\request_uri;
use function Harbor\Request\request_url;
use function Harbor\Request\request_user_agent;
use function Harbor\Request\request_except;
use function Harbor\Support\harbor_is_null;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class RequestHelpersTest extends TestCase
{
    public function test_request_metadata_helpers_read_server_state(): void
    {
        $this->boot_request_helper(
            server: [
                'REQUEST_METHOD' => 'post',
                'REQUEST_URI' => '/api/items/15?page=2&active=1',
                'HTTP_HOST' => 'example.test:8080',
                'SERVER_PORT' => '8080',
                'REMOTE_ADDR' => '192.0.2.10',
                'HTTP_X_FORWARDED_FOR' => '198.51.100.8, 10.0.0.1',
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
                'HTTP_USER_AGENT' => 'phpunit-agent',
                'HTTP_REFERER' => 'https://ref.example/path',
                'CONTENT_TYPE' => 'application/json; charset=utf-8',
            ],
        );

        self::assertSame('POST', request_method());
        self::assertTrue(request_is_post());
        self::assertSame('/api/items/15?page=2&active=1', request_uri());
        self::assertSame('/api/items/15', request_path());
        self::assertSame('page=2&active=1', request_query_string());
        self::assertSame('http', request_scheme());
        self::assertSame('example.test', request_host());
        self::assertSame(8080, request_port());
        self::assertSame('http://example.test:8080/api/items/15', request_url());
        self::assertSame('http://example.test:8080/api/items/15?page=2&active=1', request_full_url());
        self::assertSame('198.51.100.8', request_ip());
        self::assertSame('phpunit-agent', request_user_agent());
        self::assertSame('https://ref.example/path', request_referer());
        self::assertTrue(request_is_ajax());
        self::assertTrue(request_is_json());
        self::assertFalse(request_is_secure());
    }

    public function test_request_header_helpers_cast_values(): void
    {
        $this->boot_request_helper(
            server: [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/',
                'HTTP_X_RATE_LIMIT' => '15',
                'HTTP_X_WEIGHT' => '3.5',
                'HTTP_X_ENABLED' => 'true',
                'HTTP_X_TAGS' => 'php, tests',
                'HTTP_X_META' => rawurlencode('{"name":"Ada"}'),
                'HTTP_X_OBJECT' => rawurlencode('{"team":"core"}'),
                'CONTENT_LENGTH' => '120',
                'HTTP_X_FORWARDED_PROTO' => 'https',
            ],
        );

        self::assertTrue(request_header_exists('x-rate-limit'));
        self::assertSame(15, request_header_int('x-rate-limit'));
        self::assertSame(3.5, request_header_float('x-weight'));
        self::assertTrue(request_header_bool('x-enabled'));
        self::assertSame(['php', 'tests'], request_header_arr('x-tags'));
        self::assertSame(['name' => 'Ada'], request_header_json('x-meta'));
        self::assertSame('120', request_header('content-length'));
        self::assertTrue(request_is_secure());

        $header_object = request_header_obj('x-object');
        self::assertInstanceOf(\stdClass::class, $header_object);
        self::assertSame('core', $header_object->team);
    }

    public function test_request_body_and_input_helpers_from_post_data(): void
    {
        $this->boot_request_helper(
            server: [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/submit',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            ],
            post: [
                'name' => 'Ada',
                'age' => '27',
                'ratio' => '2.75',
                'enabled' => 'true',
                'tags' => 'php,framework',
                'meta' => '{"id":9}',
                'filters' => ['owner' => ['id' => '44']],
            ],
        );

        self::assertSame('Ada', request_body_str('name'));
        self::assertSame('44', request_body('filters.owner.id'));
        self::assertSame(27, request_body_int('age'));
        self::assertSame(2.75, request_body_float('ratio'));
        self::assertTrue(request_body_bool('enabled'));
        self::assertSame(['php', 'framework'], request_body_arr('tags'));
        self::assertSame(['id' => 9], request_body_json('meta'));
        self::assertTrue(request_body_exists('filters.owner.id'));
        self::assertSame('Ada', request_input_str('name'));
        self::assertSame(7, request_body_count());

        $body_object = request_body_obj('meta');
        self::assertInstanceOf(\stdClass::class, $body_object);
        self::assertSame(9, $body_object->id);
        self::assertSame('Ada', request_body_all()['name']);
    }

    public function test_request_only_and_except_helpers_filter_input_data(): void
    {
        $this->boot_request_helper(
            server: [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/submit',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            ],
            post: [
                'first' => 'Ada',
                'second' => 'Lovelace',
                'third' => 'Framework',
                'filters' => ['owner' => ['id' => '44']],
            ],
        );

        self::assertSame(
            [
                'first' => 'Ada',
                'second' => 'Lovelace',
                'filters.owner.id' => '44',
            ],
            request_only('first', 'second', 'filters.owner.id', 'missing')
        );

        self::assertSame(
            [
                'first' => 'Ada',
                'third' => 'Framework',
                'filters' => ['owner' => ['id' => '44']],
            ],
            request_except('second', 'missing')
        );
    }

    public function test_request_cookie_file_server_and_snapshot_helpers(): void
    {
        $route = [
            'path' => '/dashboard',
            'segments' => ['dashboard'],
            'query' => ['tab' => 'profile'],
        ];

        $this->boot_request_helper(
            server: [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/dashboard?tab=profile',
                'HTTP_HOST' => 'app.test',
                'SERVER_NAME' => 'app.test',
                'SERVER_PORT' => '80',
                'REMOTE_ADDR' => '203.0.113.10',
            ],
            cookie: [
                'session' => 'abc123',
            ],
            files: [
                'avatar' => [
                    'name' => 'avatar.png',
                    'type' => 'image/png',
                    'tmp_name' => '/tmp/avatar.png',
                    'error' => 0,
                    'size' => 120,
                ],
            ],
            route: $route,
        );

        self::assertSame('abc123', request_cookie('session'));
        self::assertTrue(request_cookie_exists('session'));
        self::assertTrue(request_has_file('avatar'));
        self::assertSame('avatar.png', request_files('avatar.name'));
        self::assertSame('GET', request_server('REQUEST_METHOD'));

        $snapshot = request();
        self::assertSame($route, $snapshot['route']);
        self::assertSame('abc123', $snapshot['cookies']['session']);
        self::assertSame('avatar.png', $snapshot['files']['avatar']['name']);
        self::assertSame('http://app.test/dashboard?tab=profile', $snapshot['full_url']);
    }

    private function boot_request_helper(
        array $server,
        array $post = [],
        array $cookie = [],
        array $files = [],
        mixed $route = null,
    ): void {
        $_SERVER = $server;
        $_POST = $post;
        $_COOKIE = $cookie;
        $_FILES = $files;

        if (harbor_is_null($route)) {
            unset($GLOBALS['route']);
        } else {
            $GLOBALS['route'] = $route;
        }

        unset($GLOBALS['request']);
        HelperLoader::load('request');
    }
}
