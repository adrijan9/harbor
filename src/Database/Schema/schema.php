<?php

declare(strict_types=1);

namespace Harbor\Database;

require_once __DIR__.'/../../Support/value.php';

require_once __DIR__.'/Column.php';

require_once __DIR__.'/ForeignKey.php';

require_once __DIR__.'/schema_compile.php';

use Harbor\Database\Schema\Column;
use Harbor\Database\Schema\ForeignKey;

use function Harbor\Support\harbor_is_blank;

/** Public */
/**
 * @return array<string, mixed>
 */
function schema_builder_alter(string $table): array
{
    return schema_builder_make('alter', $table);
}

/**
 * @return array<string, mixed>
 */
function schema_builder_create(string $table, bool $if_not_exists = false): array
{
    $builder = schema_builder_make('create', $table);
    $builder['if_not_exists'] = $if_not_exists;

    return $builder;
}

/**
 * @return array<string, mixed>
 */
function schema_builder_drop(string $table, bool $if_exists = true): array
{
    $builder = schema_builder_make('drop', $table);
    $builder['if_exists'] = $if_exists;

    return $builder;
}

/**
 * @return array<string, mixed>
 */
function schema_builder_rename(string $from, string $to): array
{
    $builder = schema_builder_make('rename', $from);
    $builder['target'] = schema_compile_validate_identifier($to, 'target table name');

    return $builder;
}

/**
 * @param array<string, mixed> $builder
 *
 * @return array<string, mixed>
 */
function schema_add_column(array $builder, string $name, Column $column): array
{
    $column_name = schema_compile_validate_identifier($name, 'column name');

    return schema_builder_push_operation($builder, [
        'type' => 'add_column',
        'name' => $column_name,
        'column' => $column->to_array(),
    ]);
}

/**
 * @param array<string, mixed> $builder
 *
 * @return array<string, mixed>
 */
function schema_change_column(array $builder, string $name, Column $column): array
{
    $column_name = schema_compile_validate_identifier($name, 'column name');

    return schema_builder_push_operation($builder, [
        'type' => 'change_column',
        'name' => $column_name,
        'column' => $column->to_array(),
    ]);
}

/**
 * @param array<string, mixed> $builder
 *
 * @return array<string, mixed>
 */
function schema_drop_column(array $builder, string $name): array
{
    $column_name = schema_compile_validate_identifier($name, 'column name');

    return schema_builder_push_operation($builder, [
        'type' => 'drop_column',
        'name' => $column_name,
    ]);
}

/**
 * @param array<string, mixed> $builder
 *
 * @return array<string, mixed>
 */
function schema_rename_column(array $builder, string $from, string $to): array
{
    $source_column = schema_compile_validate_identifier($from, 'source column name');
    $target_column = schema_compile_validate_identifier($to, 'target column name');

    return schema_builder_push_operation($builder, [
        'type' => 'rename_column',
        'from' => $source_column,
        'to' => $target_column,
    ]);
}

/**
 * @param array<string, mixed> $builder
 * @param array<int, string>   $columns
 *
 * @return array<string, mixed>
 */
function schema_add_primary(array $builder, array $columns, ?string $name = null): array
{
    $normalized_columns = schema_compile_normalize_identifier_list($columns, 'primary key columns');
    $normalized_name = null;

    if (is_string($name) && ! harbor_is_blank(trim($name))) {
        $normalized_name = schema_compile_validate_identifier($name, 'primary key name');
    }

    return schema_builder_push_operation($builder, [
        'type' => 'add_primary',
        'name' => $normalized_name,
        'columns' => $normalized_columns,
    ]);
}

/**
 * @param array<string, mixed> $builder
 *
 * @return array<string, mixed>
 */
function schema_drop_primary(array $builder, ?string $name = null): array
{
    $normalized_name = null;

    if (is_string($name) && ! harbor_is_blank(trim($name))) {
        $normalized_name = schema_compile_validate_identifier($name, 'primary key name');
    }

    return schema_builder_push_operation($builder, [
        'type' => 'drop_primary',
        'name' => $normalized_name,
    ]);
}

/**
 * @param array<string, mixed> $builder
 * @param array<int, string>   $columns
 *
 * @return array<string, mixed>
 */
function schema_add_unique(array $builder, string $name, array $columns): array
{
    $normalized_name = schema_compile_validate_identifier($name, 'unique index name');
    $normalized_columns = schema_compile_normalize_identifier_list($columns, 'unique index columns');

    return schema_builder_push_operation($builder, [
        'type' => 'add_unique',
        'name' => $normalized_name,
        'columns' => $normalized_columns,
    ]);
}

/**
 * @param array<string, mixed> $builder
 *
 * @return array<string, mixed>
 */
function schema_drop_unique(array $builder, string $name): array
{
    $normalized_name = schema_compile_validate_identifier($name, 'unique index name');

    return schema_builder_push_operation($builder, [
        'type' => 'drop_unique',
        'name' => $normalized_name,
    ]);
}

/**
 * @param array<string, mixed> $builder
 * @param array<int, string>   $columns
 *
 * @return array<string, mixed>
 */
function schema_add_index(
    array $builder,
    string $name,
    array $columns,
    bool $unique = false,
    bool $if_not_exists = false
): array {
    $normalized_name = schema_compile_validate_identifier($name, 'index name');
    $normalized_columns = schema_compile_normalize_identifier_list($columns, 'index columns');

    return schema_builder_push_operation($builder, [
        'type' => 'add_index',
        'name' => $normalized_name,
        'columns' => $normalized_columns,
        'unique' => $unique,
        'if_not_exists' => $if_not_exists,
    ]);
}

/**
 * @param array<string, mixed> $builder
 *
 * @return array<string, mixed>
 */
function schema_drop_index(array $builder, string $name, bool $if_exists = false): array
{
    $normalized_name = schema_compile_validate_identifier($name, 'index name');

    return schema_builder_push_operation($builder, [
        'type' => 'drop_index',
        'name' => $normalized_name,
        'if_exists' => $if_exists,
    ]);
}

/**
 * @param array<string, mixed> $builder
 *
 * @return array<string, mixed>
 */
function schema_add_foreign(array $builder, ForeignKey $foreign_key): array
{
    return schema_builder_push_operation($builder, [
        'type' => 'add_foreign',
        'foreign_key' => $foreign_key->to_array(),
    ]);
}

/**
 * @param array<string, mixed> $builder
 *
 * @return array<string, mixed>
 */
function schema_drop_foreign(array $builder, string $name): array
{
    $normalized_name = schema_compile_validate_identifier($name, 'foreign key name');

    return schema_builder_push_operation($builder, [
        'type' => 'drop_foreign',
        'name' => $normalized_name,
    ]);
}

/**
 * @param array<string, mixed> $builder
 *
 * @return array<int, string>
 */
function schema_statements(array $builder, ?string $driver = null): array
{
    $resolved_driver = schema_resolve_driver($driver);

    return schema_compile_statements($builder, $resolved_driver);
}

/**
 * @param array<string, mixed> $builder
 */
function schema_execute(\mysqli|\PDO $connection, array $builder, ?string $driver = null): bool
{
    $resolved_driver = schema_resolve_connection_driver($connection, $driver);
    $version_payload = schema_resolve_connection_version_payload($connection, $resolved_driver);
    $statements = schema_compile_statements($builder, $resolved_driver, $version_payload);

    if (empty($statements)) {
        return true;
    }

    if ($connection instanceof \PDO) {
        return schema_execute_pdo_statements($connection, $statements, $resolved_driver);
    }

    return schema_execute_mysqli_statements($connection, $statements, $resolved_driver);
}

/** Private */
/**
 * @return array<string, mixed>
 */
function schema_builder_make(string $action, string $table): array
{
    return [
        'action' => schema_compile_validate_identifier($action, 'schema action'),
        'table' => schema_compile_validate_identifier($table, 'table name'),
        'target' => null,
        'if_exists' => false,
        'if_not_exists' => false,
        'operations' => [],
    ];
}

/**
 * @param array<string, mixed> $builder
 * @param array<string, mixed> $operation
 *
 * @return array<string, mixed>
 */
function schema_builder_push_operation(array $builder, array $operation): array
{
    $normalized_builder = schema_assert_builder($builder);
    $normalized_builder['operations'][] = $operation;

    return $normalized_builder;
}

/**
 * @param array<string, mixed> $builder
 *
 * @return array<string, mixed>
 */
function schema_assert_builder(array $builder): array
{
    if (! is_string($builder['action'] ?? null) || harbor_is_blank(trim((string) $builder['action']))) {
        throw new \InvalidArgumentException('Invalid schema builder: missing action.');
    }

    if (! is_string($builder['table'] ?? null) || harbor_is_blank(trim((string) $builder['table']))) {
        throw new \InvalidArgumentException('Invalid schema builder: missing table.');
    }

    $operations = $builder['operations'] ?? [];
    if (! is_array($operations)) {
        throw new \InvalidArgumentException('Invalid schema builder: operations must be an array.');
    }

    return $builder;
}

function schema_resolve_driver(?string $driver = null): string
{
    if (is_string($driver) && ! harbor_is_blank(trim($driver))) {
        return schema_compile_normalize_driver($driver);
    }

    return schema_compile_normalize_driver(db_driver());
}

function schema_resolve_connection_driver(\mysqli|\PDO $connection, ?string $driver = null): string
{
    if (is_string($driver) && ! harbor_is_blank(trim($driver))) {
        return schema_compile_normalize_driver($driver);
    }

    if ($connection instanceof \mysqli) {
        return 'mysqli';
    }

    $driver_name = strtolower((string) $connection->getAttribute(\PDO::ATTR_DRIVER_NAME));

    return schema_compile_normalize_driver($driver_name);
}

/**
 * @return array{
 *   driver: string,
 *   version: string,
 *   major: int,
 *   minor: int,
 *   patch: int
 * }
 */
function schema_resolve_connection_version_payload(\mysqli|\PDO $connection, string $driver): array
{
    $version_string = '0.0.0';

    if ($connection instanceof \PDO) {
        if ('sqlite' === $driver) {
            $statement = $connection->query('SELECT sqlite_version()');
            $result = $statement instanceof \PDOStatement ? $statement->fetchColumn() : null;

            if (is_string($result) && ! harbor_is_blank($result)) {
                $version_string = $result;
            }
        } elseif ('mysql' === $driver) {
            $statement = $connection->query('SELECT VERSION()');
            $result = $statement instanceof \PDOStatement ? $statement->fetchColumn() : null;

            if (is_string($result) && ! harbor_is_blank($result)) {
                $version_string = $result;
            }
        }
    } elseif ('mysqli' === $driver) {
        $result = $connection->server_info;

        if (is_string($result) && ! harbor_is_blank($result)) {
            $version_string = $result;
        }
    }

    return schema_normalize_version_payload($driver, $version_string);
}

/**
 * @return array{
 *   driver: string,
 *   version: string,
 *   major: int,
 *   minor: int,
 *   patch: int
 * }
 */
function schema_normalize_version_payload(string $driver, string $version_string): array
{
    $major = 0;
    $minor = 0;
    $patch = 0;

    if (preg_match('/(\d+)\.(\d+)\.(\d+)/', $version_string, $matches)) {
        $major = (int) $matches[1];
        $minor = (int) $matches[2];
        $patch = (int) $matches[3];
    }

    return [
        'driver' => $driver,
        'version' => $version_string,
        'major' => $major,
        'minor' => $minor,
        'patch' => $patch,
    ];
}

/**
 * @param array<int, string> $statements
 */
function schema_execute_pdo_statements(\PDO $connection, array $statements, string $driver): bool
{
    $owns_transaction = false;

    if (! $connection->inTransaction()) {
        $connection->beginTransaction();
        $owns_transaction = true;
    }

    try {
        foreach ($statements as $index => $statement) {
            db_execute($connection, $statement);
        }
    } catch (\Throwable $throwable) {
        if ($owns_transaction && $connection->inTransaction()) {
            $connection->rollBack();
        }

        schema_throw_execution_error($driver, $index ?? 0, $statement ?? '', $throwable);
    }

    if ($owns_transaction && $connection->inTransaction()) {
        $connection->commit();
    }

    return true;
}

/**
 * @param array<int, string> $statements
 */
function schema_execute_mysqli_statements(\mysqli $connection, array $statements, string $driver): bool
{
    $owns_transaction = false;

    try {
        $owns_transaction = $connection->begin_transaction();
    } catch (\Throwable) {
        $owns_transaction = false;
    }

    try {
        foreach ($statements as $index => $statement) {
            db_execute($connection, $statement);
        }
    } catch (\Throwable $throwable) {
        if ($owns_transaction) {
            try {
                $connection->rollback();
            } catch (\Throwable) {
            }
        }

        schema_throw_execution_error($driver, $index ?? 0, $statement ?? '', $throwable);
    }

    if ($owns_transaction) {
        $connection->commit();
    }

    return true;
}

function schema_throw_execution_error(string $driver, int $statement_index, string $statement, \Throwable $throwable): never
{
    throw new \RuntimeException(
        sprintf(
            'Schema execution failed on driver "%s" at statement #%d: %s. Error: %s',
            $driver,
            $statement_index + 1,
            $statement,
            $throwable->getMessage()
        ),
        previous: $throwable
    );
}
