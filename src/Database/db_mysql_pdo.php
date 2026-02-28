<?php

declare(strict_types=1);

namespace Harbor\Database;

require_once __DIR__.'/../Support/value.php';

require_once __DIR__.'/../Support/array.php';

require_once __DIR__.'/MysqlDto.php';

use function Harbor\Support\array_first;
use function Harbor\Support\array_last;
use function Harbor\Support\harbor_is_blank;

function db_mysql_connect(
    string $host,
    string $user,
    string $password,
    string $database,
    int $port = 3306,
    string $charset = 'utf8mb4',
    array $options = []
): \PDO {
    $normalized_host = trim($host);
    if (harbor_is_blank($normalized_host)) {
        throw new \InvalidArgumentException('MySQL host cannot be empty.');
    }

    $normalized_user = trim($user);
    if (harbor_is_blank($normalized_user)) {
        throw new \InvalidArgumentException('MySQL user cannot be empty.');
    }

    $normalized_database = trim($database);
    if (harbor_is_blank($normalized_database)) {
        throw new \InvalidArgumentException('MySQL database cannot be empty.');
    }

    $normalized_charset = trim($charset);
    if (harbor_is_blank($normalized_charset)) {
        throw new \InvalidArgumentException('MySQL charset cannot be empty.');
    }

    $pdo_options = array_replace(
        [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ],
        $options
    );

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $normalized_host,
        $port,
        $normalized_database,
        $normalized_charset
    );

    return new \PDO($dsn, $normalized_user, $password, $pdo_options);
}

function db_mysql_connect_dto(MysqlDto $dto): \PDO
{
    return db_mysql_connect(
        $dto->host,
        $dto->user,
        $dto->password,
        $dto->database,
        $dto->port,
        $dto->charset,
        $dto->options
    );
}

function db_mysql_execute(\PDO $connection, string $sql, array $bindings = []): bool
{
    db_mysql_assert_driver($connection);

    $statement = db_mysql_prepare_and_execute($connection, $sql, $bindings);

    return $statement->rowCount() >= 0;
}

function db_mysql_array(\PDO $connection, string $sql, array $bindings = []): array
{
    db_mysql_assert_driver($connection);

    $statement = db_mysql_prepare_and_execute($connection, $sql, $bindings);
    $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

    return is_array($rows) ? $rows : [];
}

function db_mysql_first(\PDO $connection, string $sql, array $bindings = []): array
{
    $rows = db_mysql_array($connection, $sql, $bindings);
    $first_row = array_first($rows, []);

    return is_array($first_row) ? $first_row : [];
}

function db_mysql_last(\PDO $connection, string $sql, array $bindings = []): array
{
    $rows = db_mysql_array($connection, $sql, $bindings);
    $last_row = array_last($rows, []);

    return is_array($last_row) ? $last_row : [];
}

function db_mysql_objects(\PDO $connection, string $sql, array $bindings = []): array
{
    db_mysql_assert_driver($connection);

    $statement = db_mysql_prepare_and_execute($connection, $sql, $bindings);
    $rows = $statement->fetchAll(\PDO::FETCH_OBJ);

    return is_array($rows) ? $rows : [];
}

function db_mysql_prepare_and_execute(\PDO $connection, string $sql, array $bindings = []): \PDOStatement
{
    $normalized_sql = trim($sql);
    if (harbor_is_blank($normalized_sql)) {
        throw new \InvalidArgumentException('SQL query cannot be empty.');
    }

    $statement = $connection->prepare($normalized_sql);
    if (! $statement instanceof \PDOStatement) {
        throw new \RuntimeException('Failed to prepare MySQL PDO statement.');
    }

    $executed = $statement->execute($bindings);
    if (false === $executed) {
        throw new \RuntimeException('Failed to execute MySQL PDO statement.');
    }

    return $statement;
}

function db_mysql_assert_driver(\PDO $connection): void
{
    $driver_name = (string) $connection->getAttribute(\PDO::ATTR_DRIVER_NAME);
    if ('mysql' !== strtolower($driver_name)) {
        throw new \InvalidArgumentException('Provided PDO connection is not a MySQL connection.');
    }
}
