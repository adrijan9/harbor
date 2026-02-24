<?php

declare(strict_types=1);

namespace Harbor\Tests\Router;

use Harbor\HelperLoader;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\TestCase;

use function Harbor\Router\route_queries_count;
use function Harbor\Router\route_query;
use function Harbor\Router\route_query_arr;
use function Harbor\Router\route_query_bool;
use function Harbor\Router\route_query_exists;
use function Harbor\Router\route_query_except;
use function Harbor\Router\route_query_int;
use function Harbor\Router\route_query_json;
use function Harbor\Router\route_query_obj;
use function Harbor\Router\route_query_only;
use function Harbor\Router\route_query_str;
use function Harbor\Router\route_segment;
use function Harbor\Router\route_segment_arr;
use function Harbor\Router\route_segment_bool;
use function Harbor\Router\route_segment_exists;
use function Harbor\Router\route_segment_int;
use function Harbor\Router\route_segment_json;
use function Harbor\Router\route_segment_obj;
use function Harbor\Router\route_segments_count;
use function Harbor\Router\route as route_path;
use function Harbor\Router\route_exists;
use function Harbor\Router\route_name_is;

final class RouteHelpersTest extends TestCase
{
    private bool $had_route = false;
    private mixed $original_route = null;
    private bool $had_routes = false;
    private mixed $original_routes = null;

    #[BeforeClass]
    public static function load_route_helpers(): void
    {
        HelperLoader::load('route');
    }

    public function test_segment_helpers_read_and_cast_route_segments(): void
    {
        self::assertSame('42', route_segment(0));
        self::assertSame(42, route_segment_int(0));
        self::assertTrue(route_segment_bool(1));
        self::assertSame(['php', 'framework', 'tests'], route_segment_arr(2));
        self::assertSame(['id' => 15, 'enabled' => true], route_segment_json(3));

        $segment_object = route_segment_obj(4);
        self::assertInstanceOf(\stdClass::class, $segment_object);
        self::assertSame(1, $segment_object->a);
    }

    public function test_segment_helpers_return_defaults_for_missing_indexes(): void
    {
        self::assertFalse(route_segment_exists(50));
        self::assertSame('fallback', route_segment(50, 'fallback'));
        self::assertSame(5, route_segment_int(50, 5));
        self::assertSame(6, route_segments_count());
    }

    public function test_query_helpers_support_nested_keys_and_type_casts(): void
    {
        self::assertSame('9', route_query('filters.author.id'));
        self::assertSame(7, route_query_int('page'));
        self::assertTrue(route_query_bool('enabled'));
        self::assertSame(['php', 'tests'], route_query_arr('tags'));
        self::assertSame(['sort' => 'desc', 'limit' => 10], route_query_json('meta'));

        $payload = route_query_obj('payload');
        self::assertInstanceOf(\stdClass::class, $payload);
        self::assertSame('Ada', $payload->name);
    }

    public function test_query_helpers_expose_presence_and_defaults(): void
    {
        self::assertTrue(route_query_exists('filters.author.id'));
        self::assertSame('fallback', route_query_str('missing', 'fallback'));
        self::assertSame([], route_query_arr('missing', []));
        self::assertSame($GLOBALS['route']['query'], route_query());
        self::assertSame(6, route_queries_count());
    }

    public function test_query_only_and_except_helpers_filter_query_data(): void
    {
        self::assertSame(
            [
                'page' => '7',
                'enabled' => '1',
                'filters.author.id' => '9',
            ],
            route_query_only('page', 'enabled', 'filters.author.id', 'missing')
        );

        self::assertSame(
            [
                'page' => '7',
                'tags' => 'php,tests',
                'filters' => ['author' => ['id' => '9']],
                'payload' => rawurlencode('{"name":"Ada"}'),
            ],
            route_query_except('enabled', 'meta', 'missing')
        );
    }

    public function test_named_route_helpers_build_paths_and_check_presence(): void
    {
        self::assertTrue(route_exists('posts.show'));
        self::assertTrue(route_exists('teams.members.show'));
        self::assertFalse(route_exists('missing.route'));
        self::assertTrue(route_name_is('posts.show'));
        self::assertFalse(route_name_is('home'));

        self::assertSame('/posts/42', route_path('posts.show', [42]));
        self::assertSame('/teams/9/members/3', route_path('teams.members.show', [9, 3]));
        self::assertSame('/search/hello%20world', route_path('search.query', ['hello world']));
        self::assertSame('/', route_path('home'));
    }

    public function test_named_route_helper_throws_when_route_is_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Route "unknown.route" is not defined.');

        route_path('unknown.route');
    }

    public function test_named_route_helper_throws_when_parameters_do_not_match_placeholders(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing parameter at index 1 for route "teams.members.show".');

        route_path('teams.members.show', [9]);
    }

    public function test_named_route_helper_throws_when_too_many_parameters_are_provided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Too many parameters for route "posts.show". Expected 1, got 2.');

        route_path('posts.show', [42, 55]);
    }

    #[Before]
    protected function prepare_route_globals(): void
    {
        $this->had_route = array_key_exists('route', $GLOBALS);
        $this->original_route = $this->had_route ? $GLOBALS['route'] : null;
        $this->had_routes = array_key_exists('routes', $GLOBALS);
        $this->original_routes = $this->had_routes ? $GLOBALS['routes'] : null;

        $GLOBALS['route'] = [
            'name' => 'posts.show',
            'path' => '/posts/$',
            'segments' => [
                '42',
                'true',
                'php,framework,tests',
                rawurlencode('{"id":15,"enabled":true}'),
                ['a' => 1],
                'not-json',
            ],
            'query' => [
                'page' => '7',
                'enabled' => '1',
                'tags' => 'php,tests',
                'filters' => ['author' => ['id' => '9']],
                'meta' => rawurlencode('{"sort":"desc","limit":10}'),
                'payload' => rawurlencode('{"name":"Ada"}'),
            ],
        ];

        $GLOBALS['routes'] = [
            [
                'method' => 'GET',
                'path' => '/',
                'name' => 'home',
                'entry' => 'pages/index.php',
            ],
            [
                'method' => 'GET',
                'path' => '/posts/$',
                'name' => 'posts.show',
                'entry' => 'pages/post.php',
            ],
            [
                'method' => 'GET',
                'path' => '/teams/$/members/$',
                'name' => 'teams.members.show',
                'entry' => 'pages/member.php',
            ],
            [
                'method' => 'GET',
                'path' => '/search/$',
                'name' => 'search.query',
                'entry' => 'pages/search.php',
            ],
        ];
    }

    #[After]
    protected function restore_route_globals(): void
    {
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
    }
}
