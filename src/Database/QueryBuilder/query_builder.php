<?php

declare(strict_types=1);

namespace Harbor\Database;

require_once __DIR__.'/QueryExpression.php';

require_once __DIR__.'/QueryBuilder.php';

require_once __DIR__.'/query_compile.php';

use Harbor\Database\QueryBuilder\QueryBuilder;

/** Public */
function query_select(?string $table = null): QueryBuilder
{
    return query_builder_new('select', $table);
}

function query_insert(?string $table = null): QueryBuilder
{
    return query_builder_new('insert', $table);
}

function query_update(?string $table = null): QueryBuilder
{
    return query_builder_new('update', $table);
}

function query_delete(?string $table = null): QueryBuilder
{
    return query_builder_new('delete', $table);
}

/** Private */
function query_builder_new(string $type, ?string $table = null): QueryBuilder
{
    return match ($type) {
        'select' => QueryBuilder::select($table),
        'insert' => QueryBuilder::insert($table),
        'update' => QueryBuilder::update($table),
        'delete' => QueryBuilder::delete($table),
        default => throw new \InvalidArgumentException(sprintf('Unsupported query builder type "%s".', $type)),
    };
}
