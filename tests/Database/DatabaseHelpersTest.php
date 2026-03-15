<?php

declare(strict_types=1);

namespace Harbor\Tests\Database;

use Harbor\Database\DbDriver;
use Harbor\Database\MysqlDto;
use Harbor\Database\SqliteDto;
use Harbor\HelperLoader;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\TestCase;

use function Harbor\Database\db_array;
use function Harbor\Database\db_begin;
use function Harbor\Database\db_close;
use function Harbor\Database\db_commit;
use function Harbor\Database\db_connect;
use function Harbor\Database\db_driver;
use function Harbor\Database\db_execute;
use function Harbor\Database\db_first;
use function Harbor\Database\db_is_mysqli;
use function Harbor\Database\db_is_mysql;
use function Harbor\Database\db_is_sqlite;
use function Harbor\Database\db_last;
use function Harbor\Database\db_mysqli_connect;
use function Harbor\Database\db_mysqli_connect_dto;
use function Harbor\Database\db_mysql_connect;
use function Harbor\Database\db_mysql_connect_dto;
use function Harbor\Database\db_mysql_execute;
use function Harbor\Database\db_mysql_pdo_close;
use function Harbor\Database\db_objects;
use function Harbor\Database\db_rollback;
use function Harbor\Database\db_sqlite_array;
use function Harbor\Database\db_sqlite_connect;
use function Harbor\Database\db_sqlite_connect_dto;
use function Harbor\Database\db_sqlite_close;
use function Harbor\Database\db_sqlite_execute;
use function Harbor\Database\db_sqlite_first;
use function Harbor\Database\db_sqlite_last;
use function Harbor\Database\db_sqlite_objects;
use function Harbor\Database\db_transaction;

/**
 * Class DatabaseHelpersTest.
 */
final class DatabaseHelpersTest extends TestCase
{
    private string $workspace_path;
    private string $sqlite_database_path;
    private array $original_env = [];

    #[BeforeClass]
    public static function load_db_helpers(): void
    {
        HelperLoader::load('db');
    }

    public function test_db_sqlite_helpers_execute_array_and_objects_queries(): void
    {
        $connection = db_sqlite_connect($this->sqlite_database_path);

        db_sqlite_execute($connection, 'CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT)');
        db_sqlite_execute($connection, 'INSERT INTO users (name, email) VALUES (:name, :email)', [
            'name' => 'Ada',
            'email' => 'ada@example.com',
        ]);
        db_sqlite_execute($connection, 'INSERT INTO users (name, email) VALUES (:name, :email)', [
            'name' => 'Linus',
            'email' => 'linus@example.com',
        ]);

        $rows = db_sqlite_array($connection, 'SELECT name, email FROM users ORDER BY id ASC');
        self::assertSame(
            [
                ['name' => 'Ada', 'email' => 'ada@example.com'],
                ['name' => 'Linus', 'email' => 'linus@example.com'],
            ],
            $rows
        );

        $objects = db_sqlite_objects($connection, 'SELECT name, email FROM users ORDER BY id ASC');
        self::assertCount(2, $objects);
        self::assertIsObject($objects[0]);
        self::assertSame('Ada', $objects[0]->name);
        self::assertSame('linus@example.com', $objects[1]->email);

        $first = db_sqlite_first($connection, 'SELECT name, email FROM users ORDER BY id ASC');
        $last = db_sqlite_last($connection, 'SELECT name, email FROM users ORDER BY id ASC');
        self::assertSame(['name' => 'Ada', 'email' => 'ada@example.com'], $first);
        self::assertSame(['name' => 'Linus', 'email' => 'linus@example.com'], $last);

        $empty_first = db_sqlite_first($connection, 'SELECT name, email FROM users WHERE id < 0');
        $empty_last = db_sqlite_last($connection, 'SELECT name, email FROM users WHERE id < 0');
        self::assertSame([], $empty_first);
        self::assertSame([], $empty_last);
    }

    public function test_db_sqlite_connect_dto_connects_and_queries(): void
    {
        $dto = SqliteDto::make($this->sqlite_database_path);
        $connection = db_sqlite_connect_dto($dto);

        db_sqlite_execute($connection, 'CREATE TABLE notes (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT)');
        db_sqlite_execute($connection, 'INSERT INTO notes (title) VALUES (:title)', [
            'title' => 'DTO connect',
        ]);

        $rows = db_sqlite_array($connection, 'SELECT title FROM notes ORDER BY id ASC');
        self::assertSame([['title' => 'DTO connect']], $rows);
    }

    public function test_db_sqlite_close_returns_true_for_sqlite_connection(): void
    {
        $connection = db_sqlite_connect($this->sqlite_database_path);

        self::assertTrue(db_sqlite_close($connection));
        self::assertTrue(db_close($connection));
    }

    public function test_db_mysql_pdo_close_throws_for_non_mysql_pdo_connection(): void
    {
        $connection = db_sqlite_connect($this->sqlite_database_path);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Provided PDO connection is not a MySQL connection.');

        db_mysql_pdo_close($connection);
    }

    public function test_sqlite_dto_from_config_reads_runtime_config(): void
    {
        $_ENV['db'] = [
            'sqlite' => [
                'path' => $this->sqlite_database_path,
            ],
        ];
        $GLOBALS['_ENV'] = $_ENV;

        $dto = SqliteDto::from_config();

        self::assertSame($this->sqlite_database_path, $dto->database_path);
    }

    public function test_mysql_dto_from_config_reads_runtime_config(): void
    {
        $_ENV['db'] = [
            'mysql' => [
                'host' => '192.168.1.10',
                'port' => 3307,
                'user' => 'app_user',
                'password' => 'secret',
                'database' => 'harbor_db',
                'charset' => 'utf8',
                'options' => [\PDO::ATTR_TIMEOUT => 2],
            ],
        ];
        $GLOBALS['_ENV'] = $_ENV;

        $dto = MysqlDto::from_config();

        self::assertSame('192.168.1.10', $dto->host);
        self::assertSame(3307, $dto->port);
        self::assertSame('app_user', $dto->user);
        self::assertSame('secret', $dto->password);
        self::assertSame('harbor_db', $dto->database);
        self::assertSame('utf8', $dto->charset);
        self::assertSame([\PDO::ATTR_TIMEOUT => 2], $dto->options);
    }

    public function test_db_connect_resolves_sqlite_driver_and_runs_generic_queries(): void
    {
        $_ENV['db'] = [
            'driver' => 'sqlite',
            'sqlite' => [
                'path' => $this->sqlite_database_path,
            ],
        ];
        $GLOBALS['_ENV'] = $_ENV;

        self::assertSame('sqlite', db_driver());
        self::assertTrue(db_is_sqlite());
        self::assertFalse(db_is_mysql());
        self::assertFalse(db_is_mysqli());

        $connection = db_connect();

        db_execute($connection, 'CREATE TABLE tasks (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT)');
        db_execute($connection, 'INSERT INTO tasks (title) VALUES (:title)', [
            'title' => 'Write tests',
        ]);

        $rows = db_array($connection, 'SELECT title FROM tasks ORDER BY id ASC');
        self::assertSame([['title' => 'Write tests']], $rows);

        $first = db_first($connection, 'SELECT title FROM tasks ORDER BY id ASC');
        $last = db_last($connection, 'SELECT title FROM tasks ORDER BY id ASC');
        self::assertSame(['title' => 'Write tests'], $first);
        self::assertSame(['title' => 'Write tests'], $last);

        $objects = db_objects($connection, 'SELECT title FROM tasks ORDER BY id ASC');
        self::assertCount(1, $objects);
        self::assertSame('Write tests', $objects[0]->title);
    }

    public function test_db_begin_and_db_commit_persist_changes_for_sqlite(): void
    {
        $connection = db_sqlite_connect($this->sqlite_database_path);
        db_execute($connection, 'CREATE TABLE transaction_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, note TEXT)');

        self::assertTrue(db_begin($connection));
        self::assertTrue(db_execute($connection, 'INSERT INTO transaction_logs (note) VALUES (:note)', [
            'note' => 'committed',
        ]));
        self::assertTrue(db_commit($connection));

        $rows = db_array($connection, 'SELECT note FROM transaction_logs ORDER BY id ASC');
        self::assertSame([['note' => 'committed']], $rows);
    }

    public function test_db_begin_and_db_rollback_revert_changes_for_sqlite(): void
    {
        $connection = db_sqlite_connect($this->sqlite_database_path);
        db_execute($connection, 'CREATE TABLE transaction_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, note TEXT)');

        self::assertTrue(db_begin($connection));
        self::assertTrue(db_execute($connection, 'INSERT INTO transaction_logs (note) VALUES (:note)', [
            'note' => 'rolled-back',
        ]));
        self::assertTrue(db_rollback($connection));

        $rows = db_array($connection, 'SELECT note FROM transaction_logs ORDER BY id ASC');
        self::assertSame([], $rows);
    }

    public function test_db_transaction_commits_on_success_and_returns_callback_value(): void
    {
        $connection = db_sqlite_connect($this->sqlite_database_path);
        db_execute($connection, 'CREATE TABLE transaction_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, note TEXT)');

        $result = db_transaction($connection, function (\PDO $active_connection): string {
            db_execute($active_connection, 'INSERT INTO transaction_logs (note) VALUES (:note)', [
                'note' => 'inside-callback',
            ]);

            return 'done';
        });

        self::assertSame('done', $result);
        $rows = db_array($connection, 'SELECT note FROM transaction_logs ORDER BY id ASC');
        self::assertSame([['note' => 'inside-callback']], $rows);
    }

    public function test_db_transaction_rolls_back_when_callback_throws(): void
    {
        $connection = db_sqlite_connect($this->sqlite_database_path);
        db_execute($connection, 'CREATE TABLE transaction_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, note TEXT)');

        try {
            db_transaction($connection, function (\PDO $active_connection): void {
                db_execute($active_connection, 'INSERT INTO transaction_logs (note) VALUES (:note)', [
                    'note' => 'should-not-persist',
                ]);

                throw new \RuntimeException('callback failed');
            });

            self::fail('Expected db_transaction() to rethrow callback exception.');
        } catch (\RuntimeException $exception) {
            self::assertSame('callback failed', $exception->getMessage());
        }

        $rows = db_array($connection, 'SELECT note FROM transaction_logs ORDER BY id ASC');
        self::assertSame([], $rows);
    }

    public function test_db_transaction_joins_existing_pdo_transaction_without_auto_commit(): void
    {
        $connection = db_sqlite_connect($this->sqlite_database_path);
        db_execute($connection, 'CREATE TABLE transaction_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, note TEXT)');

        self::assertTrue(db_begin($connection));

        db_transaction($connection, function (\PDO $active_connection): void {
            db_execute($active_connection, 'INSERT INTO transaction_logs (note) VALUES (:note)', [
                'note' => 'nested',
            ]);
        });

        self::assertTrue(db_rollback($connection));

        $rows = db_array($connection, 'SELECT note FROM transaction_logs ORDER BY id ASC');
        self::assertSame([], $rows);
    }

    public function test_db_driver_falls_back_for_invalid_configured_driver(): void
    {
        $_ENV['db'] = [
            'driver' => 'redis',
        ];
        $GLOBALS['_ENV'] = $_ENV;

        self::assertSame('sqlite', db_driver());
        self::assertSame('mysql', db_driver(DbDriver::MYSQL));
    }

    public function test_db_connect_throws_when_sqlite_path_is_missing(): void
    {
        $_ENV['db'] = [
            'driver' => 'sqlite',
            'sqlite' => [],
        ];
        $GLOBALS['_ENV'] = $_ENV;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SQLite database path not configured. Set "db.sqlite.path" or provide it in db_connect() config.');

        db_connect();
    }

    public function test_db_mysql_connect_validates_required_arguments(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('MySQL host cannot be empty.');

        db_mysql_connect('   ', 'root', '', 'test');
    }

    public function test_db_mysqli_connect_validates_required_arguments(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('MySQLi host cannot be empty.');

        db_mysqli_connect('   ', 'root', '', 'test');
    }

    public function test_db_mysql_connect_dto_validates_required_arguments(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('MySQL host cannot be empty.');

        $dto = new MysqlDto('   ', 'root', '', 'test');
        db_mysql_connect_dto($dto);
    }

    public function test_db_mysqli_connect_dto_validates_required_arguments(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('MySQLi host cannot be empty.');

        $dto = new MysqlDto('   ', 'root', '', 'test');
        db_mysqli_connect_dto($dto);
    }

    public function test_db_mysql_execute_throws_for_non_mysql_pdo_connection(): void
    {
        $connection = db_sqlite_connect($this->sqlite_database_path);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Provided PDO connection is not a MySQL connection.');

        db_mysql_execute($connection, 'SELECT 1');
    }

    #[Before]
    protected function create_workspace(): void
    {
        $this->original_env = is_array($_ENV) ? $_ENV : [];
        $this->workspace_path = sys_get_temp_dir().'/harbor_db_'.bin2hex(random_bytes(8));
        $this->sqlite_database_path = $this->workspace_path.'/database.sqlite';

        if (! mkdir($this->workspace_path, 0o777, true) && ! is_dir($this->workspace_path)) {
            throw new \RuntimeException(sprintf('Failed to create test workspace "%s".', $this->workspace_path));
        }

        $_ENV = $this->original_env;
        $GLOBALS['_ENV'] = $_ENV;
    }

    #[After]
    protected function cleanup_workspace(): void
    {
        $_ENV = $this->original_env;
        $GLOBALS['_ENV'] = $_ENV;

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
