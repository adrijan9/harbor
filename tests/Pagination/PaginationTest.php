<?php

declare(strict_types=1);

namespace Harbor\Tests\Pagination;

use Harbor\Database\QueryBuilder\QueryBuilder;
use Harbor\HelperLoader;
use Harbor\Pagination\Pagination;
use Harbor\Pagination\PaginationOptionsBag;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\TestCase;

use function Harbor\Database\db_execute;
use function Harbor\Database\db_sqlite_connect;
use function Harbor\Pagination\pagination_paginate;

/**
 * Class UsersPaginationStub.
 */
final class UsersPaginationStub extends Pagination
{
    public function __construct(
        private readonly string $status = 'active',
    ) {}

    protected function base_query(): QueryBuilder
    {
        return QueryBuilder::select()
            ->from('users')
            ->columns('id', 'email', 'status')
            ->where('status', '=', $this->status)
            ->order_by('id', 'asc')
        ;
    }
}

/**
 * Class InvalidLimitPaginationStub.
 */
final class InvalidLimitPaginationStub extends Pagination
{
    protected function base_query(): QueryBuilder
    {
        return QueryBuilder::select()
            ->from('users')
            ->columns('id')
            ->limit(5)
        ;
    }
}

/**
 * Class PaginationTest.
 */
final class PaginationTest extends TestCase
{
    private string $workspace_path;
    private string $primary_sqlite_database_path;
    private string $secondary_sqlite_database_path;

    #[BeforeClass]
    public static function load_helpers(): void
    {
        HelperLoader::load('db', 'pagination');
    }

    public function test_class_mode_with_set_connection_returns_payload(): void
    {
        $connection = db_sqlite_connect($this->primary_sqlite_database_path);
        $this->seed_users($connection, [
            ['email' => 'a@example.com', 'status' => 'active'],
            ['email' => 'b@example.com', 'status' => 'inactive'],
            ['email' => 'c@example.com', 'status' => 'active'],
            ['email' => 'd@example.com', 'status' => 'active'],
        ]);

        $result = new UsersPaginationStub()
            ->set_connection($connection)
            ->paginate(page: 1, per_page: 2)
        ;

        self::assertCount(2, $result['data']);
        self::assertSame(3, $result['meta']['total']);
        self::assertSame(2, $result['meta']['per_page']);
        self::assertSame(1, $result['meta']['current_page']);
        self::assertSame(2, $result['meta']['last_page']);
        self::assertSame(1, $result['meta']['from']);
        self::assertSame(2, $result['meta']['to']);
        self::assertTrue($result['meta']['has_more']);
        self::assertSame('?page=1', $result['links']['first']);
        self::assertSame('?page=2', $result['links']['next']);
    }

    public function test_class_mode_uses_default_per_page_of_twenty_five(): void
    {
        $connection = db_sqlite_connect($this->primary_sqlite_database_path);
        $rows = [];

        for ($index = 1; $index <= 30; ++$index) {
            $rows[] = [
                'email' => 'user'.$index.'@example.com',
                'status' => 'active',
            ];
        }

        $this->seed_users($connection, $rows);

        $result = new UsersPaginationStub()
            ->set_connection($connection)
            ->paginate(page: 1)
        ;

        self::assertSame(25, $result['meta']['per_page']);
        self::assertCount(25, $result['data']);
        self::assertSame(2, $result['meta']['last_page']);
    }

    public function test_set_connection_changes_dataset(): void
    {
        $primary_connection = db_sqlite_connect($this->primary_sqlite_database_path);
        $secondary_connection = db_sqlite_connect($this->secondary_sqlite_database_path);

        $this->seed_users($primary_connection, [
            ['email' => 'a@example.com', 'status' => 'active'],
            ['email' => 'b@example.com', 'status' => 'inactive'],
            ['email' => 'c@example.com', 'status' => 'active'],
            ['email' => 'd@example.com', 'status' => 'active'],
        ]);
        $this->seed_users($secondary_connection, [
            ['email' => 'x@example.com', 'status' => 'active'],
        ]);

        $paginator = new UsersPaginationStub()->set_connection($primary_connection);
        $primary_result = $paginator->paginate(page: 1, per_page: 10);
        $secondary_result = $paginator
            ->set_connection($secondary_connection)
            ->paginate(page: 1, per_page: 10)
        ;

        self::assertSame(3, $primary_result['meta']['total']);
        self::assertSame(1, $secondary_result['meta']['total']);
    }

    public function test_class_mode_set_options_applies_links_and_max_per_page(): void
    {
        $connection = db_sqlite_connect($this->primary_sqlite_database_path);
        $this->seed_users($connection, [
            ['email' => 'a@example.com', 'status' => 'active'],
            ['email' => 'b@example.com', 'status' => 'active'],
            ['email' => 'c@example.com', 'status' => 'active'],
        ]);

        $result = new UsersPaginationStub()
            ->set_connection($connection)
            ->set_options(
                PaginationOptionsBag::make()
                    ->set_base_path('/users')
                    ->set_query(['status' => 'active'])
                    ->set_max_per_page(1)
            )
            ->paginate(page: 2, per_page: 50)
        ;

        self::assertSame(1, $result['meta']['per_page']);
        self::assertCount(1, $result['data']);
        self::assertSame('/users?status=active&page=1', $result['links']['first']);
        self::assertSame('/users?status=active&page=1', $result['links']['prev']);
        self::assertSame('/users?status=active&page=3', $result['links']['next']);
        self::assertSame('/users?status=active&page=3', $result['links']['last']);
    }

    public function test_class_mode_resolves_page_from_route_query_when_page_argument_is_omitted(): void
    {
        $connection = db_sqlite_connect($this->primary_sqlite_database_path);
        $this->seed_users($connection, [
            ['email' => 'a@example.com', 'status' => 'active'],
            ['email' => 'b@example.com', 'status' => 'inactive'],
            ['email' => 'c@example.com', 'status' => 'active'],
            ['email' => 'd@example.com', 'status' => 'active'],
        ]);

        $route_snapshot = $this->snapshot_route_global();
        $GLOBALS['route'] = ['query' => ['page' => '2']];

        try {
            $result = new UsersPaginationStub()
                ->set_connection($connection)
                ->paginate(per_page: 1)
            ;
        } finally {
            $this->restore_route_global($route_snapshot);
        }

        self::assertSame(2, $result['meta']['current_page']);
        self::assertSame(2, $result['meta']['from']);
        self::assertSame(2, $result['meta']['to']);
    }

    public function test_helper_mode_matches_class_mode_and_applies_link_options(): void
    {
        $connection = db_sqlite_connect($this->primary_sqlite_database_path);
        $this->seed_users($connection, [
            ['email' => 'a@example.com', 'status' => 'active'],
            ['email' => 'b@example.com', 'status' => 'inactive'],
            ['email' => 'c@example.com', 'status' => 'active'],
            ['email' => 'd@example.com', 'status' => 'active'],
        ]);

        $base_query = QueryBuilder::select()
            ->from('users')
            ->columns('id', 'email', 'status')
            ->where('status', '=', 'active')
            ->order_by('id', 'asc')
        ;

        $helper_result = pagination_paginate(
            base_query: $base_query,
            page: 2,
            per_page: 2,
            connection: $connection,
            options: PaginationOptionsBag::make()
                ->set_base_path('/model/pagination')
                ->set_query(['status' => 'active'])
        );

        $class_result = new UsersPaginationStub()
            ->set_connection($connection)
            ->paginate(page: 2, per_page: 2)
        ;

        self::assertSame($class_result['meta']['total'], $helper_result['meta']['total']);
        self::assertSame($class_result['meta']['last_page'], $helper_result['meta']['last_page']);
        self::assertCount(1, $helper_result['data']);
        self::assertSame('/model/pagination?status=active&page=1', $helper_result['links']['first']);
        self::assertSame('/model/pagination?status=active&page=1', $helper_result['links']['prev']);
        self::assertSame('/model/pagination?status=active&page=2', $helper_result['links']['next']);
        self::assertSame('/model/pagination?status=active&page=2', $helper_result['links']['last']);
    }

    public function test_helper_mode_resolves_page_from_route_query_when_page_argument_is_omitted(): void
    {
        $connection = db_sqlite_connect($this->primary_sqlite_database_path);
        $this->seed_users($connection, [
            ['email' => 'a@example.com', 'status' => 'active'],
            ['email' => 'b@example.com', 'status' => 'inactive'],
            ['email' => 'c@example.com', 'status' => 'active'],
            ['email' => 'd@example.com', 'status' => 'active'],
        ]);

        $base_query = QueryBuilder::select()
            ->from('users')
            ->columns('id', 'email', 'status')
            ->where('status', '=', 'active')
            ->order_by('id', 'asc')
        ;

        $route_snapshot = $this->snapshot_route_global();
        $GLOBALS['route'] = ['query' => ['page' => '3']];

        try {
            $result = pagination_paginate(
                base_query: $base_query,
                per_page: 1,
                connection: $connection
            );
        } finally {
            $this->restore_route_global($route_snapshot);
        }

        self::assertSame(3, $result['meta']['current_page']);
        self::assertSame(3, $result['meta']['from']);
        self::assertSame(3, $result['meta']['to']);
    }

    public function test_helper_mode_uses_default_per_page_of_twenty_five(): void
    {
        $connection = db_sqlite_connect($this->primary_sqlite_database_path);
        $rows = [];

        for ($index = 1; $index <= 30; ++$index) {
            $rows[] = [
                'email' => 'user'.$index.'@example.com',
                'status' => 'active',
            ];
        }

        $this->seed_users($connection, $rows);

        $base_query = QueryBuilder::select()
            ->from('users')
            ->columns('id', 'email')
            ->where('status', '=', 'active')
            ->order_by('id', 'asc')
        ;

        $result = pagination_paginate(
            base_query: $base_query,
            page: 1,
            connection: $connection
        );

        self::assertSame(25, $result['meta']['per_page']);
        self::assertCount(25, $result['data']);
        self::assertSame(2, $result['meta']['last_page']);
    }

    public function test_explicit_page_argument_overrides_route_query_page(): void
    {
        $connection = db_sqlite_connect($this->primary_sqlite_database_path);
        $this->seed_users($connection, [
            ['email' => 'a@example.com', 'status' => 'active'],
            ['email' => 'b@example.com', 'status' => 'inactive'],
            ['email' => 'c@example.com', 'status' => 'active'],
            ['email' => 'd@example.com', 'status' => 'active'],
        ]);

        $route_snapshot = $this->snapshot_route_global();
        $GLOBALS['route'] = ['query' => ['page' => '3']];

        try {
            $result = new UsersPaginationStub()
                ->set_connection($connection)
                ->paginate(page: 1, per_page: 1)
            ;
        } finally {
            $this->restore_route_global($route_snapshot);
        }

        self::assertSame(1, $result['meta']['current_page']);
        self::assertSame(1, $result['meta']['from']);
        self::assertSame(1, $result['meta']['to']);
    }

    public function test_paginate_throws_when_base_query_contains_limit(): void
    {
        $connection = db_sqlite_connect($this->primary_sqlite_database_path);
        $this->seed_users($connection, [
            ['email' => 'a@example.com', 'status' => 'active'],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Pagination base_query() cannot include limit().');

        new InvalidLimitPaginationStub()
            ->set_connection($connection)
            ->paginate(page: 1, per_page: 10)
        ;
    }

    public function test_paginate_throws_for_invalid_page_and_per_page(): void
    {
        $connection = db_sqlite_connect($this->primary_sqlite_database_path);
        $this->seed_users($connection, [
            ['email' => 'a@example.com', 'status' => 'active'],
        ]);

        $base_query = QueryBuilder::select()
            ->from('users')
            ->columns('id')
            ->order_by('id', 'asc')
        ;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('paginate() expects page >= 1.');

        pagination_paginate($base_query, 0, 10, $connection);
    }

    public function test_paginate_throws_for_invalid_per_page(): void
    {
        $connection = db_sqlite_connect($this->primary_sqlite_database_path);
        $this->seed_users($connection, [
            ['email' => 'a@example.com', 'status' => 'active'],
        ]);

        $base_query = QueryBuilder::select()
            ->from('users')
            ->columns('id')
            ->order_by('id', 'asc')
        ;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('paginate() expects per_page >= 1.');

        pagination_paginate($base_query, 1, 0, $connection);
    }

    public function test_helper_mode_applies_max_per_page_from_options_bag(): void
    {
        $connection = db_sqlite_connect($this->primary_sqlite_database_path);
        $this->seed_users($connection, [
            ['email' => 'a@example.com', 'status' => 'active'],
            ['email' => 'b@example.com', 'status' => 'active'],
            ['email' => 'c@example.com', 'status' => 'active'],
        ]);

        $base_query = QueryBuilder::select()
            ->from('users')
            ->columns('id', 'email')
            ->where('status', '=', 'active')
            ->order_by('id', 'asc')
        ;

        $result = pagination_paginate(
            base_query: $base_query,
            page: 1,
            per_page: 50,
            connection: $connection,
            options: PaginationOptionsBag::make()->set_max_per_page(2)
        );

        self::assertSame(2, $result['meta']['per_page']);
        self::assertCount(2, $result['data']);
        self::assertSame(2, $result['meta']['last_page']);
    }

    #[Before]
    protected function create_workspace(): void
    {
        $this->workspace_path = sys_get_temp_dir().'/harbor_pagination_'.bin2hex(random_bytes(8));
        $this->primary_sqlite_database_path = $this->workspace_path.'/primary.sqlite';
        $this->secondary_sqlite_database_path = $this->workspace_path.'/secondary.sqlite';

        if (! mkdir($this->workspace_path, 0o777, true) && ! is_dir($this->workspace_path)) {
            throw new \RuntimeException(sprintf('Failed to create test workspace "%s".', $this->workspace_path));
        }
    }

    #[After]
    protected function cleanup_workspace(): void
    {
        if (! is_dir($this->workspace_path)) {
            return;
        }

        $this->delete_directory_tree($this->workspace_path);
    }

    private function seed_users(\mysqli|\PDO $connection, array $rows): void
    {
        db_execute(
            $connection,
            'CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT NOT NULL, status TEXT NOT NULL)'
        );
        db_execute($connection, 'DELETE FROM users');

        foreach ($rows as $row) {
            db_execute(
                $connection,
                'INSERT INTO users (email, status) VALUES (?, ?)',
                [(string) ($row['email'] ?? ''), (string) ($row['status'] ?? '')]
            );
        }
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

    /**
     * @return array{exists: bool, value: mixed}
     */
    private function snapshot_route_global(): array
    {
        return [
            'exists' => array_key_exists('route', $GLOBALS),
            'value' => $GLOBALS['route'] ?? null,
        ];
    }

    /**
     * @param array{exists: bool, value: mixed} $snapshot
     */
    private function restore_route_global(array $snapshot): void
    {
        if ($snapshot['exists']) {
            $GLOBALS['route'] = $snapshot['value'];

            return;
        }

        unset($GLOBALS['route']);
    }
}
