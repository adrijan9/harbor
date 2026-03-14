<?php

declare(strict_types=1);

namespace Harbor\Pagination;

require_once __DIR__.'/../Database/db.php';

require_once __DIR__.'/../Database/QueryBuilder/QueryBuilder.php';

require_once __DIR__.'/PaginationOptionsBag.php';

require_once __DIR__.'/../Support/value.php';

use Harbor\Database\QueryBuilder\QueryBuilder;

use function Harbor\Database\db_array;
use function Harbor\Database\db_connect;
use function Harbor\Database\db_first;
use function Harbor\Support\harbor_is_blank;

/**
 * Class Pagination.
 */
abstract class Pagination
{
    private \mysqli|\PDO|null $connection = null;
    private ?PaginationOptionsBag $options = null;

    final public function set_connection(\mysqli|\PDO $connection): static
    {
        $this->connection = $connection;

        return $this;
    }

    final public function set_options(PaginationOptionsBag $options): static
    {
        $this->options = $options;

        return $this;
    }

    final public function paginate(
        int $page = 1,
        int $per_page = 15
    ): array {
        [$normalized_page, $normalized_per_page] = $this->normalize_pagination_input($page, $per_page);

        $active_connection = $this->resolve_active_connection();
        $base_query = $this->base_query();
        $this->assert_base_query($base_query);

        $total = $this->resolve_total_internal($active_connection, $base_query);
        $rows = $this->resolve_rows_internal($active_connection, $base_query, $normalized_page, $normalized_per_page);

        return $this->build_payload($rows, $total, $normalized_page, $normalized_per_page);
    }

    abstract protected function base_query(): QueryBuilder;

    protected function build_payload(array $rows, int $total, int $page, int $per_page): array
    {
        $last_page = max(1, (int) ceil($total / $per_page));
        $current_page = min($page, $last_page);

        $from = null;
        $to = null;
        if (! empty($rows)) {
            $from = (($current_page - 1) * $per_page) + 1;
            $to = $from + count($rows) - 1;
        }

        return [
            'data' => array_values($rows),
            'meta' => [
                'total' => $total,
                'per_page' => $per_page,
                'current_page' => $current_page,
                'last_page' => $last_page,
                'from' => $from,
                'to' => $to,
                'has_more' => $current_page < $last_page,
            ],
            'links' => $this->default_links($current_page, $last_page),
        ];
    }

    protected function max_per_page(): int
    {
        return $this->options()->max_per_page();
    }

    final protected function connection(): \mysqli|\PDO
    {
        return $this->resolve_active_connection();
    }

    final protected function options(): PaginationOptionsBag
    {
        if (! $this->options instanceof PaginationOptionsBag) {
            $this->options = PaginationOptionsBag::make();
        }

        return $this->options;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function normalize_pagination_input(int $page, int $per_page): array
    {
        if ($page < 1) {
            throw new \InvalidArgumentException('paginate() expects page >= 1.');
        }

        if ($per_page < 1) {
            throw new \InvalidArgumentException('paginate() expects per_page >= 1.');
        }

        $max_per_page = max(1, $this->max_per_page());
        $normalized_per_page = min($per_page, $max_per_page);

        return [$page, $normalized_per_page];
    }

    private function resolve_active_connection(): \mysqli|\PDO
    {
        if ($this->connection instanceof \PDO || $this->connection instanceof \mysqli) {
            return $this->connection;
        }

        return db_connect();
    }

    private function assert_base_query(QueryBuilder $base_query): void
    {
        if ('select' !== $base_query->type()) {
            throw new \InvalidArgumentException('Pagination base_query() must return a select QueryBuilder.');
        }

        $state = $base_query->state();

        $limit = $state['limit'] ?? null;
        if (is_int($limit)) {
            throw new \InvalidArgumentException('Pagination base_query() cannot include limit().');
        }

        $offset = $state['offset'] ?? null;
        if (is_int($offset)) {
            throw new \InvalidArgumentException('Pagination base_query() cannot include offset().');
        }
    }

    private function resolve_total_internal(\mysqli|\PDO $connection, QueryBuilder $base_query): int
    {
        $count_query = QueryBuilder::select()
            ->from_sub($base_query, 'base_rows')
            ->count('*', 'total_count')
        ;

        $row = db_first(
            $connection,
            $count_query->get_sql(),
            $count_query->get_bindings()
        );

        return max(0, (int) ($row['total_count'] ?? 0));
    }

    private function resolve_rows_internal(
        \mysqli|\PDO $connection,
        QueryBuilder $base_query,
        int $page,
        int $per_page
    ): array {
        $rows_query = clone $base_query;
        $rows_query->for_page($page, $per_page);

        return db_array(
            $connection,
            $rows_query->get_sql(),
            $rows_query->get_bindings()
        );
    }

    /**
     * @return array{first: string, prev: string, next: string, last: string}
     */
    private function default_links(int $current_page, int $last_page): array
    {
        $base_path = $this->options()->base_path();
        if (! is_string($base_path) || harbor_is_blank($base_path)) {
            return [
                'first' => '?page=1',
                'prev' => '?page='.max(1, $current_page - 1),
                'next' => '?page='.min($last_page, $current_page + 1),
                'last' => '?page='.$last_page,
            ];
        }

        $query = $this->options()->query();

        return [
            'first' => $this->build_page_url($base_path, $query, 1),
            'prev' => $this->build_page_url($base_path, $query, max(1, $current_page - 1)),
            'next' => $this->build_page_url($base_path, $query, min($last_page, $current_page + 1)),
            'last' => $this->build_page_url($base_path, $query, $last_page),
        ];
    }

    /**
     * @param array<string, mixed> $query
     */
    private function build_page_url(string $base_path, array $query, int $page): string
    {
        $query['page'] = $page;
        $query_string = http_build_query($query);

        if (harbor_is_blank($query_string)) {
            return $base_path;
        }

        return $base_path.'?'.$query_string;
    }
}
