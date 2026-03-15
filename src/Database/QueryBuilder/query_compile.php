<?php

declare(strict_types=1);

namespace Harbor\Database;

require_once __DIR__.'/../../Support/value.php';

require_once __DIR__.'/QueryExpression.php';

require_once __DIR__.'/QueryBuilder.php';

use Harbor\Database\QueryBuilder\QueryBuilder;
use Harbor\Database\QueryBuilder\QueryExpression;

use function Harbor\Support\harbor_is_blank;
use function Harbor\Support\harbor_is_null;

/** Public */
/**
 * @return array{driver: string, statement: string, bindings: array<int, mixed>}
 */
function query_builder_compile(QueryBuilder $builder, ?string $driver = null): array
{
    $resolved_driver = query_builder_resolve_driver($driver);
    $state = $builder->state();

    $compiled = match ($state['type'] ?? null) {
        'select' => query_builder_compile_select($state, $resolved_driver),
        'insert' => query_builder_compile_insert($state, $resolved_driver),
        'update' => query_builder_compile_update($state, $resolved_driver),
        'delete' => query_builder_compile_delete($state, $resolved_driver),
        default => throw new \InvalidArgumentException(
            sprintf('Unsupported query builder type "%s".', (string) ($state['type'] ?? ''))
        ),
    };

    $compiled['driver'] = $resolved_driver;

    return $compiled;
}

/**
 * @param array<int, mixed> $bindings
 */
function query_builder_interpolate_bindings(string $statement, array $bindings): string
{
    $placeholder_count = substr_count($statement, '?');
    if ($placeholder_count !== count($bindings)) {
        throw new \InvalidArgumentException(
            sprintf(
                'Bindings count mismatch. SQL placeholders: %d, bindings: %d.',
                $placeholder_count,
                count($bindings)
            )
        );
    }

    if (0 === $placeholder_count) {
        return $statement;
    }

    $segments = explode('?', $statement);
    $sql = array_shift($segments);

    foreach ($bindings as $index => $binding) {
        $sql .= query_builder_literal($binding).($segments[$index] ?? '');
    }

    return $sql;
}

/** Private */
/**
 * @param array<string, mixed> $state
 *
 * @return array{statement: string, bindings: array<int, mixed>}
 */
function query_builder_compile_select(array $state, string $driver): array
{
    $bindings = [];
    $columns_sql = query_builder_compile_select_columns($state, $driver, $bindings);
    $from_sql = query_builder_compile_select_from($state, $driver, $bindings);

    $lock_sql = query_builder_compile_lock($state['lock'] ?? [], $driver);

    $sql = 'SELECT';
    if (! harbor_is_blank($lock_sql['prefix'])) {
        $sql .= ' '.$lock_sql['prefix'];
    }

    if ((bool) ($state['distinct'] ?? false)) {
        $sql .= ' DISTINCT';
    }

    $sql .= ' '.$columns_sql.' FROM '.$from_sql;

    $joins_sql = query_builder_compile_joins($state['joins'] ?? [], $driver, $bindings);
    if (! harbor_is_blank($joins_sql)) {
        $sql .= ' '.$joins_sql;
    }

    $where_sql = query_builder_compile_conditions($state['wheres'] ?? [], $driver, $bindings);
    if (! harbor_is_blank($where_sql)) {
        $sql .= ' WHERE '.$where_sql;
    }

    $group_sql = query_builder_compile_group_by($state['groups'] ?? []);
    if (! harbor_is_blank($group_sql)) {
        $sql .= ' '.$group_sql;
    }

    $having_sql = query_builder_compile_conditions($state['havings'] ?? [], $driver, $bindings);
    if (! harbor_is_blank($having_sql)) {
        $sql .= ' HAVING '.$having_sql;
    }

    $order_sql = query_builder_compile_order_by($state['orders'] ?? []);
    if (! harbor_is_blank($order_sql)) {
        $sql .= ' '.$order_sql;
    }

    $limit = $state['limit'] ?? null;
    $offset = $state['offset'] ?? null;

    if (is_int($limit)) {
        $sql .= ' LIMIT '.$limit;
    }

    if (is_int($offset)) {
        if (! is_int($limit)) {
            $sql .= in_array($driver, ['mysql', 'mysqli'], true)
                ? ' LIMIT 18446744073709551615'
                : ' LIMIT -1';
        }

        $sql .= ' OFFSET '.$offset;
    }

    $unions = $state['unions'] ?? [];
    if (is_array($unions) && ! empty($unions)) {
        $union_sql = '('.$sql.')';

        foreach ($unions as $union) {
            $union_query = $union['query'] ?? null;
            if (! $union_query instanceof QueryBuilder || 'select' !== $union_query->type()) {
                throw new \InvalidArgumentException('union() and union_all() accept only select query builders.');
            }

            $compiled_union = query_builder_compile($union_query, $driver);
            $union_sql .= (bool) ($union['all'] ?? false) ? ' UNION ALL ' : ' UNION ';
            $union_sql .= '('.$compiled_union['statement'].')';

            foreach ($compiled_union['bindings'] as $binding) {
                $bindings[] = $binding;
            }
        }

        $sql = $union_sql;
    }

    if (! harbor_is_blank($lock_sql['suffix'])) {
        $sql .= ' '.$lock_sql['suffix'];
    }

    return [
        'statement' => $sql,
        'bindings' => $bindings,
    ];
}

/**
 * @param array<string, mixed> $state
 *
 * @return array{statement: string, bindings: array<int, mixed>}
 */
function query_builder_compile_insert(array $state, string $driver): array
{
    $table = $state['table'] ?? null;
    if (! is_string($table) || harbor_is_blank($table)) {
        throw new \InvalidArgumentException('Insert query requires into() table.');
    }

    if ((bool) ($state['ignore'] ?? false) && is_array($state['upsert'] ?? null)) {
        throw new \InvalidArgumentException('Insert ignore cannot be combined with upsert.');
    }

    $upsert = $state['upsert'] ?? null;
    if (is_array($upsert)) {
        return query_builder_compile_upsert($state, $driver);
    }

    $rows = $state['insert_rows'] ?? [];
    if (! is_array($rows) || empty($rows)) {
        throw new \InvalidArgumentException('Insert query requires values() or rows().');
    }

    [$columns, $normalized_rows] = query_builder_normalize_insert_rows($rows);

    $bindings = [];
    $placeholder_chunks = [];

    foreach ($normalized_rows as $row) {
        $row_placeholders = [];
        foreach ($columns as $column) {
            $row_placeholders[] = '?';
            $bindings[] = $row[$column];
        }
        $placeholder_chunks[] = '('.implode(', ', $row_placeholders).')';
    }

    $insert_keyword = 'INSERT INTO';
    if ((bool) ($state['ignore'] ?? false)) {
        $insert_keyword = 'sqlite' === $driver ? 'INSERT OR IGNORE INTO' : 'INSERT IGNORE INTO';
    }

    $sql = sprintf(
        '%s %s (%s) VALUES %s',
        $insert_keyword,
        query_builder_quote_identifier_path($table),
        implode(', ', array_map('Harbor\Database\query_builder_quote_identifier_path', $columns)),
        implode(', ', $placeholder_chunks)
    );

    $sql .= query_builder_compile_returning($state['returning'] ?? [], $driver);

    return [
        'statement' => $sql,
        'bindings' => $bindings,
    ];
}

/**
 * @param array<string, mixed> $state
 *
 * @return array{statement: string, bindings: array<int, mixed>}
 */
function query_builder_compile_update(array $state, string $driver): array
{
    $table = $state['table'] ?? null;
    if (! is_string($table) || harbor_is_blank($table)) {
        throw new \InvalidArgumentException('Update query requires table() table.');
    }

    $updates = $state['updates'] ?? [];
    if (! is_array($updates) || empty($updates)) {
        throw new \InvalidArgumentException('Update query requires set() or increment()/decrement() operations.');
    }

    $set_fragments = [];
    $bindings = [];

    foreach ($updates as $update) {
        if (! is_array($update)) {
            throw new \InvalidArgumentException('Invalid update operation payload.');
        }

        $column = query_builder_quote_identifier_path((string) ($update['column'] ?? ''));
        $type = (string) ($update['type'] ?? '');

        if ('set' === $type) {
            $set_fragments[] = sprintf('%s = ?', $column);
            $bindings[] = $update['value'] ?? null;

            continue;
        }

        if ('increment' === $type) {
            $set_fragments[] = sprintf('%s = %s + ?', $column, $column);
            $bindings[] = $update['by'] ?? 1;

            continue;
        }

        if ('decrement' === $type) {
            $set_fragments[] = sprintf('%s = %s - ?', $column, $column);
            $bindings[] = $update['by'] ?? 1;

            continue;
        }

        throw new \InvalidArgumentException(
            sprintf('Unsupported update operation type "%s".', $type)
        );
    }

    $sql = sprintf(
        'UPDATE %s SET %s',
        query_builder_quote_identifier_path($table),
        implode(', ', $set_fragments)
    );

    $where_sql = query_builder_compile_conditions($state['wheres'] ?? [], $driver, $bindings);
    if (! harbor_is_blank($where_sql)) {
        $sql .= ' WHERE '.$where_sql;
    }

    $sql .= query_builder_compile_returning($state['returning'] ?? [], $driver);

    return [
        'statement' => $sql,
        'bindings' => $bindings,
    ];
}

/**
 * @param array<string, mixed> $state
 *
 * @return array{statement: string, bindings: array<int, mixed>}
 */
function query_builder_compile_delete(array $state, string $driver): array
{
    $table = $state['table'] ?? null;
    if (! is_string($table) || harbor_is_blank($table)) {
        throw new \InvalidArgumentException('Delete query requires from() table.');
    }

    $bindings = [];
    $wheres = $state['wheres'] ?? [];
    if (! is_array($wheres)) {
        $wheres = [];
    }

    if (empty($wheres) && ! (bool) ($state['allow_full_table'] ?? false)) {
        throw new \InvalidArgumentException('Delete query without where() requires allow_full_table(true).');
    }

    $sql = 'DELETE FROM '.query_builder_quote_identifier_path($table);
    $where_sql = query_builder_compile_conditions($wheres, $driver, $bindings);
    if (! harbor_is_blank($where_sql)) {
        $sql .= ' WHERE '.$where_sql;
    }

    $sql .= query_builder_compile_returning($state['returning'] ?? [], $driver);

    return [
        'statement' => $sql,
        'bindings' => $bindings,
    ];
}

/**
 * @param array<string, mixed> $state
 * @param array<int, mixed>    $bindings
 */
function query_builder_compile_select_columns(array $state, string $driver, array &$bindings): string
{
    $aggregate = $state['aggregate'] ?? null;
    if (is_array($aggregate) && ! harbor_is_blank((string) ($aggregate['function'] ?? null))) {
        $function_name = strtoupper((string) $aggregate['function']);
        $column = (string) ($aggregate['column'] ?? '*');
        $alias = (string) ($aggregate['alias'] ?? strtolower($function_name));
        $column_sql = '*' === $column ? '*' : query_builder_quote_identifier_path($column);

        return sprintf(
            '%s(%s) AS %s',
            $function_name,
            $column_sql,
            query_builder_quote_identifier($alias)
        );
    }

    $columns = $state['columns'] ?? [];
    if (! is_array($columns) || empty($columns)) {
        return '*';
    }

    $compiled_columns = [];

    foreach ($columns as $column) {
        if (! is_array($column)) {
            throw new \InvalidArgumentException('Invalid select column payload.');
        }

        $type = (string) ($column['type'] ?? '');

        if ('column' === $type) {
            $compiled_columns[] = query_builder_compile_column_token((string) ($column['value'] ?? ''));

            continue;
        }

        if ('raw' === $type) {
            $expression = $column['expression'] ?? null;
            if (! $expression instanceof QueryExpression) {
                throw new \InvalidArgumentException('Invalid select raw expression payload.');
            }

            $compiled_columns[] = query_builder_compile_expression_sql($expression, $bindings);

            continue;
        }

        if ('subquery' === $type) {
            $query = $column['query'] ?? null;
            $alias = $column['alias'] ?? null;
            if (! $query instanceof QueryBuilder || ! is_string($alias) || harbor_is_blank($alias)) {
                throw new \InvalidArgumentException('Invalid select subquery payload.');
            }

            $compiled_sub = query_builder_compile($query, $driver);
            $compiled_columns[] = sprintf(
                '(%s) AS %s',
                $compiled_sub['statement'],
                query_builder_quote_identifier($alias)
            );

            foreach ($compiled_sub['bindings'] as $binding) {
                $bindings[] = $binding;
            }

            continue;
        }

        throw new \InvalidArgumentException(sprintf('Unsupported select column type "%s".', $type));
    }

    return implode(', ', $compiled_columns);
}

/**
 * @param array<string, mixed> $state
 * @param array<int, mixed>    $bindings
 */
function query_builder_compile_select_from(array $state, string $driver, array &$bindings): string
{
    $from_sub = $state['from_sub'] ?? null;
    if (is_array($from_sub)) {
        $query = $from_sub['query'] ?? null;
        $alias = $from_sub['alias'] ?? null;
        if (! $query instanceof QueryBuilder || ! is_string($alias) || harbor_is_blank($alias)) {
            throw new \InvalidArgumentException('Invalid from_sub() payload.');
        }

        $compiled_sub = query_builder_compile($query, $driver);
        foreach ($compiled_sub['bindings'] as $binding) {
            $bindings[] = $binding;
        }

        return sprintf(
            '(%s) AS %s',
            $compiled_sub['statement'],
            query_builder_quote_identifier($alias)
        );
    }

    $table = $state['table'] ?? null;
    if (! is_string($table) || harbor_is_blank($table)) {
        throw new \InvalidArgumentException('Query requires table source.');
    }

    $sql = query_builder_quote_identifier_path($table);

    $alias = $state['alias'] ?? null;
    if (is_string($alias) && ! harbor_is_blank($alias)) {
        $sql .= ' AS '.query_builder_quote_identifier($alias);
    }

    return $sql;
}

/**
 * @param array<int, array<string, mixed>> $joins
 * @param array<int, mixed>                $bindings
 */
function query_builder_compile_joins(array $joins, string $driver, array &$bindings): string
{
    if (empty($joins)) {
        return '';
    }

    $compiled_joins = [];

    foreach ($joins as $join) {
        if (! is_array($join)) {
            throw new \InvalidArgumentException('Invalid join payload.');
        }

        $join_type = strtolower((string) ($join['join_type'] ?? ''));
        $kind = strtolower((string) ($join['kind'] ?? ''));

        $join_keyword = match ($join_type) {
            'inner' => 'INNER JOIN',
            'left' => 'LEFT JOIN',
            'right' => 'RIGHT JOIN',
            'cross' => 'CROSS JOIN',
            default => throw new \InvalidArgumentException(sprintf('Unsupported join type "%s".', $join_type)),
        };

        if ('table' === $kind) {
            $table = (string) ($join['table'] ?? '');
            if (harbor_is_blank($table)) {
                throw new \InvalidArgumentException('Join table cannot be empty.');
            }

            $join_sql = sprintf('%s %s', $join_keyword, query_builder_quote_identifier_path($table));

            if ('cross' !== $join_type) {
                $left = query_builder_quote_identifier_path((string) ($join['left'] ?? ''));
                $operator = query_builder_normalize_operator((string) ($join['operator'] ?? ''));
                $right = query_builder_quote_identifier_path((string) ($join['right'] ?? ''));

                $join_sql .= sprintf(' ON %s %s %s', $left, $operator, $right);
            }

            $compiled_joins[] = $join_sql;

            continue;
        }

        if ('subquery' === $kind) {
            $query = $join['query'] ?? null;
            $alias = $join['alias'] ?? null;
            if (! $query instanceof QueryBuilder || ! is_string($alias) || harbor_is_blank($alias)) {
                throw new \InvalidArgumentException('Invalid join_sub() payload.');
            }

            $compiled_sub = query_builder_compile($query, $driver);
            foreach ($compiled_sub['bindings'] as $binding) {
                $bindings[] = $binding;
            }

            $left = query_builder_quote_identifier_path((string) ($join['left'] ?? ''));
            $operator = query_builder_normalize_operator((string) ($join['operator'] ?? ''));
            $right = query_builder_quote_identifier_path((string) ($join['right'] ?? ''));

            $compiled_joins[] = sprintf(
                '%s (%s) AS %s ON %s %s %s',
                $join_keyword,
                $compiled_sub['statement'],
                query_builder_quote_identifier($alias),
                $left,
                $operator,
                $right
            );

            continue;
        }

        throw new \InvalidArgumentException(sprintf('Unsupported join kind "%s".', $kind));
    }

    return implode(' ', $compiled_joins);
}

/**
 * @param array<int, array<string, mixed>> $conditions
 * @param array<int, mixed>                $bindings
 */
function query_builder_compile_conditions(array $conditions, string $driver, array &$bindings): string
{
    if (empty($conditions)) {
        return '';
    }

    $compiled_conditions = [];

    foreach ($conditions as $index => $condition) {
        if (! is_array($condition)) {
            throw new \InvalidArgumentException('Invalid condition payload.');
        }

        $compiled_condition = query_builder_compile_condition($condition, $driver, $bindings);
        $boolean = strtoupper((string) ($condition['boolean'] ?? 'AND'));
        if (! in_array($boolean, ['AND', 'OR'], true)) {
            throw new \InvalidArgumentException(sprintf('Unsupported condition boolean "%s".', $boolean));
        }

        if (0 === $index) {
            $compiled_conditions[] = $compiled_condition;

            continue;
        }

        $compiled_conditions[] = sprintf('%s %s', $boolean, $compiled_condition);
    }

    return implode(' ', $compiled_conditions);
}

/**
 * @param array<string, mixed> $condition
 * @param array<int, mixed>    $bindings
 */
function query_builder_compile_condition(array $condition, string $driver, array &$bindings): string
{
    $type = (string) ($condition['type'] ?? '');

    if ('basic' === $type) {
        $column = query_builder_quote_identifier_path((string) ($condition['column'] ?? ''));
        $operator = query_builder_normalize_operator((string) ($condition['operator'] ?? ''));
        $bindings[] = $condition['value'] ?? null;

        return sprintf('%s %s ?', $column, $operator);
    }

    if ('column' === $type) {
        $left = query_builder_quote_identifier_path((string) ($condition['left'] ?? ''));
        $operator = query_builder_normalize_operator((string) ($condition['operator'] ?? ''));
        $right = query_builder_quote_identifier_path((string) ($condition['right'] ?? ''));

        return sprintf('%s %s %s', $left, $operator, $right);
    }

    if ('like' === $type) {
        $column = query_builder_quote_identifier_path((string) ($condition['column'] ?? ''));
        $case_sensitive = (bool) ($condition['case_sensitive'] ?? false);
        $bindings[] = $condition['pattern'] ?? '';

        if ($case_sensitive) {
            if ('sqlite' === $driver) {
                return sprintf('%s LIKE ? COLLATE BINARY', $column);
            }

            return sprintf('BINARY %s LIKE ?', $column);
        }

        return sprintf('%s LIKE ?', $column);
    }

    if ('in' === $type || 'not_in' === $type) {
        $column = query_builder_quote_identifier_path((string) ($condition['column'] ?? ''));
        $values = $condition['values'] ?? [];
        if (! is_array($values) || empty($values)) {
            throw new \InvalidArgumentException('IN condition requires at least one value.');
        }

        foreach ($values as $value) {
            $bindings[] = $value;
        }

        $operator = 'not_in' === $type ? 'NOT IN' : 'IN';
        $placeholders = implode(', ', array_fill(0, count($values), '?'));

        return sprintf('%s %s (%s)', $column, $operator, $placeholders);
    }

    if ('null' === $type) {
        $column = query_builder_quote_identifier_path((string) ($condition['column'] ?? ''));
        $not = (bool) ($condition['not'] ?? false);

        return sprintf('%s IS %sNULL', $column, $not ? 'NOT ' : '');
    }

    if ('between' === $type) {
        $column = query_builder_quote_identifier_path((string) ($condition['column'] ?? ''));
        $bindings[] = $condition['from'] ?? null;
        $bindings[] = $condition['to'] ?? null;

        return sprintf('%s BETWEEN ? AND ?', $column);
    }

    if ('raw' === $type) {
        $expression = $condition['expression'] ?? null;
        if (! $expression instanceof QueryExpression) {
            throw new \InvalidArgumentException('Invalid raw condition expression payload.');
        }

        $sql = query_builder_compile_expression_sql($expression, $bindings);

        return '('.$sql.')';
    }

    if ('group' === $type) {
        $nested_conditions = $condition['conditions'] ?? [];
        if (! is_array($nested_conditions) || empty($nested_conditions)) {
            throw new \InvalidArgumentException('where_group() condition cannot be empty.');
        }

        $nested_sql = query_builder_compile_conditions($nested_conditions, $driver, $bindings);

        return '('.$nested_sql.')';
    }

    if ('exists' === $type) {
        $query = $condition['query'] ?? null;
        if (! $query instanceof QueryBuilder) {
            throw new \InvalidArgumentException('Invalid exists condition payload.');
        }

        $compiled_sub = query_builder_compile($query, $driver);
        foreach ($compiled_sub['bindings'] as $binding) {
            $bindings[] = $binding;
        }

        $not = (bool) ($condition['not'] ?? false);

        return sprintf('%sEXISTS (%s)', $not ? 'NOT ' : '', $compiled_sub['statement']);
    }

    if ('in_sub' === $type) {
        $column = query_builder_quote_identifier_path((string) ($condition['column'] ?? ''));
        $query = $condition['query'] ?? null;
        if (! $query instanceof QueryBuilder) {
            throw new \InvalidArgumentException('Invalid in_sub condition payload.');
        }

        $compiled_sub = query_builder_compile($query, $driver);
        foreach ($compiled_sub['bindings'] as $binding) {
            $bindings[] = $binding;
        }

        $not = (bool) ($condition['not'] ?? false);

        return sprintf(
            '%s %s (%s)',
            $column,
            $not ? 'NOT IN' : 'IN',
            $compiled_sub['statement']
        );
    }

    if ('date_part' === $type) {
        $part = strtolower((string) ($condition['part'] ?? ''));
        $column = query_builder_quote_identifier_path((string) ($condition['column'] ?? ''));
        $operator = query_builder_normalize_operator((string) ($condition['operator'] ?? '='));

        $expression = match ($part) {
            'date' => sprintf('DATE(%s)', $column),
            'year' => 'sqlite' === $driver
                ? sprintf("CAST(STRFTIME('%%Y', %s) AS INTEGER)", $column)
                : sprintf('YEAR(%s)', $column),
            'month' => 'sqlite' === $driver
                ? sprintf("CAST(STRFTIME('%%m', %s) AS INTEGER)", $column)
                : sprintf('MONTH(%s)', $column),
            'day' => 'sqlite' === $driver
                ? sprintf("CAST(STRFTIME('%%d', %s) AS INTEGER)", $column)
                : sprintf('DAY(%s)', $column),
            default => throw new \InvalidArgumentException(
                sprintf('Unsupported date part "%s".', $part)
            ),
        };

        $bindings[] = $condition['value'] ?? null;

        return sprintf('%s %s ?', $expression, $operator);
    }

    throw new \InvalidArgumentException(sprintf('Unsupported condition type "%s".', $type));
}

/**
 * @param array<int, string> $groups
 */
function query_builder_compile_group_by(array $groups): string
{
    if (empty($groups)) {
        return '';
    }

    $compiled_groups = array_map('Harbor\Database\query_builder_quote_identifier_path', $groups);

    return 'GROUP BY '.implode(', ', $compiled_groups);
}

/**
 * @param array<int, array<string, mixed>> $orders
 */
function query_builder_compile_order_by(array $orders): string
{
    if (empty($orders)) {
        return '';
    }

    $compiled_orders = [];

    foreach ($orders as $order) {
        if (! is_array($order)) {
            throw new \InvalidArgumentException('Invalid order by payload.');
        }

        $type = (string) ($order['type'] ?? '');

        if ('basic' === $type) {
            $column = query_builder_quote_identifier_path((string) ($order['column'] ?? ''));
            $direction = strtoupper((string) ($order['direction'] ?? 'ASC'));
            if (! in_array($direction, ['ASC', 'DESC'], true)) {
                throw new \InvalidArgumentException(sprintf('Unsupported order direction "%s".', $direction));
            }

            $compiled_orders[] = sprintf('%s %s', $column, $direction);

            continue;
        }

        if ('raw' === $type) {
            $expression = $order['expression'] ?? null;
            if (! $expression instanceof QueryExpression) {
                throw new \InvalidArgumentException('Invalid order raw expression payload.');
            }

            if (false === empty($expression->bindings())) {
                throw new \InvalidArgumentException('order_by_raw() does not support bindings.');
            }

            $compiled_orders[] = $expression->sql();

            continue;
        }

        throw new \InvalidArgumentException(sprintf('Unsupported order by type "%s".', $type));
    }

    return 'ORDER BY '.implode(', ', $compiled_orders);
}

/**
 * @param array<string, mixed> $state
 *
 * @return array{statement: string, bindings: array<int, mixed>}
 */
function query_builder_compile_upsert(array $state, string $driver): array
{
    $table = (string) ($state['table'] ?? '');
    if (harbor_is_blank($table)) {
        throw new \InvalidArgumentException('Upsert query requires into() table.');
    }

    $upsert = $state['upsert'] ?? null;
    if (! is_array($upsert)) {
        throw new \InvalidArgumentException('Invalid upsert payload.');
    }

    $rows = $upsert['rows'] ?? [];
    if (! is_array($rows) || empty($rows)) {
        throw new \InvalidArgumentException('Upsert requires at least one row.');
    }

    [$columns, $normalized_rows] = query_builder_normalize_insert_rows($rows);

    $bindings = [];
    $value_chunks = [];
    foreach ($normalized_rows as $row) {
        $row_placeholders = [];
        foreach ($columns as $column) {
            $row_placeholders[] = '?';
            $bindings[] = $row[$column];
        }
        $value_chunks[] = '('.implode(', ', $row_placeholders).')';
    }

    $sql = sprintf(
        'INSERT INTO %s (%s) VALUES %s',
        query_builder_quote_identifier_path($table),
        implode(', ', array_map('Harbor\Database\query_builder_quote_identifier_path', $columns)),
        implode(', ', $value_chunks)
    );

    $conflict = $state['conflict'] ?? [];
    if (! is_array($conflict)) {
        $conflict = [];
    }

    if ('sqlite' === $driver) {
        $on_conflict = $conflict['on_conflict'] ?? $upsert['unique_by'] ?? [];
        if (! is_array($on_conflict) || empty($on_conflict)) {
            throw new \InvalidArgumentException('SQLite upsert requires on_conflict columns.');
        }

        $quoted_conflict_columns = array_map(
            'Harbor\Database\query_builder_quote_identifier_path',
            $on_conflict
        );
        $sql .= sprintf(' ON CONFLICT (%s)', implode(', ', $quoted_conflict_columns));

        $do_nothing = (bool) ($conflict['do_nothing'] ?? false);
        $update_columns = $conflict['do_update'] ?? $upsert['update_columns'] ?? [];

        if ($do_nothing || ! is_array($update_columns) || empty($update_columns)) {
            $sql .= ' DO NOTHING';
        } else {
            $assignments = [];
            foreach ($update_columns as $column) {
                if (! is_string($column) || harbor_is_blank($column)) {
                    throw new \InvalidArgumentException('SQLite upsert update columns must be non-empty strings.');
                }

                $quoted_column = query_builder_quote_identifier_path($column);
                $assignments[] = sprintf('%s = excluded.%s', $quoted_column, $quoted_column);
            }

            $sql .= ' DO UPDATE SET '.implode(', ', $assignments);
        }
    } else {
        $on_duplicate_update = $conflict['on_duplicate_key_update'] ?? $conflict['do_update'] ?? $upsert['update_columns'] ?? [];

        if (! is_array($on_duplicate_update) || empty($on_duplicate_update)) {
            throw new \InvalidArgumentException('MySQL upsert requires update columns.');
        }

        $assignments = [];
        foreach ($on_duplicate_update as $column) {
            if (! is_string($column) || harbor_is_blank($column)) {
                throw new \InvalidArgumentException('MySQL upsert update columns must be non-empty strings.');
            }

            $quoted_column = query_builder_quote_identifier_path($column);
            $assignments[] = sprintf('%s = VALUES(%s)', $quoted_column, $quoted_column);
        }

        $sql .= ' ON DUPLICATE KEY UPDATE '.implode(', ', $assignments);
    }

    $sql .= query_builder_compile_returning($state['returning'] ?? [], $driver);

    return [
        'statement' => $sql,
        'bindings' => $bindings,
    ];
}

/**
 * @return array{0: array<int, string>, 1: array<int, array<string, mixed>>}
 */
function query_builder_normalize_insert_rows(mixed $rows): array
{
    if (! is_array($rows) || empty($rows)) {
        throw new \InvalidArgumentException('Insert rows cannot be empty.');
    }

    $normalized_rows = [];
    $columns = [];

    foreach ($rows as $index => $row) {
        if (! is_array($row) || empty($row)) {
            throw new \InvalidArgumentException('Each insert row must be a non-empty array.');
        }

        $normalized_row = [];
        foreach ($row as $column => $value) {
            if (! is_string($column) || harbor_is_blank(trim($column))) {
                throw new \InvalidArgumentException('Insert row columns must be non-empty strings.');
            }

            $normalized_column = query_builder_validate_identifier_path($column, 'insert column');
            $normalized_row[$normalized_column] = $value;
        }

        if (0 === $index) {
            $columns = array_keys($normalized_row);
        } else {
            $row_columns = array_keys($normalized_row);
            if ($row_columns !== $columns) {
                throw new \InvalidArgumentException('All insert rows must contain identical column keys in the same order.');
            }
        }

        $normalized_rows[] = $normalized_row;
    }

    return [$columns, $normalized_rows];
}

/**
 * @return array{prefix: string, suffix: string}
 */
function query_builder_compile_lock(mixed $lock, string $driver): array
{
    if (! is_array($lock)) {
        return ['prefix' => '', 'suffix' => ''];
    }

    $prefix_expression = $lock['prefix'] ?? null;
    $suffix_expression = $lock['suffix'] ?? null;
    $mode = is_string($lock['mode'] ?? null) ? strtolower($lock['mode']) : null;

    $prefix = '';
    if ($prefix_expression instanceof QueryExpression) {
        if (false === empty($prefix_expression->bindings())) {
            throw new \InvalidArgumentException('lock_prefix() does not support bindings.');
        }

        $prefix = $prefix_expression->sql();
    }

    $suffix = '';
    if ($suffix_expression instanceof QueryExpression) {
        if (false === empty($suffix_expression->bindings())) {
            throw new \InvalidArgumentException('lock_suffix() does not support bindings.');
        }

        $suffix = $suffix_expression->sql();
    }

    if (harbor_is_blank($suffix) && ! harbor_is_null($mode)) {
        if ('sqlite' === $driver) {
            throw new \InvalidArgumentException('SQLite driver does not support row lock clauses.');
        }

        $suffix = match ($mode) {
            'for_update' => 'FOR UPDATE',
            'for_share' => 'LOCK IN SHARE MODE',
            default => throw new \InvalidArgumentException(sprintf('Unsupported lock mode "%s".', $mode)),
        };
    }

    return [
        'prefix' => trim($prefix),
        'suffix' => trim($suffix),
    ];
}

function query_builder_compile_returning(mixed $returning, string $driver): string
{
    if (! is_array($returning) || empty($returning)) {
        return '';
    }

    if (in_array($driver, ['mysql', 'mysqli'], true)) {
        throw new \InvalidArgumentException(
            sprintf('Driver "%s" does not support RETURNING clause.', $driver)
        );
    }

    $quoted_columns = [];
    foreach ($returning as $column) {
        if (! is_string($column) || harbor_is_blank(trim($column))) {
            throw new \InvalidArgumentException('Returning columns must be non-empty strings.');
        }

        $quoted_columns[] = query_builder_quote_identifier_path($column);
    }

    return ' RETURNING '.implode(', ', $quoted_columns);
}

/**
 * @param array<int, mixed> $bindings
 */
function query_builder_compile_expression_sql(mixed $expression, array &$bindings): string
{
    if (! $expression instanceof QueryExpression) {
        throw new \InvalidArgumentException('Raw expression payload must be a QueryExpression instance.');
    }

    foreach ($expression->bindings() as $binding) {
        $bindings[] = $binding;
    }

    return $expression->sql();
}

function query_builder_compile_column_token(string $token): string
{
    $normalized_token = trim($token);
    if (harbor_is_blank($normalized_token)) {
        throw new \InvalidArgumentException('Select column token cannot be empty.');
    }

    if (preg_match('/\s+as\s+/i', $normalized_token)) {
        $parts = preg_split('/\s+as\s+/i', $normalized_token);
        if (! is_array($parts) || 2 !== count($parts)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid aliased select column token "%s".', $token)
            );
        }

        $source = query_builder_quote_identifier_path(trim($parts[0]));
        $alias = query_builder_quote_identifier(trim($parts[1]));

        return sprintf('%s AS %s', $source, $alias);
    }

    return query_builder_quote_identifier_path($normalized_token);
}

function query_builder_resolve_driver(?string $driver = null): string
{
    if (is_string($driver) && ! harbor_is_blank(trim($driver))) {
        return query_builder_normalize_driver($driver);
    }

    if (function_exists('Harbor\Database\db_driver')) {
        /** @var string $resolved */
        $resolved = db_driver();

        return query_builder_normalize_driver($resolved);
    }

    return 'sqlite';
}

function query_builder_normalize_driver(string $driver): string
{
    $normalized_driver = strtolower(trim($driver));

    return match ($normalized_driver) {
        'sqlite', 'mysql', 'mysqli' => $normalized_driver,
        default => throw new \InvalidArgumentException(
            sprintf('Unsupported query compile driver "%s".', $driver)
        ),
    };
}

function query_builder_normalize_operator(string $operator): string
{
    $normalized_operator = strtoupper(trim($operator));
    $allowed_operators = [
        '=', '!=', '<>', '>', '<', '>=', '<=', 'LIKE', 'NOT LIKE',
    ];

    if (! in_array($normalized_operator, $allowed_operators, true)) {
        throw new \InvalidArgumentException(
            sprintf('Unsupported SQL operator "%s".', $operator)
        );
    }

    return $normalized_operator;
}

function query_builder_quote_identifier_path(string $identifier): string
{
    $normalized_identifier = query_builder_validate_identifier_path($identifier);

    if ('*' === $normalized_identifier) {
        return '*';
    }

    $parts = explode('.', $normalized_identifier);
    $quoted_parts = [];

    foreach ($parts as $index => $part) {
        if ('*' === $part && $index === count($parts) - 1) {
            $quoted_parts[] = '*';

            continue;
        }

        $quoted_parts[] = query_builder_quote_identifier($part);
    }

    return implode('.', $quoted_parts);
}

function query_builder_quote_identifier(string $identifier): string
{
    $normalized_identifier = trim($identifier);
    if (harbor_is_blank($normalized_identifier)) {
        throw new \InvalidArgumentException('Identifier cannot be empty.');
    }

    if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $normalized_identifier)) {
        throw new \InvalidArgumentException(
            sprintf('Identifier "%s" contains unsupported characters.', $identifier)
        );
    }

    return sprintf('`%s`', str_replace('`', '``', $normalized_identifier));
}

function query_builder_validate_identifier_path(string $identifier, string $label = 'identifier'): string
{
    $normalized_identifier = trim($identifier);
    if (harbor_is_blank($normalized_identifier)) {
        throw new \InvalidArgumentException(sprintf('%s cannot be empty.', ucfirst($label)));
    }

    if (str_contains($normalized_identifier, ' ')) {
        throw new \InvalidArgumentException(
            sprintf('%s "%s" cannot contain spaces.', ucfirst($label), $identifier)
        );
    }

    if ('*' === $normalized_identifier) {
        return $normalized_identifier;
    }

    $parts = explode('.', $normalized_identifier);

    foreach ($parts as $index => $part) {
        if ('*' === $part && $index === count($parts) - 1) {
            continue;
        }

        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $part)) {
            throw new \InvalidArgumentException(
                sprintf('%s "%s" contains unsupported characters.', ucfirst($label), $identifier)
            );
        }
    }

    return $normalized_identifier;
}

function query_builder_literal(mixed $value): string
{
    if (harbor_is_null($value)) {
        return 'NULL';
    }

    if (is_bool($value)) {
        return $value ? '1' : '0';
    }

    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }

    if (is_string($value)) {
        return "'".str_replace("'", "''", $value)."'";
    }

    if ($value instanceof \DateTimeInterface) {
        return "'".$value->format('Y-m-d H:i:s')."'";
    }

    if (is_object($value) && method_exists($value, '__toString')) {
        return "'".str_replace("'", "''", (string) $value)."'";
    }

    throw new \InvalidArgumentException(
        sprintf('Unsupported binding value type "%s".', get_debug_type($value))
    );
}
