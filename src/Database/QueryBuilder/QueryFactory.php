<?php

declare(strict_types=1);

namespace Harbor\Database\QueryBuilder;

/**
 * Class QueryFactory.
 */
final class QueryFactory
{
    public function select(?string $table = null): QueryBuilder
    {
        return QueryBuilder::select($table);
    }

    public function insert(?string $table = null): QueryBuilder
    {
        return QueryBuilder::insert($table);
    }

    public function update(?string $table = null): QueryBuilder
    {
        return QueryBuilder::update($table);
    }

    public function delete(?string $table = null): QueryBuilder
    {
        return QueryBuilder::delete($table);
    }

    public function table(string $table): QueryBuilder
    {
        return QueryBuilder::select($table);
    }
}
