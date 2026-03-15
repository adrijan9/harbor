<?php

declare(strict_types=1);

namespace Harbor\Database\Transaction;

/**
 * Class Transaction.
 */
final class Transaction
{
    public static function begin(\mysqli|\PDO $connection): bool
    {
        if ($connection instanceof \PDO) {
            return self::pdo_begin($connection);
        }

        return self::mysqli_begin($connection);
    }

    public static function commit(\mysqli|\PDO $connection): bool
    {
        if ($connection instanceof \PDO) {
            return self::pdo_commit($connection);
        }

        return self::mysqli_commit($connection);
    }

    public static function rollback(\mysqli|\PDO $connection): bool
    {
        if ($connection instanceof \PDO) {
            return self::pdo_rollback($connection);
        }

        return self::mysqli_rollback($connection);
    }

    public static function run(\mysqli|\PDO $connection, callable $callback): mixed
    {
        if ($connection instanceof \PDO) {
            return self::run_pdo($connection, $callback);
        }

        return self::run_mysqli($connection, $callback);
    }

    private static function run_pdo(\PDO $connection, callable $callback): mixed
    {
        $owns_transaction = ! $connection->inTransaction();

        if ($owns_transaction) {
            self::pdo_begin($connection);
        }

        try {
            $result = $callback($connection);
        } catch (\Throwable $throwable) {
            if ($owns_transaction && $connection->inTransaction()) {
                self::pdo_rollback($connection);
            }

            throw $throwable;
        }

        if ($owns_transaction && $connection->inTransaction()) {
            self::pdo_commit($connection);
        }

        return $result;
    }

    private static function run_mysqli(\mysqli $connection, callable $callback): mixed
    {
        self::mysqli_begin($connection);

        try {
            $result = $callback($connection);
        } catch (\Throwable $throwable) {
            try {
                self::mysqli_rollback($connection);
            } catch (\Throwable $rollback_throwable) {
                throw new \RuntimeException(
                    'MySQLi transaction callback failed and rollback failed: '.$rollback_throwable->getMessage(),
                    previous: $throwable
                );
            }

            throw $throwable;
        }

        self::mysqli_commit($connection);

        return $result;
    }

    private static function pdo_begin(\PDO $connection): bool
    {
        if ($connection->inTransaction()) {
            return true;
        }

        $started = $connection->beginTransaction();
        if (false === $started) {
            throw new \RuntimeException('Failed to begin PDO transaction.');
        }

        return true;
    }

    private static function pdo_commit(\PDO $connection): bool
    {
        if (! $connection->inTransaction()) {
            return true;
        }

        $committed = $connection->commit();
        if (false === $committed) {
            throw new \RuntimeException('Failed to commit PDO transaction.');
        }

        return true;
    }

    private static function pdo_rollback(\PDO $connection): bool
    {
        if (! $connection->inTransaction()) {
            return true;
        }

        $rolled_back = $connection->rollBack();
        if (false === $rolled_back) {
            throw new \RuntimeException('Failed to rollback PDO transaction.');
        }

        return true;
    }

    private static function mysqli_begin(\mysqli $connection): bool
    {
        try {
            $started = $connection->begin_transaction();
        } catch (\Throwable $throwable) {
            throw new \RuntimeException(
                'Failed to begin MySQLi transaction: '.$throwable->getMessage(),
                previous: $throwable
            );
        }

        if (false === $started) {
            throw new \RuntimeException('Failed to begin MySQLi transaction: '.$connection->error);
        }

        return true;
    }

    private static function mysqli_commit(\mysqli $connection): bool
    {
        try {
            $committed = $connection->commit();
        } catch (\Throwable $throwable) {
            throw new \RuntimeException(
                'Failed to commit MySQLi transaction: '.$throwable->getMessage(),
                previous: $throwable
            );
        }

        if (false === $committed) {
            throw new \RuntimeException('Failed to commit MySQLi transaction: '.$connection->error);
        }

        return true;
    }

    private static function mysqli_rollback(\mysqli $connection): bool
    {
        try {
            $rolled_back = $connection->rollback();
        } catch (\Throwable $throwable) {
            throw new \RuntimeException(
                'Failed to rollback MySQLi transaction: '.$throwable->getMessage(),
                previous: $throwable
            );
        }

        if (false === $rolled_back) {
            throw new \RuntimeException('Failed to rollback MySQLi transaction: '.$connection->error);
        }

        return true;
    }
}
