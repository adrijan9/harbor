<?php

declare(strict_types=1);

namespace Harbor\Database;

require_once __DIR__.'/../Filesystem/filesystem.php';

require_once __DIR__.'/../Support/array.php';

require_once __DIR__.'/../Support/value.php';

require_once __DIR__.'/SqliteDto.php';

use function Harbor\Filesystem\fs_dir_create;
use function Harbor\Filesystem\fs_dir_exists;
use function Harbor\Support\array_first;
use function Harbor\Support\array_last;
use function Harbor\Support\harbor_is_blank;

/** Public */
function db_sqlite_connect(string $database_path, array $options = []): \PDO
{
    $normalized_database_path = trim($database_path);
    if (harbor_is_blank($normalized_database_path)) {
        throw new \InvalidArgumentException('SQLite database path cannot be empty.');
    }

    $directory_path = dirname($normalized_database_path);
    if (! fs_dir_exists($directory_path)) {
        fs_dir_create($directory_path);
    }

    $pdo_options = array_replace(
        [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ],
        $options
    );

    return new \PDO('sqlite:'.$normalized_database_path, null, null, $pdo_options);
}

function db_sqlite_connect_dto(SqliteDto $dto): \PDO
{
    return db_sqlite_connect($dto->database_path, $dto->options);
}

function db_sqlite_close(\PDO $connection): bool
{
    db_sqlite_assert_driver($connection);

    return true;
}

function db_sqlite_execute(\PDO $connection, string $sql, array $bindings = []): bool
{
    db_sqlite_assert_driver($connection);

    $statement = db_sqlite_prepare_and_execute($connection, $sql, $bindings);

    return $statement->rowCount() >= 0;
}

function db_sqlite_array(\PDO $connection, string $sql, array $bindings = []): array
{
    db_sqlite_assert_driver($connection);

    $statement = db_sqlite_prepare_and_execute($connection, $sql, $bindings);
    $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

    return is_array($rows) ? $rows : [];
}

function db_sqlite_first(\PDO $connection, string $sql, array $bindings = []): array
{
    $rows = db_sqlite_array($connection, $sql, $bindings);
    $first_row = array_first($rows, []);

    return is_array($first_row) ? $first_row : [];
}

function db_sqlite_last(\PDO $connection, string $sql, array $bindings = []): array
{
    $rows = db_sqlite_array($connection, $sql, $bindings);
    $last_row = array_last($rows, []);

    return is_array($last_row) ? $last_row : [];
}

function db_sqlite_objects(\PDO $connection, string $sql, array $bindings = []): array
{
    db_sqlite_assert_driver($connection);

    $statement = db_sqlite_prepare_and_execute($connection, $sql, $bindings);
    $rows = $statement->fetchAll(\PDO::FETCH_OBJ);

    return is_array($rows) ? $rows : [];
}

/** Private */
function db_sqlite_prepare_and_execute(\PDO $connection, string $sql, array $bindings = []): \PDOStatement
{
    $normalized_sql = trim($sql);
    if (harbor_is_blank($normalized_sql)) {
        throw new \InvalidArgumentException('SQL query cannot be empty.');
    }

    $statement = $connection->prepare($normalized_sql);
    if (! $statement instanceof \PDOStatement) {
        throw new \RuntimeException('Failed to prepare SQLite statement.');
    }

    $executed = $statement->execute($bindings);
    if (false === $executed) {
        throw new \RuntimeException('Failed to execute SQLite statement.');
    }

    return $statement;
}

function db_sqlite_assert_driver(\PDO $connection): void
{
    $driver_name = (string) $connection->getAttribute(\PDO::ATTR_DRIVER_NAME);
    if ('sqlite' !== strtolower($driver_name)) {
        throw new \InvalidArgumentException('Provided PDO connection is not a SQLite connection.');
    }
}
