<?php

declare(strict_types=1);

namespace Harbor\Database;

require_once __DIR__.'/../Support/value.php';

require_once __DIR__.'/MysqlDto.php';

use function Harbor\Support\harbor_is_blank;

function db_mysqli_connect(
    string $host,
    string $user,
    string $password,
    string $database,
    int $port = 3306,
    string $charset = 'utf8mb4'
): \mysqli {
    $normalized_host = trim($host);
    if (harbor_is_blank($normalized_host)) {
        throw new \InvalidArgumentException('MySQLi host cannot be empty.');
    }

    $normalized_user = trim($user);
    if (harbor_is_blank($normalized_user)) {
        throw new \InvalidArgumentException('MySQLi user cannot be empty.');
    }

    $normalized_database = trim($database);
    if (harbor_is_blank($normalized_database)) {
        throw new \InvalidArgumentException('MySQLi database cannot be empty.');
    }

    $normalized_charset = trim($charset);
    if (harbor_is_blank($normalized_charset)) {
        throw new \InvalidArgumentException('MySQLi charset cannot be empty.');
    }

    $connection = mysqli_init();
    if (! $connection instanceof \mysqli) {
        throw new \RuntimeException('Failed to initialize MySQLi connection.');
    }

    $connected = $connection->real_connect($normalized_host, $normalized_user, $password, $normalized_database, $port);
    if (false === $connected) {
        throw new \RuntimeException('Failed to connect using MySQLi: '.$connection->connect_error);
    }

    if (! $connection->set_charset($normalized_charset)) {
        throw new \RuntimeException('Failed to set MySQLi charset: '.$connection->error);
    }

    return $connection;
}

function db_mysqli_connect_dto(MysqlDto $dto): \mysqli
{
    return db_mysqli_connect(
        $dto->host,
        $dto->user,
        $dto->password,
        $dto->database,
        $dto->port,
        $dto->charset
    );
}

function db_mysqli_execute(\mysqli $connection, string $sql): bool
{
    $result = db_mysqli_query($connection, $sql);

    if ($result instanceof \mysqli_result) {
        $result->free();
    }

    return true;
}

function db_mysqli_array(\mysqli $connection, string $sql): array
{
    $result = db_mysqli_query($connection, $sql);
    if (! $result instanceof \mysqli_result) {
        return [];
    }

    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();

    return is_array($rows) ? $rows : [];
}

function db_mysqli_objects(\mysqli $connection, string $sql): array
{
    $result = db_mysqli_query($connection, $sql);
    if (! $result instanceof \mysqli_result) {
        return [];
    }

    $rows = [];
    while ($object = $result->fetch_object()) {
        $rows[] = $object;
    }

    $result->free();

    return $rows;
}

function db_mysqli_query(\mysqli $connection, string $sql): \mysqli_result|bool
{
    $normalized_sql = trim($sql);
    if (harbor_is_blank($normalized_sql)) {
        throw new \InvalidArgumentException('SQL query cannot be empty.');
    }

    $result = $connection->query($normalized_sql);
    if (false === $result) {
        throw new \RuntimeException('MySQLi query failed: '.$connection->error);
    }

    return $result;
}
