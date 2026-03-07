<?php

declare(strict_types=1);

namespace Harbor\Database;

require_once __DIR__.'/../Config/config.php';

require_once __DIR__.'/../Support/array.php';

require_once __DIR__.'/../Support/value.php';

require_once __DIR__.'/DbDriver.php';
require_once __DIR__.'/db_sqlite.php';
require_once __DIR__.'/db_mysql_pdo.php';
require_once __DIR__.'/db_mysqli.php';

use function Harbor\Config\config_array_get;
use function Harbor\Config\config_resolve;
use function Harbor\Support\array_first;
use function Harbor\Support\array_last;
use function Harbor\Support\harbor_is_blank;
use function Harbor\Support\harbor_is_null;

function db_driver(string|DbDriver $default_driver = DbDriver::SQLITE): string
{
    $resolved_default_driver = db_resolve_driver($default_driver);
    if (harbor_is_null($resolved_default_driver)) {
        $resolved_default_driver = DbDriver::SQLITE;
    }

    $configured_driver = config_resolve('db.driver', 'database.driver', $resolved_default_driver->value);
    $resolved_driver = db_resolve_driver($configured_driver);

    if (harbor_is_null($resolved_driver)) {
        return $resolved_default_driver->value;
    }

    return $resolved_driver->value;
}

function db_is_sqlite(): bool
{
    return DbDriver::SQLITE->value === db_driver();
}

function db_is_mysql(): bool
{
    return DbDriver::MYSQL->value === db_driver();
}

function db_is_mysqli(): bool
{
    return DbDriver::MYSQLI->value === db_driver();
}

function db_connect(string|DbDriver|null $driver = null, array $config = []): \PDO|\mysqli
{
    $resolved_driver = db_resolve_driver($driver ?? db_driver());
    if (harbor_is_null($resolved_driver)) {
        throw new \InvalidArgumentException('Unable to resolve database driver.');
    }

    if (DbDriver::SQLITE === $resolved_driver) {
        $sqlite_path = db_resolve_sqlite_path($config);
        if (! is_string($sqlite_path) || harbor_is_blank($sqlite_path)) {
            throw new \RuntimeException('SQLite database path not configured. Set "db.sqlite.path" or provide it in db_connect() config.');
        }

        return db_sqlite_connect($sqlite_path);
    }

    $host = db_resolve_mysql_option($config, ['host'], '127.0.0.1');
    $user = db_resolve_mysql_option($config, ['user', 'username'], 'root');
    $password = db_resolve_mysql_option($config, ['password', 'pass'], '');
    $database = db_resolve_mysql_option($config, ['database', 'db'], '');
    $port = (int) db_resolve_mysql_option($config, ['port'], 3306);
    $charset = db_resolve_mysql_option($config, ['charset'], 'utf8mb4');

    if (DbDriver::MYSQLI === $resolved_driver) {
        return db_mysqli_connect($host, $user, $password, $database, $port, $charset);
    }

    return db_mysql_connect($host, $user, $password, $database, $port, $charset);
}

function db_execute(\PDO|\mysqli $connection, string $sql, array $bindings = []): bool
{
    if ($connection instanceof \mysqli) {
        if (empty($bindings) === false) {
            throw new \InvalidArgumentException('MySQLi wrapper does not support bindings in db_execute().');
        }

        return db_mysqli_execute($connection, $sql);
    }

    $driver_name = strtolower((string) $connection->getAttribute(\PDO::ATTR_DRIVER_NAME));
    if ('sqlite' === $driver_name) {
        return db_sqlite_execute($connection, $sql, $bindings);
    }

    if ('mysql' === $driver_name) {
        return db_mysql_execute($connection, $sql, $bindings);
    }

    return db_pdo_execute($connection, $sql, $bindings);
}

function db_array(\PDO|\mysqli $connection, string $sql, array $bindings = []): array
{
    if ($connection instanceof \mysqli) {
        if (empty($bindings) === false) {
            throw new \InvalidArgumentException('MySQLi wrapper does not support bindings in db_array().');
        }

        return db_mysqli_array($connection, $sql);
    }

    $driver_name = strtolower((string) $connection->getAttribute(\PDO::ATTR_DRIVER_NAME));
    if ('sqlite' === $driver_name) {
        return db_sqlite_array($connection, $sql, $bindings);
    }

    if ('mysql' === $driver_name) {
        return db_mysql_array($connection, $sql, $bindings);
    }

    return db_pdo_array($connection, $sql, $bindings);
}

function db_first(\PDO|\mysqli $connection, string $sql, array $bindings = []): array
{
    $rows = db_array($connection, $sql, $bindings);
    $first_row = array_first($rows, []);

    return is_array($first_row) ? $first_row : [];
}

function db_last(\PDO|\mysqli $connection, string $sql, array $bindings = []): array
{
    $rows = db_array($connection, $sql, $bindings);
    $last_row = array_last($rows, []);

    return is_array($last_row) ? $last_row : [];
}

function db_objects(\PDO|\mysqli $connection, string $sql, array $bindings = []): array
{
    if ($connection instanceof \mysqli) {
        if (empty($bindings) === false) {
            throw new \InvalidArgumentException('MySQLi wrapper does not support bindings in db_objects().');
        }

        return db_mysqli_objects($connection, $sql);
    }

    $driver_name = strtolower((string) $connection->getAttribute(\PDO::ATTR_DRIVER_NAME));
    if ('sqlite' === $driver_name) {
        return db_sqlite_objects($connection, $sql, $bindings);
    }

    if ('mysql' === $driver_name) {
        return db_mysql_objects($connection, $sql, $bindings);
    }

    return db_pdo_objects($connection, $sql, $bindings);
}

function db_close(\PDO|\mysqli $connection): bool
{
    if ($connection instanceof \mysqli) {
        return db_mysqli_close($connection);
    }

    $driver_name = strtolower((string) $connection->getAttribute(\PDO::ATTR_DRIVER_NAME));
    if ('sqlite' === $driver_name) {
        return db_sqlite_close($connection);
    }

    if ('mysql' === $driver_name) {
        return db_mysql_pdo_close($connection);
    }

    return true;
}

function db_resolve_driver(mixed $driver): ?DbDriver
{
    if ($driver instanceof DbDriver) {
        return $driver;
    }

    if (! is_string($driver)) {
        return null;
    }

    $normalized_driver = strtolower(trim($driver));
    if (harbor_is_blank($normalized_driver)) {
        return null;
    }

    return DbDriver::tryFrom($normalized_driver);
}

function db_resolve_sqlite_path(array $config = []): ?string
{
    $config_path = db_config_pick(
        $config,
        ['sqlite.path', 'path', 'database']
    );

    if (is_string($config_path) && ! harbor_is_blank($config_path)) {
        return trim($config_path);
    }

    $resolved_from_runtime = config_resolve('db.sqlite.path', 'database.sqlite.path');
    if (is_string($resolved_from_runtime) && ! harbor_is_blank($resolved_from_runtime)) {
        return trim($resolved_from_runtime);
    }

    return null;
}

function db_resolve_mysql_option(array $config, array $keys, mixed $default = null): mixed
{
    foreach ($keys as $key) {
        $from_config = db_config_pick($config, ['mysql.'.$key, $key]);
        if (! harbor_is_null($from_config) && ! (is_string($from_config) && harbor_is_blank($from_config))) {
            return $from_config;
        }

        $from_db = config_resolve('db.mysql.'.$key, 'database.mysql.'.$key);
        if (! harbor_is_null($from_db) && ! (is_string($from_db) && harbor_is_blank($from_db))) {
            return $from_db;
        }
    }

    return $default;
}

function db_config_pick(array $config, array $keys): mixed
{
    foreach ($keys as $key) {
        $value = config_array_get($config, $key);
        if (! harbor_is_null($value)) {
            return $value;
        }
    }

    return null;
}

function db_pdo_execute(\PDO $connection, string $sql, array $bindings = []): bool
{
    $statement = db_pdo_prepare_and_execute($connection, $sql, $bindings);

    return $statement->rowCount() >= 0;
}

function db_pdo_array(\PDO $connection, string $sql, array $bindings = []): array
{
    $statement = db_pdo_prepare_and_execute($connection, $sql, $bindings);
    $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

    return is_array($rows) ? $rows : [];
}

function db_pdo_objects(\PDO $connection, string $sql, array $bindings = []): array
{
    $statement = db_pdo_prepare_and_execute($connection, $sql, $bindings);
    $rows = $statement->fetchAll(\PDO::FETCH_OBJ);

    return is_array($rows) ? $rows : [];
}

function db_pdo_prepare_and_execute(\PDO $connection, string $sql, array $bindings = []): \PDOStatement
{
    $normalized_sql = trim($sql);
    if (harbor_is_blank($normalized_sql)) {
        throw new \InvalidArgumentException('SQL query cannot be empty.');
    }

    $statement = $connection->prepare($normalized_sql);
    if (! $statement instanceof \PDOStatement) {
        throw new \RuntimeException('Failed to prepare PDO statement.');
    }

    $executed = $statement->execute($bindings);
    if (false === $executed) {
        throw new \RuntimeException('Failed to execute PDO statement.');
    }

    return $statement;
}
