<?php

declare(strict_types=1);

namespace PhpFramework\Tests\Router;

use PhpFramework\HelperLoader;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\TestCase;

use function PhpFramework\Router\route_queries_count;
use function PhpFramework\Router\route_query;
use function PhpFramework\Router\route_query_arr;
use function PhpFramework\Router\route_query_bool;
use function PhpFramework\Router\route_query_exists;
use function PhpFramework\Router\route_query_int;
use function PhpFramework\Router\route_query_json;
use function PhpFramework\Router\route_query_obj;
use function PhpFramework\Router\route_query_str;
use function PhpFramework\Router\route_segment;
use function PhpFramework\Router\route_segment_arr;
use function PhpFramework\Router\route_segment_bool;
use function PhpFramework\Router\route_segment_exists;
use function PhpFramework\Router\route_segment_int;
use function PhpFramework\Router\route_segment_json;
use function PhpFramework\Router\route_segment_obj;
use function PhpFramework\Router\route_segments_count;

final class RouteHelpersTest extends TestCase
{
    private bool $had_route = false;
    private mixed $original_route = null;

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

    #[Before]
    protected function prepare_route_globals(): void
    {
        $this->had_route = array_key_exists('route', $GLOBALS);
        $this->original_route = $this->had_route ? $GLOBALS['route'] : null;

        $GLOBALS['route'] = [
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
    }

    #[After]
    protected function restore_route_globals(): void
    {
        if ($this->had_route) {
            $GLOBALS['route'] = $this->original_route;

            return;
        }

        unset($GLOBALS['route']);
    }
}
