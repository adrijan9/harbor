<?php

declare(strict_types=1);

namespace Harbor\Database;

require_once __DIR__.'/Transaction.php';

use Harbor\Database\Transaction\Transaction;

/** Public */
function db_begin(\mysqli|\PDO $connection): bool
{
    return Transaction::begin($connection);
}

function db_commit(\mysqli|\PDO $connection): bool
{
    return Transaction::commit($connection);
}

function db_rollback(\mysqli|\PDO $connection): bool
{
    return Transaction::rollback($connection);
}

function db_transaction(\mysqli|\PDO $connection, callable $callback): mixed
{
    return Transaction::run($connection, $callback);
}
