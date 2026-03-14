<?php

declare(strict_types=1);

namespace Harbor\Tests\Database\Schema;

use Harbor\Database\Schema\Column;
use Harbor\Database\Schema\ForeignKey;
use Harbor\HelperLoader;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\TestCase;

use function Harbor\Database\db_array;
use function Harbor\Database\db_execute;
use function Harbor\Database\db_sqlite_connect;
use function Harbor\Database\schema_add_column;
use function Harbor\Database\schema_add_foreign;
use function Harbor\Database\schema_builder_alter;
use function Harbor\Database\schema_builder_create;
use function Harbor\Database\schema_builder_drop;
use function Harbor\Database\schema_builder_rename;
use function Harbor\Database\schema_execute;
use function Harbor\Database\schema_rename_column;
use function Harbor\Database\schema_statements;

/**
 * Class SchemaBuilderTest.
 */
final class SchemaBuilderTest extends TestCase
{
    private string $workspace_path;
    private string $sqlite_database_path;

    #[BeforeClass]
    public static function load_schema_helpers(): void
    {
        HelperLoader::load('db');
    }

    public function test_schema_builder_create_compiles_and_executes_for_sqlite(): void
    {
        $connection = db_sqlite_connect($this->sqlite_database_path);

        $builder = schema_builder_create('users', true);
        $builder = schema_add_column($builder, 'id', Column::int()->primary()->auto_increment());
        $builder = schema_add_column($builder, 'email', Column::varchar(190));
        $builder = schema_add_column($builder, 'status', Column::varchar(30)->default('active'));

        $statements = schema_statements($builder, 'sqlite');

        self::assertNotEmpty($statements);
        self::assertStringContainsString('CREATE TABLE IF NOT EXISTS `users`', $statements[0]);
        self::assertStringContainsString('`id` INTEGER PRIMARY KEY AUTOINCREMENT', $statements[0]);
        self::assertStringContainsString("`status` VARCHAR(30) NOT NULL DEFAULT 'active'", $statements[0]);

        self::assertTrue(schema_execute($connection, $builder, 'sqlite'));

        $tables = db_array(
            $connection,
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name = :name",
            ['name' => 'users']
        );

        self::assertSame([['name' => 'users']], $tables);
    }

    public function test_schema_execute_runs_multiple_statements_and_creates_index(): void
    {
        $connection = db_sqlite_connect($this->sqlite_database_path);

        $builder = schema_builder_create('profiles');
        $builder = schema_add_column($builder, 'id', Column::int()->primary()->auto_increment());
        $builder = schema_add_column($builder, 'nickname', Column::varchar(100)->index());

        $statements = schema_statements($builder, 'sqlite');

        self::assertCount(2, $statements);
        self::assertStringStartsWith('CREATE TABLE', $statements[0]);
        self::assertStringStartsWith('CREATE INDEX', $statements[1]);

        self::assertTrue(schema_execute($connection, $builder, 'sqlite'));

        $indexes = db_array(
            $connection,
            "SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = :table_name ORDER BY name ASC",
            ['table_name' => 'profiles']
        );

        self::assertSame([['name' => 'profiles_nickname_index']], $indexes);
    }

    public function test_schema_builder_rename_and_drop_execute_for_sqlite(): void
    {
        $connection = db_sqlite_connect($this->sqlite_database_path);

        db_execute($connection, 'CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT)');

        $rename_builder = schema_builder_rename('users', 'accounts');
        self::assertTrue(schema_execute($connection, $rename_builder, 'sqlite'));

        $renamed_tables = db_array(
            $connection,
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name = :name",
            ['name' => 'accounts']
        );
        self::assertSame([['name' => 'accounts']], $renamed_tables);

        $drop_builder = schema_builder_drop('accounts', true);
        self::assertTrue(schema_execute($connection, $drop_builder, 'sqlite'));

        $remaining_tables = db_array(
            $connection,
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name = :name",
            ['name' => 'accounts']
        );
        self::assertSame([], $remaining_tables);
    }

    public function test_schema_add_column_with_after_throws_for_sqlite(): void
    {
        $builder = schema_builder_alter('users');
        $builder = schema_add_column($builder, 'nickname', Column::varchar(120)->after('email'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SQLite driver does not support AFTER column positioning.');

        schema_statements($builder, 'sqlite');
    }

    public function test_schema_foreign_key_add_throws_for_sqlite_alter(): void
    {
        $builder = schema_builder_alter('posts');
        $builder = schema_add_foreign(
            $builder,
            ForeignKey::from('user_id')
                ->references('id')
                ->on('users')
                ->on_delete('cascade')
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SQLite driver does not support ALTER TABLE ADD CONSTRAINT FOREIGN KEY.');

        schema_statements($builder, 'sqlite');
    }

    public function test_schema_rejects_unsafe_default_expression_tokens(): void
    {
        $builder = schema_builder_alter('users');
        $builder = schema_add_column(
            $builder,
            'danger',
            Column::varchar(50)->default_expression("x'; DROP TABLE users; --")
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('contains unsafe SQL token');

        schema_statements($builder, 'sqlite');
    }

    public function test_schema_rename_column_compiles_for_sqlite(): void
    {
        $builder = schema_builder_alter('users');
        $builder = schema_rename_column($builder, 'old_name', 'new_name');

        $statements = schema_statements($builder, 'sqlite');

        self::assertSame(
            ['ALTER TABLE `users` RENAME COLUMN `old_name` TO `new_name`'],
            $statements
        );
    }

    #[Before]
    protected function create_workspace(): void
    {
        $this->workspace_path = sys_get_temp_dir().'/harbor_schema_'.bin2hex(random_bytes(8));
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
