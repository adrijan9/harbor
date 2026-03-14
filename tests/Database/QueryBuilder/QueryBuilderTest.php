<?php

declare(strict_types=1);

namespace Harbor\Tests\Database\QueryBuilder;

use Harbor\HelperLoader;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\TestCase;

use function Harbor\Database\db_array;
use function Harbor\Database\db_execute;
use function Harbor\Database\db_sqlite_connect;
use function Harbor\Database\query;
use function Harbor\Database\query_builder_interpolate_bindings;
use function Harbor\Database\query_delete;
use function Harbor\Database\query_insert;
use function Harbor\Database\query_select;
use function Harbor\Database\query_update;

/**
 * Class QueryBuilderTest.
 */
final class QueryBuilderTest extends TestCase
{
    private string $workspace_path;
    private string $sqlite_database_path;

    #[BeforeClass]
    public static function load_db_helpers(): void
    {
        HelperLoader::load('db');
    }

    public function test_select_get_sql_get_bindings_and_build_are_consistent(): void
    {
        $builder = query_select('users')
            ->columns('id', 'email')
            ->where('status', '=', 'active')
            ->where_between('created_at', '2026-01-01', '2026-12-31')
            ->order_by('id', 'desc')
            ->limit(10)
            ->offset(20)
        ;

        $sql = $builder->get_sql('sqlite');
        $bindings = $builder->get_bindings();

        self::assertSame(
            'SELECT `id`, `email` FROM `users` WHERE `status` = ? AND `created_at` BETWEEN ? AND ? ORDER BY `id` DESC LIMIT 10 OFFSET 20',
            $sql
        );
        self::assertSame(['active', '2026-01-01', '2026-12-31'], $bindings);

        self::assertSame(
            query_builder_interpolate_bindings($sql, $bindings),
            $builder->build('sqlite')
        );
    }

    public function test_query_helper_supports_query_select_from_flow(): void
    {
        $builder = query()
            ->select()
            ->from('users')
            ->columns('id')
            ->where('status', '=', 'active')
            ->limit(1)
        ;

        self::assertSame(
            'SELECT `id` FROM `users` WHERE `status` = ? LIMIT 1',
            $builder->get_sql('sqlite')
        );
        self::assertSame(['active'], $builder->get_bindings());
    }

    public function test_subquery_helpers_compile_expected_sql(): void
    {
        $exists_sub = query_select('orders')
            ->columns('id')
            ->where_raw('orders.user_id = users.id')
        ;

        $in_sub = query_select('orders')
            ->columns('user_id')
            ->where('status', '=', 'paid')
        ;

        $builder = query_select('users')
            ->columns('users.id')
            ->where_exists($exists_sub)
            ->where_in_sub('users.id', $in_sub)
        ;

        self::assertSame(
            'SELECT `users`.`id` FROM `users` WHERE EXISTS (SELECT `id` FROM `orders` WHERE (orders.user_id = users.id)) AND `users`.`id` IN (SELECT `user_id` FROM `orders` WHERE `status` = ?)',
            $builder->get_sql('sqlite')
        );
        self::assertSame(['paid'], $builder->get_bindings());
    }

    public function test_select_sub_from_sub_and_join_sub_compile_expected_sql(): void
    {
        $total_orders_sub = query_select('orders')
            ->select_raw('COUNT(*)')
            ->where_raw('orders.user_id = users.id')
        ;

        $select_sub_sql = query_select('users')
            ->columns('users.id')
            ->select_sub($total_orders_sub, 'orders_count')
            ->get_sql('sqlite')
        ;

        self::assertSame(
            'SELECT `users`.`id`, (SELECT COUNT(*) FROM `orders` WHERE (orders.user_id = users.id)) AS `orders_count` FROM `users`',
            $select_sub_sql
        );

        $active_users_sub = query_select('users')
            ->columns('id')
            ->where('status', '=', 'active')
        ;

        $from_sub_builder = query_select()
            ->from_sub($active_users_sub, 'u')
            ->columns('u.id')
        ;

        self::assertSame(
            'SELECT `u`.`id` FROM (SELECT `id` FROM `users` WHERE `status` = ?) AS `u`',
            $from_sub_builder->get_sql('sqlite')
        );
        self::assertSame(['active'], $from_sub_builder->get_bindings());

        $paid_orders_sub = query_select('orders')
            ->sum('amount', 'paid_total')
            ->where('status', '=', 'paid')
            ->group_by('user_id')
        ;

        $join_sub_builder = query_select('users')
            ->columns('users.id', 'po.paid_total')
            ->left_join_sub($paid_orders_sub, 'po', 'po.user_id', '=', 'users.id')
        ;

        self::assertSame(
            'SELECT `users`.`id`, `po`.`paid_total` FROM `users` LEFT JOIN (SELECT SUM(`amount`) AS `paid_total` FROM `orders` WHERE `status` = ? GROUP BY `user_id`) AS `po` ON `po`.`user_id` = `users`.`id`',
            $join_sub_builder->get_sql('sqlite')
        );
        self::assertSame(['paid'], $join_sub_builder->get_bindings());
    }

    public function test_lock_helpers_compile_precise_prefix_suffix_and_defaults(): void
    {
        $for_update_sql = query_select('users')
            ->columns('id')
            ->lock_for_update()
            ->get_sql('mysql')
        ;

        self::assertSame('SELECT `id` FROM `users` FOR UPDATE', $for_update_sql);

        $suffix_override_sql = query_select('users')
            ->columns('id')
            ->lock_for_update()
            ->lock_suffix('FOR UPDATE SKIP LOCKED')
            ->get_sql('mysql')
        ;

        self::assertSame(
            'SELECT `id` FROM `users` FOR UPDATE SKIP LOCKED',
            $suffix_override_sql
        );

        $prefix_sql = query_select('users')
            ->columns('id')
            ->lock_prefix('SQL_NO_CACHE')
            ->get_sql('mysql')
        ;

        self::assertSame('SELECT SQL_NO_CACHE `id` FROM `users`', $prefix_sql);

        $clear_sql = query_select('users')
            ->columns('id')
            ->lock_for_update()
            ->clear_lock()
            ->get_sql('mysql')
        ;

        self::assertSame('SELECT `id` FROM `users`', $clear_sql);
    }

    public function test_lock_for_update_throws_for_sqlite_driver(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SQLite driver does not support row lock clauses.');

        query_select('users')
            ->columns('id')
            ->lock_for_update()
            ->get_sql('sqlite')
        ;
    }

    public function test_date_part_helpers_compile_for_sqlite(): void
    {
        $builder = query_select('orders')
            ->columns('id')
            ->where_year('created_at', '=', 2026)
            ->where_month('created_at', '=', 3)
            ->where_day('created_at', '>=', 10)
            ->where_date('created_at', '=', '2026-03-14')
        ;

        self::assertSame(
            "SELECT `id` FROM `orders` WHERE CAST(STRFTIME('%Y', `created_at`) AS INTEGER) = ? AND CAST(STRFTIME('%m', `created_at`) AS INTEGER) = ? AND CAST(STRFTIME('%d', `created_at`) AS INTEGER) >= ? AND DATE(`created_at`) = ?",
            $builder->get_sql('sqlite')
        );
        self::assertSame([2026, 3, 10, '2026-03-14'], $builder->get_bindings());
    }

    public function test_delete_requires_allow_full_table_opt_in(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Delete query without where() requires allow_full_table(true).');

        query_delete('users')->get_sql('sqlite');
    }

    public function test_delete_allow_full_table_compiles_after_opt_in(): void
    {
        $sql = query_delete('users')
            ->allow_full_table(true)
            ->get_sql('sqlite')
        ;

        self::assertSame('DELETE FROM `users`', $sql);
    }

    public function test_upsert_compiles_for_sqlite_and_mysql(): void
    {
        $builder = query_insert('users')->upsert(
            [
                ['email' => 'ada@example.com', 'status' => 'active'],
                ['email' => 'linus@example.com', 'status' => 'active'],
            ],
            ['email'],
            ['status']
        );

        self::assertSame(
            'INSERT INTO `users` (`email`, `status`) VALUES (?, ?), (?, ?) ON CONFLICT (`email`) DO UPDATE SET `status` = excluded.`status`',
            $builder->get_sql('sqlite')
        );
        self::assertSame(
            'INSERT INTO `users` (`email`, `status`) VALUES (?, ?), (?, ?) ON DUPLICATE KEY UPDATE `status` = VALUES(`status`)',
            $builder->get_sql('mysql')
        );
        self::assertSame(
            ['ada@example.com', 'active', 'linus@example.com', 'active'],
            $builder->get_bindings()
        );
    }

    public function test_returning_throws_for_mysql_driver(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Driver "mysql" does not support RETURNING clause.');

        query_insert('users')
            ->values(['email' => 'ada@example.com'])
            ->returning('id')
            ->get_sql('mysql')
        ;
    }

    public function test_query_builder_executes_on_sqlite_with_placeholder_mode(): void
    {
        $connection = db_sqlite_connect($this->sqlite_database_path);

        db_execute(
            $connection,
            'CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT NOT NULL, status TEXT NOT NULL)'
        );

        $insert_builder = query_insert('users')->rows([
            ['email' => 'ada@example.com', 'status' => 'active'],
            ['email' => 'linus@example.com', 'status' => 'inactive'],
        ]);

        self::assertTrue(
            db_execute($connection, $insert_builder->get_sql('sqlite'), $insert_builder->get_bindings())
        );

        $select_builder = query_select('users')
            ->columns('email')
            ->where('status', '=', 'active')
            ->order_by('id')
        ;

        $active_rows = db_array($connection, $select_builder->get_sql('sqlite'), $select_builder->get_bindings());
        self::assertSame([['email' => 'ada@example.com']], $active_rows);

        $update_builder = query_update('users')
            ->set('status', 'active')
            ->where('email', '=', 'linus@example.com')
        ;

        self::assertTrue(
            db_execute($connection, $update_builder->get_sql('sqlite'), $update_builder->get_bindings())
        );

        $delete_builder = query_delete('users')
            ->where('email', '=', 'ada@example.com')
        ;

        self::assertTrue(
            db_execute($connection, $delete_builder->get_sql('sqlite'), $delete_builder->get_bindings())
        );

        $remaining_rows = db_array($connection, 'SELECT email, status FROM users ORDER BY id ASC');
        self::assertSame([['email' => 'linus@example.com', 'status' => 'active']], $remaining_rows);
    }

    #[Before]
    protected function create_workspace(): void
    {
        $this->workspace_path = sys_get_temp_dir().'/harbor_query_builder_'.bin2hex(random_bytes(8));
        $this->sqlite_database_path = $this->workspace_path.'/database.sqlite';

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
