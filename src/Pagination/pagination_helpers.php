<?php

declare(strict_types=1);

namespace Harbor\Pagination;

require_once __DIR__.'/../Database/Connection/db.php';

require_once __DIR__.'/../Database/QueryBuilder/QueryBuilder.php';

require_once __DIR__.'/PaginationOptionsBag.php';

require_once __DIR__.'/../Support/value.php';

require_once __DIR__.'/../Router/helpers/route_query.php';

use Harbor\Database\QueryBuilder\QueryBuilder;

use function Harbor\Database\db_array;
use function Harbor\Database\db_connect;
use function Harbor\Database\db_first;
use function Harbor\Router\route_query_int;
use function Harbor\Support\harbor_is_blank;

/** Public */
function pagination_paginate(
    QueryBuilder $base_query,
    ?int $page = null,
    int $per_page = 25,
    \mysqli|\PDO|null $connection = null,
    ?PaginationOptionsBag $options = null
): array {
    $resolved_options = pagination_internal_resolve_options($options);
    $resolved_page = pagination_internal_resolve_page($page);

    [$normalized_page, $normalized_per_page] = pagination_internal_normalize_input(
        $resolved_page,
        $per_page,
        pagination_internal_max_per_page($resolved_options)
    );

    pagination_internal_assert_base_query($base_query);

    $active_connection = pagination_internal_resolve_connection($connection);
    $total = pagination_internal_resolve_total($active_connection, $base_query);
    $rows = pagination_internal_resolve_rows(
        $active_connection,
        $base_query,
        $normalized_page,
        $normalized_per_page
    );

    return pagination_internal_build_payload(
        $rows,
        $total,
        $normalized_page,
        $normalized_per_page,
        $resolved_options
    );
}

/** Private */
function pagination_internal_resolve_options(?PaginationOptionsBag $options): PaginationOptionsBag
{
    if ($options instanceof PaginationOptionsBag) {
        return $options;
    }

    return PaginationOptionsBag::make();
}

function pagination_internal_resolve_page(?int $page): int
{
    if (is_int($page)) {
        return $page;
    }

    return route_query_int('page', 1);
}

/**
 * @return array{0: int, 1: int}
 */
function pagination_internal_normalize_input(int $page, int $per_page, int $max_per_page): array
{
    if ($page < 1) {
        throw new \InvalidArgumentException('paginate() expects page >= 1.');
    }

    if ($per_page < 1) {
        throw new \InvalidArgumentException('paginate() expects per_page >= 1.');
    }

    $normalized_per_page = min($per_page, $max_per_page);

    return [$page, $normalized_per_page];
}

function pagination_internal_max_per_page(PaginationOptionsBag $options): int
{
    return $options->max_per_page();
}

function pagination_internal_assert_base_query(QueryBuilder $base_query): void
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

function pagination_internal_resolve_connection(\mysqli|\PDO|null $connection): \mysqli|\PDO
{
    if ($connection instanceof \PDO || $connection instanceof \mysqli) {
        return $connection;
    }

    return db_connect();
}

function pagination_internal_resolve_total(\mysqli|\PDO $connection, QueryBuilder $base_query): int
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

function pagination_internal_resolve_rows(
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

function pagination_internal_build_payload(
    array $rows,
    int $total,
    int $page,
    int $per_page,
    PaginationOptionsBag $options
): array {
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
        'links' => pagination_internal_build_links($current_page, $last_page, $options),
    ];
}

/**
 * @return array{first: string, prev: string, next: string, last: string}
 */
function pagination_internal_build_links(int $current_page, int $last_page, PaginationOptionsBag $options): array
{
    $base_path = $options->base_path();
    $query = $options->query();

    if (! is_string($base_path) || harbor_is_blank($base_path)) {
        return [
            'first' => '?page=1',
            'prev' => '?page='.max(1, $current_page - 1),
            'next' => '?page='.min($last_page, $current_page + 1),
            'last' => '?page='.$last_page,
        ];
    }

    $normalized_base_path = trim($base_path);
    if (harbor_is_blank($normalized_base_path)) {
        return [
            'first' => '?page=1',
            'prev' => '?page='.max(1, $current_page - 1),
            'next' => '?page='.min($last_page, $current_page + 1),
            'last' => '?page='.$last_page,
        ];
    }

    return [
        'first' => pagination_internal_build_page_url($normalized_base_path, $query, 1),
        'prev' => pagination_internal_build_page_url($normalized_base_path, $query, max(1, $current_page - 1)),
        'next' => pagination_internal_build_page_url($normalized_base_path, $query, min($last_page, $current_page + 1)),
        'last' => pagination_internal_build_page_url($normalized_base_path, $query, $last_page),
    ];
}

/**
 * @param array<string, mixed> $query
 */
function pagination_internal_build_page_url(string $base_path, array $query, int $page): string
{
    $query['page'] = $page;
    $query_string = http_build_query($query);

    if (harbor_is_blank($query_string)) {
        return $base_path;
    }

    return $base_path.'?'.$query_string;
}
