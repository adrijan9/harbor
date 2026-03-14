<?php

declare(strict_types=1);

namespace Harbor\Database\QueryBuilder;

require_once __DIR__.'/../../Support/value.php';

require_once __DIR__.'/QueryExpression.php';

use function Harbor\Support\harbor_is_blank;

/**
 * Class QueryBuilder.
 */
final class QueryBuilder
{
    private ?string $table = null;
    private ?array $from_sub = null;
    private ?string $alias = null;

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $columns = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $joins = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $wheres = [];

    /**
     * @var array<int, string>
     */
    private array $groups = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $havings = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $orders = [];

    /**
     * @var array<int, array{all: bool, query: self}>
     */
    private array $unions = [];

    private ?int $limit = null;
    private ?int $offset = null;

    /**
     * @var null|array<string, mixed>
     */
    private ?array $aggregate = null;

    /**
     * @var array{mode: ?string, prefix: ?QueryExpression, suffix: ?QueryExpression}
     */
    private array $lock = [
        'mode' => null,
        'prefix' => null,
        'suffix' => null,
    ];

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $insert_rows = [];

    /**
     * @var null|array<string, mixed>
     */
    private ?array $upsert = null;

    /**
     * @var null|array<string, mixed>
     */
    private ?array $conflict = null;

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $updates = [];

    private bool $distinct = false;
    private bool $insert_ignore = false;
    private bool $allow_full_table = false;

    /**
     * @var array<int, string>
     */
    private array $returning = [];

    /**
     * @var null|array{driver: string, statement: string, bindings: array<int, mixed>}
     */
    private ?array $compiled_cache = null;

    private function __construct(
        private readonly string $type,
    ) {
        $this->assert_type($this->type);
    }

    public static function select(?string $table = null): self
    {
        $builder = new self('select');
        if (is_string($table) && ! harbor_is_blank(trim($table))) {
            $builder->from($table);
        }

        return $builder;
    }

    public static function insert(?string $table = null): self
    {
        $builder = new self('insert');
        if (is_string($table) && ! harbor_is_blank(trim($table))) {
            $builder->into($table);
        }

        return $builder;
    }

    public static function update(?string $table = null): self
    {
        $builder = new self('update');
        if (is_string($table) && ! harbor_is_blank(trim($table))) {
            $builder->table($table);
        }

        return $builder;
    }

    public static function delete(?string $table = null): self
    {
        $builder = new self('delete');
        if (is_string($table) && ! harbor_is_blank(trim($table))) {
            $builder->from($table);
        }

        return $builder;
    }

    public function table(string $table): self
    {
        $this->table = $this->normalize_table_name($table);
        $this->from_sub = null;
        $this->invalidate_cache();

        return $this;
    }

    public function from(string $table): self
    {
        $this->assert_builder_type(['select', 'delete'], 'from()');

        return $this->table($table);
    }

    public function into(string $table): self
    {
        $this->assert_builder_type(['insert'], 'into()');

        return $this->table($table);
    }

    public function as(string $alias): self
    {
        $this->alias = $this->normalize_alias_name($alias);
        $this->invalidate_cache();

        return $this;
    }

    public function when(bool|callable $condition, callable $then, ?callable $else = null): self
    {
        $resolved_condition = is_callable($condition)
            ? (bool) $condition($this)
            : $condition;

        if ($resolved_condition) {
            $result = $then($this);

            return $result instanceof self ? $result : $this;
        }

        if (is_callable($else)) {
            $result = $else($this);

            return $result instanceof self ? $result : $this;
        }

        return $this;
    }

    public function where(string $column, string $operator, mixed $value): self
    {
        return $this->add_where_condition('and', [
            'type' => 'basic',
            'column' => $this->normalize_identifier_reference($column, 'where column'),
            'operator' => $this->normalize_operator($operator),
            'value' => $value,
        ]);
    }

    public function or_where(string $column, string $operator, mixed $value): self
    {
        return $this->add_where_condition('or', [
            'type' => 'basic',
            'column' => $this->normalize_identifier_reference($column, 'where column'),
            'operator' => $this->normalize_operator($operator),
            'value' => $value,
        ]);
    }

    public function where_column(string $left, string $operator, string $right): self
    {
        return $this->add_where_condition('and', [
            'type' => 'column',
            'left' => $this->normalize_identifier_reference($left, 'where left column'),
            'operator' => $this->normalize_operator($operator),
            'right' => $this->normalize_identifier_reference($right, 'where right column'),
        ]);
    }

    public function or_where_column(string $left, string $operator, string $right): self
    {
        return $this->add_where_condition('or', [
            'type' => 'column',
            'left' => $this->normalize_identifier_reference($left, 'where left column'),
            'operator' => $this->normalize_operator($operator),
            'right' => $this->normalize_identifier_reference($right, 'where right column'),
        ]);
    }

    public function where_like(string $column, string $pattern, bool $case_sensitive = false): self
    {
        return $this->add_where_condition('and', [
            'type' => 'like',
            'column' => $this->normalize_identifier_reference($column, 'where like column'),
            'pattern' => $pattern,
            'case_sensitive' => $case_sensitive,
        ]);
    }

    public function or_where_like(string $column, string $pattern, bool $case_sensitive = false): self
    {
        return $this->add_where_condition('or', [
            'type' => 'like',
            'column' => $this->normalize_identifier_reference($column, 'where like column'),
            'pattern' => $pattern,
            'case_sensitive' => $case_sensitive,
        ]);
    }

    public function where_date(string $column, string $operator, string $date_ymd): self
    {
        return $this->add_date_where_condition('and', 'date', $column, $operator, $date_ymd);
    }

    public function or_where_date(string $column, string $operator, string $date_ymd): self
    {
        return $this->add_date_where_condition('or', 'date', $column, $operator, $date_ymd);
    }

    public function where_year(string $column, string $operator, int $year): self
    {
        return $this->add_date_where_condition('and', 'year', $column, $operator, $year);
    }

    public function or_where_year(string $column, string $operator, int $year): self
    {
        return $this->add_date_where_condition('or', 'year', $column, $operator, $year);
    }

    public function where_month(string $column, string $operator, int $month): self
    {
        if ($month < 1 || $month > 12) {
            throw new \InvalidArgumentException('where_month() expects month between 1 and 12.');
        }

        return $this->add_date_where_condition('and', 'month', $column, $operator, $month);
    }

    public function or_where_month(string $column, string $operator, int $month): self
    {
        if ($month < 1 || $month > 12) {
            throw new \InvalidArgumentException('or_where_month() expects month between 1 and 12.');
        }

        return $this->add_date_where_condition('or', 'month', $column, $operator, $month);
    }

    public function where_day(string $column, string $operator, int $day): self
    {
        if ($day < 1 || $day > 31) {
            throw new \InvalidArgumentException('where_day() expects day between 1 and 31.');
        }

        return $this->add_date_where_condition('and', 'day', $column, $operator, $day);
    }

    public function or_where_day(string $column, string $operator, int $day): self
    {
        if ($day < 1 || $day > 31) {
            throw new \InvalidArgumentException('or_where_day() expects day between 1 and 31.');
        }

        return $this->add_date_where_condition('or', 'day', $column, $operator, $day);
    }

    public function where_group(callable $callback): self
    {
        return $this->add_where_group_condition('and', $callback);
    }

    public function or_where_group(callable $callback): self
    {
        return $this->add_where_group_condition('or', $callback);
    }

    /**
     * @param array<int, mixed> $values
     */
    public function where_in(string $column, array $values): self
    {
        return $this->add_in_where_condition('and', 'in', $column, $values);
    }

    /**
     * @param array<int, mixed> $values
     */
    public function or_where_in(string $column, array $values): self
    {
        return $this->add_in_where_condition('or', 'in', $column, $values);
    }

    /**
     * @param array<int, mixed> $values
     */
    public function where_not_in(string $column, array $values): self
    {
        return $this->add_in_where_condition('and', 'not_in', $column, $values);
    }

    /**
     * @param array<int, mixed> $values
     */
    public function or_where_not_in(string $column, array $values): self
    {
        return $this->add_in_where_condition('or', 'not_in', $column, $values);
    }

    public function where_null(string $column): self
    {
        return $this->add_where_condition('and', [
            'type' => 'null',
            'column' => $this->normalize_identifier_reference($column, 'where null column'),
            'not' => false,
        ]);
    }

    public function or_where_null(string $column): self
    {
        return $this->add_where_condition('or', [
            'type' => 'null',
            'column' => $this->normalize_identifier_reference($column, 'where null column'),
            'not' => false,
        ]);
    }

    public function where_not_null(string $column): self
    {
        return $this->add_where_condition('and', [
            'type' => 'null',
            'column' => $this->normalize_identifier_reference($column, 'where not null column'),
            'not' => true,
        ]);
    }

    public function or_where_not_null(string $column): self
    {
        return $this->add_where_condition('or', [
            'type' => 'null',
            'column' => $this->normalize_identifier_reference($column, 'where not null column'),
            'not' => true,
        ]);
    }

    public function where_between(string $column, mixed $from, mixed $to): self
    {
        return $this->add_where_condition('and', [
            'type' => 'between',
            'column' => $this->normalize_identifier_reference($column, 'where between column'),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function or_where_between(string $column, mixed $from, mixed $to): self
    {
        return $this->add_where_condition('or', [
            'type' => 'between',
            'column' => $this->normalize_identifier_reference($column, 'where between column'),
            'from' => $from,
            'to' => $to,
        ]);
    }

    /**
     * @param array<int, mixed> $bindings
     */
    public function where_raw(QueryExpression|string $sql, array $bindings = []): self
    {
        $expression = $this->normalize_expression($sql, $bindings, 'where_raw()');

        return $this->add_where_condition('and', [
            'type' => 'raw',
            'expression' => $expression,
        ]);
    }

    public function where_exists(self $sub): self
    {
        return $this->add_where_condition('and', [
            'type' => 'exists',
            'query' => $sub,
            'not' => false,
        ]);
    }

    public function or_where_exists(self $sub): self
    {
        return $this->add_where_condition('or', [
            'type' => 'exists',
            'query' => $sub,
            'not' => false,
        ]);
    }

    public function where_not_exists(self $sub): self
    {
        return $this->add_where_condition('and', [
            'type' => 'exists',
            'query' => $sub,
            'not' => true,
        ]);
    }

    public function or_where_not_exists(self $sub): self
    {
        return $this->add_where_condition('or', [
            'type' => 'exists',
            'query' => $sub,
            'not' => true,
        ]);
    }

    public function where_in_sub(string $column, self $sub): self
    {
        return $this->add_where_condition('and', [
            'type' => 'in_sub',
            'column' => $this->normalize_identifier_reference($column, 'where in sub column'),
            'query' => $sub,
            'not' => false,
        ]);
    }

    public function or_where_in_sub(string $column, self $sub): self
    {
        return $this->add_where_condition('or', [
            'type' => 'in_sub',
            'column' => $this->normalize_identifier_reference($column, 'where in sub column'),
            'query' => $sub,
            'not' => false,
        ]);
    }

    public function where_not_in_sub(string $column, self $sub): self
    {
        return $this->add_where_condition('and', [
            'type' => 'in_sub',
            'column' => $this->normalize_identifier_reference($column, 'where in sub column'),
            'query' => $sub,
            'not' => true,
        ]);
    }

    public function or_where_not_in_sub(string $column, self $sub): self
    {
        return $this->add_where_condition('or', [
            'type' => 'in_sub',
            'column' => $this->normalize_identifier_reference($column, 'where in sub column'),
            'query' => $sub,
            'not' => true,
        ]);
    }

    public function group_by(mixed ...$columns): self
    {
        $this->assert_builder_type(['select'], 'group_by()');
        foreach ($this->normalize_mixed_string_list($columns, 'group by columns') as $column) {
            $this->groups[] = $this->normalize_identifier_reference($column, 'group by column');
        }
        $this->invalidate_cache();

        return $this;
    }

    public function having(string $column, string $operator, mixed $value): self
    {
        $this->assert_builder_type(['select'], 'having()');

        $this->havings[] = [
            'type' => 'basic',
            'column' => $this->normalize_identifier_reference($column, 'having column'),
            'operator' => $this->normalize_operator($operator),
            'value' => $value,
        ];
        $this->invalidate_cache();

        return $this;
    }

    /**
     * @param array<int, mixed> $bindings
     */
    public function having_raw(QueryExpression|string $sql, array $bindings = []): self
    {
        $this->assert_builder_type(['select'], 'having_raw()');

        $this->havings[] = [
            'type' => 'raw',
            'expression' => $this->normalize_expression($sql, $bindings, 'having_raw()'),
        ];
        $this->invalidate_cache();

        return $this;
    }

    public function order_by(string $column, string $direction = 'asc'): self
    {
        $this->assert_builder_type(['select'], 'order_by()');

        $this->orders[] = [
            'type' => 'basic',
            'column' => $this->normalize_identifier_reference($column, 'order by column'),
            'direction' => $this->normalize_direction($direction),
        ];
        $this->invalidate_cache();

        return $this;
    }

    public function order_by_raw(QueryExpression|string $sql): self
    {
        $this->assert_builder_type(['select'], 'order_by_raw()');

        $this->orders[] = [
            'type' => 'raw',
            'expression' => $this->normalize_expression($sql, [], 'order_by_raw()'),
        ];
        $this->invalidate_cache();

        return $this;
    }

    public function limit(int $limit): self
    {
        if ($limit < 0) {
            throw new \InvalidArgumentException('limit() expects a non-negative integer.');
        }

        $this->limit = $limit;
        $this->invalidate_cache();

        return $this;
    }

    public function offset(int $offset): self
    {
        if ($offset < 0) {
            throw new \InvalidArgumentException('offset() expects a non-negative integer.');
        }

        $this->offset = $offset;
        $this->invalidate_cache();

        return $this;
    }

    public function for_page(int $page, int $per_page): self
    {
        if ($page < 1) {
            throw new \InvalidArgumentException('for_page() expects page >= 1.');
        }

        if ($per_page < 1) {
            throw new \InvalidArgumentException('for_page() expects per_page >= 1.');
        }

        $this->limit = $per_page;
        $this->offset = ($page - 1) * $per_page;
        $this->invalidate_cache();

        return $this;
    }

    public function columns(mixed ...$columns): self
    {
        $this->assert_builder_type(['select'], 'columns()');

        foreach ($this->normalize_mixed_string_list($columns, 'select columns') as $column) {
            $this->columns[] = [
                'type' => 'column',
                'value' => $column,
            ];
        }
        $this->invalidate_cache();

        return $this;
    }

    /**
     * @param array<int, mixed> $bindings
     */
    public function select_raw(QueryExpression|string $sql, array $bindings = []): self
    {
        $this->assert_builder_type(['select'], 'select_raw()');

        $this->columns[] = [
            'type' => 'raw',
            'expression' => $this->normalize_expression($sql, $bindings, 'select_raw()'),
        ];
        $this->invalidate_cache();

        return $this;
    }

    public function distinct(bool $value = true): self
    {
        $this->assert_builder_type(['select'], 'distinct()');

        $this->distinct = $value;
        $this->invalidate_cache();

        return $this;
    }

    public function count(string $column = '*', string $alias = 'count'): self
    {
        return $this->set_aggregate('COUNT', $column, $alias);
    }

    public function sum(string $column, string $alias = 'sum'): self
    {
        return $this->set_aggregate('SUM', $column, $alias);
    }

    public function avg(string $column, string $alias = 'avg'): self
    {
        return $this->set_aggregate('AVG', $column, $alias);
    }

    public function min(string $column, string $alias = 'min'): self
    {
        return $this->set_aggregate('MIN', $column, $alias);
    }

    public function max(string $column, string $alias = 'max'): self
    {
        return $this->set_aggregate('MAX', $column, $alias);
    }

    public function select_sub(self $sub, string $alias): self
    {
        $this->assert_builder_type(['select'], 'select_sub()');

        $this->columns[] = [
            'type' => 'subquery',
            'query' => $sub,
            'alias' => $this->normalize_alias_name($alias),
        ];
        $this->invalidate_cache();

        return $this;
    }

    public function from_sub(self $sub, string $alias): self
    {
        $this->assert_builder_type(['select'], 'from_sub()');

        $this->from_sub = [
            'query' => $sub,
            'alias' => $this->normalize_alias_name($alias),
        ];
        $this->table = null;
        $this->alias = null;
        $this->invalidate_cache();

        return $this;
    }

    public function join(string $table, string $left, string $operator, string $right): self
    {
        return $this->add_join('inner', $table, $left, $operator, $right);
    }

    public function left_join(string $table, string $left, string $operator, string $right): self
    {
        return $this->add_join('left', $table, $left, $operator, $right);
    }

    public function right_join(string $table, string $left, string $operator, string $right): self
    {
        return $this->add_join('right', $table, $left, $operator, $right);
    }

    public function cross_join(string $table): self
    {
        $this->assert_builder_type(['select'], 'cross_join()');

        $this->joins[] = [
            'join_type' => 'cross',
            'kind' => 'table',
            'table' => $this->normalize_table_name($table),
        ];
        $this->invalidate_cache();

        return $this;
    }

    public function join_sub(self $sub, string $alias, string $left, string $operator, string $right): self
    {
        return $this->add_join_sub('inner', $sub, $alias, $left, $operator, $right);
    }

    public function left_join_sub(self $sub, string $alias, string $left, string $operator, string $right): self
    {
        return $this->add_join_sub('left', $sub, $alias, $left, $operator, $right);
    }

    public function right_join_sub(self $sub, string $alias, string $left, string $operator, string $right): self
    {
        return $this->add_join_sub('right', $sub, $alias, $left, $operator, $right);
    }

    public function union(self $query): self
    {
        return $this->add_union($query, false);
    }

    public function union_all(self $query): self
    {
        return $this->add_union($query, true);
    }

    public function lock_for_update(): self
    {
        $this->assert_builder_type(['select'], 'lock_for_update()');
        $this->lock['mode'] = 'for_update';
        $this->invalidate_cache();

        return $this;
    }

    public function lock_for_share(): self
    {
        $this->assert_builder_type(['select'], 'lock_for_share()');
        $this->lock['mode'] = 'for_share';
        $this->invalidate_cache();

        return $this;
    }

    public function lock_prefix(QueryExpression|string $fragment): self
    {
        $this->assert_builder_type(['select'], 'lock_prefix()');
        $this->lock['prefix'] = $this->normalize_expression($fragment, [], 'lock_prefix()');
        $this->invalidate_cache();

        return $this;
    }

    public function lock_suffix(QueryExpression|string $fragment): self
    {
        $this->assert_builder_type(['select'], 'lock_suffix()');
        $this->lock['suffix'] = $this->normalize_expression($fragment, [], 'lock_suffix()');
        $this->invalidate_cache();

        return $this;
    }

    public function clear_lock(): self
    {
        $this->lock = [
            'mode' => null,
            'prefix' => null,
            'suffix' => null,
        ];
        $this->invalidate_cache();

        return $this;
    }

    /**
     * @param array<string, mixed> $row
     */
    public function values(array $row): self
    {
        $this->assert_builder_type(['insert'], 'values()');

        if (! empty($this->insert_rows) || ! harbor_is_blank((string) ($this->upsert['mode'] ?? ''))) {
            throw new \InvalidArgumentException('values() cannot be mixed with rows() or upsert().');
        }

        $this->insert_rows = [$this->normalize_insert_row($row)];
        $this->invalidate_cache();

        return $this;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public function rows(array $rows): self
    {
        $this->assert_builder_type(['insert'], 'rows()');

        if (! empty($this->insert_rows) || ! harbor_is_blank((string) ($this->upsert['mode'] ?? ''))) {
            throw new \InvalidArgumentException('rows() cannot be mixed with values() or upsert().');
        }

        if (empty($rows)) {
            throw new \InvalidArgumentException('rows() expects at least one row.');
        }

        $normalized_rows = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                throw new \InvalidArgumentException('rows() expects row arrays.');
            }

            $normalized_rows[] = $this->normalize_insert_row($row);
        }

        $this->insert_rows = $normalized_rows;
        $this->invalidate_cache();

        return $this;
    }

    public function ignore(bool $value = true): self
    {
        $this->assert_builder_type(['insert'], 'ignore()');
        $this->insert_ignore = $value;
        $this->invalidate_cache();

        return $this;
    }

    public function returning(mixed ...$columns): self
    {
        $this->assert_builder_type(['insert', 'update', 'delete'], 'returning()');

        foreach ($this->normalize_mixed_string_list($columns, 'returning columns') as $column) {
            $this->returning[] = $this->normalize_identifier_reference($column, 'returning column');
        }
        $this->invalidate_cache();

        return $this;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, string>               $unique_by
     * @param array<int, string>               $update_columns
     */
    public function upsert(array $rows, array $unique_by, array $update_columns = []): self
    {
        $this->assert_builder_type(['insert'], 'upsert()');

        if (! empty($this->insert_rows)) {
            throw new \InvalidArgumentException('upsert() cannot be mixed with values() or rows().');
        }

        if (empty($rows)) {
            throw new \InvalidArgumentException('upsert() expects at least one row.');
        }

        $normalized_rows = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                throw new \InvalidArgumentException('upsert() rows must be arrays.');
            }

            $normalized_rows[] = $this->normalize_insert_row($row);
        }

        $this->upsert = [
            'mode' => 'upsert',
            'rows' => $normalized_rows,
            'unique_by' => $this->normalize_identifier_list($unique_by, 'upsert unique_by columns'),
            'update_columns' => $this->normalize_identifier_list($update_columns, 'upsert update columns', false),
        ];
        $this->invalidate_cache();

        return $this;
    }

    /**
     * @param array<int, string> $columns
     */
    public function on_conflict(array $columns): self
    {
        $this->assert_builder_type(['insert'], 'on_conflict()');
        $this->conflict['on_conflict'] = $this->normalize_identifier_list($columns, 'on_conflict columns');
        $this->invalidate_cache();

        return $this;
    }

    public function do_nothing(): self
    {
        $this->assert_builder_type(['insert'], 'do_nothing()');
        $this->conflict['do_nothing'] = true;
        $this->invalidate_cache();

        return $this;
    }

    /**
     * @param array<int, string> $columns
     */
    public function do_update(array $columns): self
    {
        $this->assert_builder_type(['insert'], 'do_update()');
        $this->conflict['do_update'] = $this->normalize_identifier_list($columns, 'do_update columns');
        $this->invalidate_cache();

        return $this;
    }

    /**
     * @param array<int, string> $columns
     */
    public function on_duplicate_key_update(array $columns): self
    {
        $this->assert_builder_type(['insert'], 'on_duplicate_key_update()');
        $this->conflict['on_duplicate_key_update'] = $this->normalize_identifier_list($columns, 'on_duplicate_key_update columns');
        $this->invalidate_cache();

        return $this;
    }

    public function set(string $column, mixed $value): self
    {
        $this->assert_builder_type(['update'], 'set()');
        $this->updates[] = [
            'type' => 'set',
            'column' => $this->normalize_identifier_reference($column, 'set column'),
            'value' => $value,
        ];
        $this->invalidate_cache();

        return $this;
    }

    /**
     * @param array<string, mixed> $values
     */
    public function set_many(array $values): self
    {
        $this->assert_builder_type(['update'], 'set_many()');

        if (empty($values)) {
            throw new \InvalidArgumentException('set_many() expects at least one column value pair.');
        }

        foreach ($values as $column => $value) {
            if (! is_string($column)) {
                throw new \InvalidArgumentException('set_many() column keys must be strings.');
            }

            $this->set($column, $value);
        }

        return $this;
    }

    public function increment(string $column, float|int $by = 1): self
    {
        $this->assert_builder_type(['update'], 'increment()');
        $this->updates[] = [
            'type' => 'increment',
            'column' => $this->normalize_identifier_reference($column, 'increment column'),
            'by' => $by,
        ];
        $this->invalidate_cache();

        return $this;
    }

    public function decrement(string $column, float|int $by = 1): self
    {
        $this->assert_builder_type(['update'], 'decrement()');
        $this->updates[] = [
            'type' => 'decrement',
            'column' => $this->normalize_identifier_reference($column, 'decrement column'),
            'by' => $by,
        ];
        $this->invalidate_cache();

        return $this;
    }

    public function allow_full_table(bool $allow = true): self
    {
        $this->allow_full_table = $allow;
        $this->invalidate_cache();

        return $this;
    }

    public function get_sql(?string $driver = null): string
    {
        $this->ensure_compiler_loaded();

        $compiled = \Harbor\Database\query_builder_compile($this, $driver);
        $this->compiled_cache = [
            'driver' => (string) $compiled['driver'],
            'statement' => (string) $compiled['statement'],
            'bindings' => is_array($compiled['bindings']) ? $compiled['bindings'] : [],
        ];

        return $this->compiled_cache['statement'];
    }

    /**
     * @return array<int, mixed>
     */
    public function get_bindings(): array
    {
        $this->ensure_compiler_loaded();

        if (is_array($this->compiled_cache) && array_key_exists('bindings', $this->compiled_cache)) {
            return array_values($this->compiled_cache['bindings']);
        }

        $compiled = \Harbor\Database\query_builder_compile($this, null);
        $this->compiled_cache = [
            'driver' => (string) $compiled['driver'],
            'statement' => (string) $compiled['statement'],
            'bindings' => is_array($compiled['bindings']) ? $compiled['bindings'] : [],
        ];

        return array_values($this->compiled_cache['bindings']);
    }

    public function build(?string $driver = null): string
    {
        $this->ensure_compiler_loaded();

        $statement = $this->get_sql($driver);
        $bindings = $this->get_bindings();

        return \Harbor\Database\query_builder_interpolate_bindings($statement, $bindings);
    }

    public function type(): string
    {
        return $this->type;
    }

    /**
     * @return array<string, mixed>
     */
    public function state(): array
    {
        return [
            'type' => $this->type,
            'table' => $this->table,
            'from_sub' => $this->from_sub,
            'alias' => $this->alias,
            'columns' => $this->columns,
            'joins' => $this->joins,
            'wheres' => $this->wheres,
            'groups' => $this->groups,
            'havings' => $this->havings,
            'orders' => $this->orders,
            'unions' => $this->unions,
            'limit' => $this->limit,
            'offset' => $this->offset,
            'aggregate' => $this->aggregate,
            'lock' => $this->lock,
            'insert_rows' => $this->insert_rows,
            'upsert' => $this->upsert,
            'conflict' => $this->conflict,
            'updates' => $this->updates,
            'distinct' => $this->distinct,
            'ignore' => $this->insert_ignore,
            'returning' => $this->returning,
            'allow_full_table' => $this->allow_full_table,
        ];
    }

    private function set_aggregate(string $function_name, string $column, string $alias): self
    {
        $this->assert_builder_type(['select'], strtolower($function_name).'()');
        $normalized_alias = $this->normalize_alias_name($alias);
        $normalized_column = '*' === trim($column)
            ? '*'
            : $this->normalize_identifier_reference($column, strtolower($function_name).' column');

        $this->aggregate = [
            'function' => strtoupper($function_name),
            'column' => $normalized_column,
            'alias' => $normalized_alias,
        ];
        $this->invalidate_cache();

        return $this;
    }

    private function add_join(string $join_type, string $table, string $left, string $operator, string $right): self
    {
        $this->assert_builder_type(['select'], 'join()');
        $normalized_join_type = strtolower(trim($join_type));
        if (! in_array($normalized_join_type, ['inner', 'left', 'right'], true)) {
            throw new \InvalidArgumentException(sprintf('Unsupported join type "%s".', $join_type));
        }

        $this->joins[] = [
            'join_type' => $normalized_join_type,
            'kind' => 'table',
            'table' => $this->normalize_table_name($table),
            'left' => $this->normalize_identifier_reference($left, 'join left column'),
            'operator' => $this->normalize_operator($operator),
            'right' => $this->normalize_identifier_reference($right, 'join right column'),
        ];
        $this->invalidate_cache();

        return $this;
    }

    private function add_join_sub(
        string $join_type,
        self $sub,
        string $alias,
        string $left,
        string $operator,
        string $right
    ): self {
        $this->assert_builder_type(['select'], 'join_sub()');
        $normalized_join_type = strtolower(trim($join_type));
        if (! in_array($normalized_join_type, ['inner', 'left', 'right'], true)) {
            throw new \InvalidArgumentException(sprintf('Unsupported join type "%s".', $join_type));
        }

        $this->joins[] = [
            'join_type' => $normalized_join_type,
            'kind' => 'subquery',
            'query' => $sub,
            'alias' => $this->normalize_alias_name($alias),
            'left' => $this->normalize_identifier_reference($left, 'join sub left column'),
            'operator' => $this->normalize_operator($operator),
            'right' => $this->normalize_identifier_reference($right, 'join sub right column'),
        ];
        $this->invalidate_cache();

        return $this;
    }

    private function add_union(self $query, bool $all): self
    {
        $this->assert_builder_type(['select'], 'union()');
        if ('select' !== $query->type()) {
            throw new \InvalidArgumentException('union() and union_all() only accept select query builders.');
        }

        $this->unions[] = [
            'all' => $all,
            'query' => $query,
        ];
        $this->invalidate_cache();

        return $this;
    }

    private function add_where_group_condition(string $boolean, callable $callback): self
    {
        $this->assert_builder_type(['select', 'update', 'delete'], 'where_group()');
        $nested = self::select();
        $result = $callback($nested);
        if ($result instanceof self) {
            $nested = $result;
        }

        $nested_state = $nested->state();
        $nested_conditions = is_array($nested_state['wheres'] ?? null) ? $nested_state['wheres'] : [];
        if (empty($nested_conditions)) {
            throw new \InvalidArgumentException('where_group() callback must add at least one where condition.');
        }

        return $this->add_where_condition($boolean, [
            'type' => 'group',
            'conditions' => $nested_conditions,
        ]);
    }

    private function add_date_where_condition(
        string $boolean,
        string $part,
        string $column,
        string $operator,
        mixed $value
    ): self {
        return $this->add_where_condition($boolean, [
            'type' => 'date_part',
            'part' => $part,
            'column' => $this->normalize_identifier_reference($column, sprintf('where %s column', $part)),
            'operator' => $this->normalize_operator($operator),
            'value' => $value,
        ]);
    }

    /**
     * @param array<int, mixed> $values
     */
    private function add_in_where_condition(string $boolean, string $type, string $column, array $values): self
    {
        if (empty($values)) {
            throw new \InvalidArgumentException(sprintf('%s() expects at least one value.', $type));
        }

        return $this->add_where_condition($boolean, [
            'type' => $type,
            'column' => $this->normalize_identifier_reference($column, sprintf('%s column', $type)),
            'values' => array_values($values),
        ]);
    }

    /**
     * @param array<string, mixed> $condition
     */
    private function add_where_condition(string $boolean, array $condition): self
    {
        $this->assert_builder_type(['select', 'update', 'delete'], 'where');
        $condition['boolean'] = $this->normalize_boolean($boolean);
        $this->wheres[] = $condition;
        $this->invalidate_cache();

        return $this;
    }

    /**
     * @param array<int, mixed> $bindings
     */
    private function normalize_expression(QueryExpression|string $sql, array $bindings, string $context): QueryExpression
    {
        if ($sql instanceof QueryExpression) {
            if (! empty($bindings)) {
                throw new \InvalidArgumentException(sprintf('%s cannot accept bindings when QueryExpression instance is provided.', $context));
            }

            return $sql;
        }

        return QueryExpression::raw($sql, $bindings);
    }

    /**
     * @param array<int, mixed> $values
     */
    private function normalize_mixed_string_list(array $values, string $label): array
    {
        $normalized = [];

        foreach ($values as $value) {
            if (is_array($value)) {
                $normalized = array_merge($normalized, $this->normalize_mixed_string_list($value, $label));

                continue;
            }

            if (! is_string($value)) {
                throw new \InvalidArgumentException(sprintf('%s must contain only strings.', ucfirst($label)));
            }

            $trimmed = trim($value);
            if (harbor_is_blank($trimmed)) {
                throw new \InvalidArgumentException(sprintf('%s cannot contain empty strings.', ucfirst($label)));
            }

            $normalized[] = $trimmed;
        }

        return $normalized;
    }

    /**
     * @param array<int, string> $columns
     *
     * @return array<int, string>
     */
    private function normalize_identifier_list(array $columns, string $label, bool $allow_empty = true): array
    {
        if (! $allow_empty && empty($columns)) {
            throw new \InvalidArgumentException(sprintf('%s cannot be empty.', ucfirst($label)));
        }

        $normalized = [];
        foreach ($columns as $column) {
            if (! is_string($column)) {
                throw new \InvalidArgumentException(sprintf('%s must contain only strings.', ucfirst($label)));
            }

            $normalized[] = $this->normalize_identifier_reference($column, $label);
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function normalize_insert_row(array $row): array
    {
        if (empty($row)) {
            throw new \InvalidArgumentException('Insert row cannot be empty.');
        }

        $normalized_row = [];

        foreach ($row as $column => $value) {
            if (! is_string($column)) {
                throw new \InvalidArgumentException('Insert row keys must be strings.');
            }

            $normalized_column = $this->normalize_identifier_reference($column, 'insert column');
            $normalized_row[$normalized_column] = $value;
        }

        return $normalized_row;
    }

    private function normalize_table_name(string $table): string
    {
        return $this->normalize_identifier_reference($table, 'table name');
    }

    private function normalize_alias_name(string $alias): string
    {
        $normalized_alias = trim($alias);
        if (harbor_is_blank($normalized_alias)) {
            throw new \InvalidArgumentException('Alias cannot be empty.');
        }

        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $normalized_alias)) {
            throw new \InvalidArgumentException(
                sprintf('Alias "%s" contains unsupported characters.', $alias)
            );
        }

        return $normalized_alias;
    }

    private function normalize_identifier_reference(string $identifier, string $label): string
    {
        $normalized_identifier = trim($identifier);
        if (harbor_is_blank($normalized_identifier)) {
            throw new \InvalidArgumentException(sprintf('%s cannot be empty.', ucfirst($label)));
        }

        if (str_contains($normalized_identifier, ' ')) {
            throw new \InvalidArgumentException(
                sprintf('%s "%s" cannot contain spaces. Use raw expression methods for SQL expressions.', ucfirst($label), $identifier)
            );
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

    private function normalize_operator(string $operator): string
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

    private function normalize_direction(string $direction): string
    {
        $normalized_direction = strtoupper(trim($direction));
        if (! in_array($normalized_direction, ['ASC', 'DESC'], true)) {
            throw new \InvalidArgumentException('Order direction must be "asc" or "desc".');
        }

        return $normalized_direction;
    }

    private function normalize_boolean(string $boolean): string
    {
        $normalized_boolean = strtoupper(trim($boolean));
        if (! in_array($normalized_boolean, ['AND', 'OR'], true)) {
            throw new \InvalidArgumentException(sprintf('Unsupported boolean operator "%s".', $boolean));
        }

        return $normalized_boolean;
    }

    /**
     * @param array<int, string> $allowed_types
     */
    private function assert_builder_type(array $allowed_types, string $method_name): void
    {
        if (! in_array($this->type, $allowed_types, true)) {
            throw new \InvalidArgumentException(
                sprintf('%s cannot be called for "%s" query builder.', $method_name, $this->type)
            );
        }
    }

    private function assert_type(string $type): void
    {
        if (! in_array($type, ['select', 'insert', 'update', 'delete'], true)) {
            throw new \InvalidArgumentException(
                sprintf('Unsupported query builder type "%s".', $type)
            );
        }
    }

    private function invalidate_cache(): void
    {
        $this->compiled_cache = null;
    }

    private function ensure_compiler_loaded(): void
    {
        if (! function_exists('Harbor\Database\query_builder_compile')) {
            require_once __DIR__.'/query_compile.php';
        }
    }
}
