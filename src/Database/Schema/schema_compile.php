<?php

declare(strict_types=1);

namespace Harbor\Database;

require_once __DIR__.'/../../Support/value.php';

use function Harbor\Support\harbor_is_blank;
use function Harbor\Support\harbor_is_null;

/** Public */
/**
 * @param null|array<string, mixed> $version_payload
 *
 * @return array<int, string>
 */
function schema_compile_statements(array $builder, string $driver, ?array $version_payload = null): array
{
    $normalized_builder = schema_compile_internal_normalize_builder($builder);
    $normalized_driver = schema_compile_internal_normalize_driver($driver);

    return match ($normalized_builder['action']) {
        'create' => schema_compile_internal_create_statements($normalized_builder, $normalized_driver, $version_payload),
        'alter' => schema_compile_internal_alter_statements($normalized_builder, $normalized_driver, $version_payload),
        'drop' => [schema_compile_internal_drop_table_statement($normalized_builder, $normalized_driver)],
        'rename' => [schema_compile_internal_rename_table_statement($normalized_builder)],
        default => throw new \InvalidArgumentException(
            sprintf('Unsupported schema action "%s".', (string) $normalized_builder['action'])
        ),
    };
}

/** Private */
/**
 * @param array<string, mixed> $builder
 *
 * @return array{
 *   action: string,
 *   table: string,
 *   target: ?string,
 *   if_exists: bool,
 *   if_not_exists: bool,
 *   operations: array<int, array<string, mixed>>
 * }
 */
function schema_compile_internal_normalize_builder(array $builder): array
{
    $action = schema_compile_internal_validate_identifier((string) ($builder['action'] ?? ''), 'schema action');
    $table = schema_compile_internal_validate_identifier((string) ($builder['table'] ?? ''), 'table name');

    $target = null;
    if (array_key_exists('target', $builder) && is_string($builder['target'])) {
        $target = schema_compile_internal_validate_identifier($builder['target'], 'target table name');
    }

    $operations = $builder['operations'] ?? [];
    if (! is_array($operations)) {
        throw new \InvalidArgumentException('Schema builder operations must be an array.');
    }

    return [
        'action' => $action,
        'table' => $table,
        'target' => $target,
        'if_exists' => (bool) ($builder['if_exists'] ?? false),
        'if_not_exists' => (bool) ($builder['if_not_exists'] ?? false),
        'operations' => schema_compile_internal_normalize_operations($operations),
    ];
}

/**
 * @param array<int, mixed> $operations
 *
 * @return array<int, array<string, mixed>>
 */
function schema_compile_internal_normalize_operations(array $operations): array
{
    $normalized = [];

    foreach ($operations as $operation) {
        if (! is_array($operation)) {
            throw new \InvalidArgumentException('Schema builder operation must be an array.');
        }

        $type = schema_compile_internal_validate_identifier((string) ($operation['type'] ?? ''), 'operation type');
        $operation['type'] = $type;
        $normalized[] = $operation;
    }

    return $normalized;
}

function schema_compile_internal_normalize_driver(string $driver): string
{
    $normalized_driver = strtolower(trim($driver));

    return match ($normalized_driver) {
        'sqlite', 'mysql', 'mysqli' => $normalized_driver,
        default => throw new \InvalidArgumentException(
            sprintf('Unsupported schema compile driver "%s".', $driver)
        ),
    };
}

/**
 * @param array{
 *   action: string,
 *   table: string,
 *   target: ?string,
 *   if_exists: bool,
 *   if_not_exists: bool,
 *   operations: array<int, array<string, mixed>>
 * } $builder
 * @param null|array<string, mixed> $version_payload
 *
 * @return array<int, string>
 */
function schema_compile_internal_create_statements(array $builder, string $driver, ?array $version_payload = null): array
{
    $table_name = $builder['table'];
    $quoted_table = schema_compile_internal_quote_identifier($table_name);

    $column_definitions = [];
    $table_constraints = [];
    $statements = [];
    $index_registry = [];
    $index_statements = [];
    $explicit_primary_columns = null;
    $implicit_primary_columns = [];

    foreach ($builder['operations'] as $operation) {
        $type = (string) $operation['type'];

        if ('add_column' === $type) {
            $column_name = schema_compile_internal_validate_identifier((string) ($operation['name'] ?? ''), 'column name');
            $column = schema_compile_internal_column_payload($operation['column'] ?? null);

            $compiled_column = schema_compile_internal_column_definition(
                table_name: $table_name,
                column_name: $column_name,
                column: $column,
                driver: $driver,
                operation_type: 'create',
                version_payload: $version_payload
            );

            $column_definitions[] = $compiled_column['definition'];
            if ($compiled_column['primary_inline']) {
                $implicit_primary_columns[] = $column_name;
            } elseif ($compiled_column['implicit_primary']) {
                $implicit_primary_columns[] = $column_name;
            }

            foreach ($compiled_column['implicit_index_operations'] as $implicit_index_operation) {
                $index_statement = schema_compile_internal_index_statement(
                    operation: $implicit_index_operation,
                    table_name: $table_name,
                    driver: $driver,
                    index_registry: $index_registry,
                    version_payload: $version_payload
                );

                if (! harbor_is_null($index_statement)) {
                    $index_statements[] = $index_statement;
                }
            }

            continue;
        }

        if ('add_primary' === $type) {
            $explicit_primary_columns = schema_compile_internal_normalize_identifier_list(
                $operation['columns'] ?? [],
                'primary key columns'
            );

            continue;
        }

        if ('add_foreign' === $type) {
            $foreign_key = schema_compile_internal_foreign_key_payload($operation['foreign_key'] ?? null);
            $table_constraints[] = schema_compile_internal_create_table_foreign_constraint(
                foreign_key: $foreign_key,
                owner_table: $table_name,
                driver: $driver
            );

            continue;
        }

        if ('add_index' === $type || 'add_unique' === $type) {
            $index_statement = schema_compile_internal_index_statement(
                operation: $operation,
                table_name: $table_name,
                driver: $driver,
                index_registry: $index_registry,
                version_payload: $version_payload
            );

            if (! harbor_is_null($index_statement)) {
                $index_statements[] = $index_statement;
            }

            continue;
        }
    }

    if (empty($column_definitions)) {
        throw new \InvalidArgumentException('Create schema builder must contain at least one added column.');
    }

    if (! harbor_is_null($explicit_primary_columns) && ! empty($implicit_primary_columns)) {
        if (schema_compile_internal_identifier_lists_equal($explicit_primary_columns, $implicit_primary_columns)) {
            $implicit_primary_columns = [];
        } else {
            throw new \InvalidArgumentException('Primary key conflict between explicit and column-defined primary keys.');
        }
    }

    $resolved_primary_columns = ! harbor_is_null($explicit_primary_columns)
        ? $explicit_primary_columns
        : $implicit_primary_columns;

    if (! empty($resolved_primary_columns) && ! schema_compile_internal_primary_is_inline_single($column_definitions, $resolved_primary_columns)) {
        $quoted_primary_columns = array_map(
            static fn (string $column): string => schema_compile_internal_quote_identifier($column),
            $resolved_primary_columns
        );
        $table_constraints[] = 'PRIMARY KEY ('.implode(', ', $quoted_primary_columns).')';
    }

    $create_keyword = $builder['if_not_exists'] ? 'CREATE TABLE IF NOT EXISTS' : 'CREATE TABLE';
    $statements[] = sprintf(
        '%s %s (%s)',
        $create_keyword,
        $quoted_table,
        implode(', ', array_merge($column_definitions, $table_constraints))
    );

    return array_merge($statements, $index_statements);
}

/**
 * @param array{
 *   action: string,
 *   table: string,
 *   target: ?string,
 *   if_exists: bool,
 *   if_not_exists: bool,
 *   operations: array<int, array<string, mixed>>
 * } $builder
 * @param null|array<string, mixed> $version_payload
 *
 * @return array<int, string>
 */
function schema_compile_internal_alter_statements(array $builder, string $driver, ?array $version_payload = null): array
{
    $table_name = $builder['table'];
    $quoted_table = schema_compile_internal_quote_identifier($table_name);
    $statements = [];
    $index_registry = [];

    foreach ($builder['operations'] as $operation) {
        $type = (string) $operation['type'];

        if ('add_column' === $type) {
            $column_name = schema_compile_internal_validate_identifier((string) ($operation['name'] ?? ''), 'column name');
            $column = schema_compile_internal_column_payload($operation['column'] ?? null);

            $compiled_column = schema_compile_internal_column_definition(
                table_name: $table_name,
                column_name: $column_name,
                column: $column,
                driver: $driver,
                operation_type: 'alter',
                version_payload: $version_payload
            );

            $statements[] = sprintf('ALTER TABLE %s ADD COLUMN %s', $quoted_table, $compiled_column['definition']);

            foreach ($compiled_column['implicit_index_operations'] as $implicit_index_operation) {
                $index_statement = schema_compile_internal_index_statement(
                    operation: $implicit_index_operation,
                    table_name: $table_name,
                    driver: $driver,
                    index_registry: $index_registry,
                    version_payload: $version_payload
                );

                if (! harbor_is_null($index_statement)) {
                    $statements[] = $index_statement;
                }
            }

            continue;
        }

        if ('change_column' === $type) {
            if ('sqlite' === $driver) {
                throw new \InvalidArgumentException('SQLite driver does not support ALTER TABLE MODIFY COLUMN.');
            }

            $column_name = schema_compile_internal_validate_identifier((string) ($operation['name'] ?? ''), 'column name');
            $column = schema_compile_internal_column_payload($operation['column'] ?? null);

            $compiled_column = schema_compile_internal_column_definition(
                table_name: $table_name,
                column_name: $column_name,
                column: $column,
                driver: $driver,
                operation_type: 'change',
                version_payload: $version_payload
            );

            $statements[] = sprintf('ALTER TABLE %s MODIFY COLUMN %s', $quoted_table, $compiled_column['definition']);

            foreach ($compiled_column['implicit_index_operations'] as $implicit_index_operation) {
                $index_statement = schema_compile_internal_index_statement(
                    operation: $implicit_index_operation,
                    table_name: $table_name,
                    driver: $driver,
                    index_registry: $index_registry,
                    version_payload: $version_payload
                );

                if (! harbor_is_null($index_statement)) {
                    $statements[] = $index_statement;
                }
            }

            continue;
        }

        if ('drop_column' === $type) {
            $column_name = schema_compile_internal_validate_identifier((string) ($operation['name'] ?? ''), 'column name');

            if ('sqlite' === $driver) {
                schema_compile_internal_assert_sqlite_feature_version(
                    feature_name: 'DROP COLUMN',
                    minimum_major: 3,
                    minimum_minor: 35,
                    minimum_patch: 0,
                    version_payload: $version_payload
                );
            }

            $statements[] = sprintf(
                'ALTER TABLE %s DROP COLUMN %s',
                $quoted_table,
                schema_compile_internal_quote_identifier($column_name)
            );

            continue;
        }

        if ('rename_column' === $type) {
            $from = schema_compile_internal_validate_identifier((string) ($operation['from'] ?? ''), 'source column name');
            $to = schema_compile_internal_validate_identifier((string) ($operation['to'] ?? ''), 'target column name');

            if ('sqlite' === $driver) {
                schema_compile_internal_assert_sqlite_feature_version(
                    feature_name: 'RENAME COLUMN',
                    minimum_major: 3,
                    minimum_minor: 25,
                    minimum_patch: 0,
                    version_payload: $version_payload
                );
            }

            $statements[] = sprintf(
                'ALTER TABLE %s RENAME COLUMN %s TO %s',
                $quoted_table,
                schema_compile_internal_quote_identifier($from),
                schema_compile_internal_quote_identifier($to)
            );

            continue;
        }

        if ('add_primary' === $type) {
            if ('sqlite' === $driver) {
                throw new \InvalidArgumentException('SQLite driver does not support ALTER TABLE ADD PRIMARY KEY.');
            }

            $columns = schema_compile_internal_normalize_identifier_list(
                $operation['columns'] ?? [],
                'primary key columns'
            );

            $quoted_columns = array_map(
                static fn (string $column): string => schema_compile_internal_quote_identifier($column),
                $columns
            );

            $key = '__primary__';
            $signature = [
                'type' => 'add_primary',
                'columns' => implode(',', $columns),
            ];
            schema_compile_internal_register_index_signature($index_registry, $key, $signature);

            $statements[] = sprintf('ALTER TABLE %s ADD PRIMARY KEY (%s)', $quoted_table, implode(', ', $quoted_columns));

            continue;
        }

        if ('drop_primary' === $type) {
            if ('sqlite' === $driver) {
                throw new \InvalidArgumentException('SQLite driver does not support ALTER TABLE DROP PRIMARY KEY.');
            }

            $key = '__primary__';
            $signature = [
                'type' => 'drop_primary',
                'columns' => '',
            ];
            schema_compile_internal_register_index_signature($index_registry, $key, $signature);

            $statements[] = sprintf('ALTER TABLE %s DROP PRIMARY KEY', $quoted_table);

            continue;
        }

        if ('add_foreign' === $type) {
            $foreign_key = schema_compile_internal_foreign_key_payload($operation['foreign_key'] ?? null);

            if ('sqlite' === $driver) {
                throw new \InvalidArgumentException('SQLite driver does not support ALTER TABLE ADD CONSTRAINT FOREIGN KEY.');
            }

            $statements[] = schema_compile_internal_alter_foreign_add_statement(
                table_name: $table_name,
                foreign_key: $foreign_key,
                driver: $driver
            );

            continue;
        }

        if ('drop_foreign' === $type) {
            $foreign_name = schema_compile_internal_validate_identifier((string) ($operation['name'] ?? ''), 'foreign key name');

            if ('sqlite' === $driver) {
                throw new \InvalidArgumentException('SQLite driver does not support ALTER TABLE DROP FOREIGN KEY.');
            }

            $statements[] = sprintf(
                'ALTER TABLE %s DROP FOREIGN KEY %s',
                $quoted_table,
                schema_compile_internal_quote_identifier($foreign_name)
            );

            continue;
        }

        if ('add_unique' === $type || 'add_index' === $type) {
            $index_statement = schema_compile_internal_index_statement(
                operation: $operation,
                table_name: $table_name,
                driver: $driver,
                index_registry: $index_registry,
                version_payload: $version_payload
            );

            if (! harbor_is_null($index_statement)) {
                $statements[] = $index_statement;
            }

            continue;
        }

        if ('drop_unique' === $type) {
            $drop_unique_operation = [
                'type' => 'drop_index',
                'name' => $operation['name'] ?? null,
                'if_exists' => (bool) ($operation['if_exists'] ?? false),
            ];

            $index_statement = schema_compile_internal_index_statement(
                operation: $drop_unique_operation,
                table_name: $table_name,
                driver: $driver,
                index_registry: $index_registry,
                version_payload: $version_payload
            );

            if (! harbor_is_null($index_statement)) {
                $statements[] = $index_statement;
            }

            continue;
        }

        if ('drop_index' === $type) {
            $index_statement = schema_compile_internal_index_statement(
                operation: $operation,
                table_name: $table_name,
                driver: $driver,
                index_registry: $index_registry,
                version_payload: $version_payload
            );

            if (! harbor_is_null($index_statement)) {
                $statements[] = $index_statement;
            }

            continue;
        }

        throw new \InvalidArgumentException(
            sprintf('Unsupported alter operation type "%s".', $type)
        );
    }

    return $statements;
}

/**
 * @param array{
 *   action: string,
 *   table: string,
 *   target: ?string,
 *   if_exists: bool,
 *   if_not_exists: bool,
 *   operations: array<int, array<string, mixed>>
 * } $builder
 */
function schema_compile_internal_drop_table_statement(array $builder, string $driver): string
{
    $quoted_table = schema_compile_internal_quote_identifier($builder['table']);
    $drop_keyword = $builder['if_exists'] ? 'DROP TABLE IF EXISTS' : 'DROP TABLE';

    if ('sqlite' !== $driver && 'mysql' !== $driver && 'mysqli' !== $driver) {
        throw new \InvalidArgumentException(
            sprintf('Unsupported driver "%s" for drop table statement.', $driver)
        );
    }

    return sprintf('%s %s', $drop_keyword, $quoted_table);
}

/**
 * @param array{
 *   action: string,
 *   table: string,
 *   target: ?string,
 *   if_exists: bool,
 *   if_not_exists: bool,
 *   operations: array<int, array<string, mixed>>
 * } $builder
 */
function schema_compile_internal_rename_table_statement(array $builder): string
{
    if (! is_string($builder['target']) || harbor_is_blank($builder['target'])) {
        throw new \InvalidArgumentException('Schema rename action requires target table name.');
    }

    return sprintf(
        'ALTER TABLE %s RENAME TO %s',
        schema_compile_internal_quote_identifier($builder['table']),
        schema_compile_internal_quote_identifier($builder['target'])
    );
}

/**
 * @param null|array<string, mixed> $version_payload
 */
function schema_compile_internal_assert_sqlite_feature_version(
    string $feature_name,
    int $minimum_major,
    int $minimum_minor,
    int $minimum_patch,
    ?array $version_payload
): void {
    if (harbor_is_null($version_payload)) {
        return;
    }

    $driver = strtolower((string) ($version_payload['driver'] ?? ''));
    if ('sqlite' !== $driver) {
        return;
    }

    $major = (int) ($version_payload['major'] ?? 0);
    $minor = (int) ($version_payload['minor'] ?? 0);
    $patch = (int) ($version_payload['patch'] ?? 0);

    if (
        $major > $minimum_major
        || ($major === $minimum_major && $minor > $minimum_minor)
        || ($major === $minimum_major && $minor === $minimum_minor && $patch >= $minimum_patch)
    ) {
        return;
    }

    throw new \RuntimeException(
        sprintf(
            'Feature "%s" requires SQLite %d.%d.%d or newer. Detected sqlite %d.%d.%d.',
            $feature_name,
            $minimum_major,
            $minimum_minor,
            $minimum_patch,
            $major,
            $minor,
            $patch
        )
    );
}

/**
 * @param null|array<string, mixed> $column
 *
 * @return array{type: string, arguments: array<int, mixed>, modifiers: array<string, mixed>}
 */
function schema_compile_internal_column_payload(mixed $column): array
{
    if (! is_array($column)) {
        throw new \InvalidArgumentException('Column payload must be an array produced by Column::to_array().');
    }

    $type = schema_compile_internal_validate_identifier((string) ($column['type'] ?? ''), 'column type');
    $arguments = $column['arguments'] ?? [];
    $modifiers = $column['modifiers'] ?? [];

    if (! is_array($arguments) || ! is_array($modifiers)) {
        throw new \InvalidArgumentException('Column payload contains invalid arguments or modifiers.');
    }

    return [
        'type' => $type,
        'arguments' => array_values($arguments),
        'modifiers' => $modifiers,
    ];
}

/**
 * @param null|array<string, mixed> $foreign_key
 *
 * @return array{
 *   columns: array<int, string>,
 *   references: array<int, string>,
 *   table: ?string,
 *   name: ?string,
 *   on_delete: ?string,
 *   on_update: ?string,
 *   deferrable: ?bool,
 *   initially_deferred: ?bool
 * }
 */
function schema_compile_internal_foreign_key_payload(mixed $foreign_key): array
{
    if (! is_array($foreign_key)) {
        throw new \InvalidArgumentException('Foreign key payload must be an array produced by ForeignKey::to_array().');
    }

    $columns = schema_compile_internal_normalize_identifier_list($foreign_key['columns'] ?? [], 'foreign key columns');
    $references = schema_compile_internal_normalize_identifier_list(
        $foreign_key['references'] ?? [],
        'foreign key reference columns'
    );

    if (count($columns) !== count($references)) {
        throw new \InvalidArgumentException('Foreign key column count must match reference column count.');
    }

    $table = schema_compile_internal_validate_identifier((string) ($foreign_key['table'] ?? ''), 'foreign key target table');

    $name = $foreign_key['name'] ?? null;
    if (is_string($name) && ! harbor_is_blank($name)) {
        $name = schema_compile_internal_validate_identifier($name, 'foreign key name');
    } else {
        $name = null;
    }

    return [
        'columns' => $columns,
        'references' => $references,
        'table' => $table,
        'name' => $name,
        'on_delete' => is_string($foreign_key['on_delete'] ?? null) ? $foreign_key['on_delete'] : null,
        'on_update' => is_string($foreign_key['on_update'] ?? null) ? $foreign_key['on_update'] : null,
        'deferrable' => is_bool($foreign_key['deferrable'] ?? null) ? $foreign_key['deferrable'] : null,
        'initially_deferred' => is_bool($foreign_key['initially_deferred'] ?? null)
            ? $foreign_key['initially_deferred']
            : null,
    ];
}

/**
 * @param array{type: string, arguments: array<int, mixed>, modifiers: array<string, mixed>} $column
 * @param null|array<string, mixed>                                                          $version_payload
 *
 * @return array{
 *   definition: string,
 *   implicit_primary: bool,
 *   primary_inline: bool,
 *   implicit_index_operations: array<int, array<string, mixed>>
 * }
 */
function schema_compile_internal_column_definition(
    string $table_name,
    string $column_name,
    array $column,
    string $driver,
    string $operation_type,
    ?array $version_payload = null
): array {
    $quoted_column = schema_compile_internal_quote_identifier($column_name);
    $type = $column['type'];
    $arguments = $column['arguments'];
    $modifiers = $column['modifiers'];

    schema_compile_internal_assert_column_expression_safety($modifiers);
    schema_compile_internal_assert_column_driver_support($driver, $modifiers, $operation_type, $version_payload);

    $implicit_primary = (bool) ($modifiers['primary'] ?? false);
    $auto_increment = (bool) ($modifiers['auto_increment'] ?? false);
    $primary_inline = false;
    $definition_parts = [];

    if ('sqlite' === $driver && $auto_increment) {
        if (! $implicit_primary) {
            throw new \InvalidArgumentException('SQLite auto_increment columns must also be primary().');
        }

        if (! schema_compile_internal_is_integer_like_type($type)) {
            throw new \InvalidArgumentException('SQLite auto_increment requires integer-like column type.');
        }

        $definition_parts[] = $quoted_column;
        $definition_parts[] = 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $primary_inline = true;
    } else {
        $definition_parts[] = $quoted_column;
        $definition_parts[] = schema_compile_internal_column_type_sql($type, $arguments, $driver);

        $nullable = (bool) ($modifiers['nullable'] ?? false);
        $definition_parts[] = $nullable ? 'NULL' : 'NOT NULL';

        if ((bool) ($modifiers['unsigned'] ?? false)) {
            if ('sqlite' === $driver) {
                throw new \InvalidArgumentException('SQLite driver does not support unsigned columns.');
            }

            $definition_parts[] = 'UNSIGNED';
        }

        if ($auto_increment) {
            if ('sqlite' === $driver) {
                throw new \InvalidArgumentException('SQLite auto increment columns must use INTEGER PRIMARY KEY AUTOINCREMENT.');
            }

            $definition_parts[] = 'AUTO_INCREMENT';
        }

        $has_default_expression = array_key_exists('default_expression', $modifiers);
        $has_default_value = array_key_exists('default', $modifiers);

        if ($has_default_expression) {
            $default_expression = (string) $modifiers['default_expression'];
            $definition_parts[] = 'DEFAULT '.$default_expression;
        } elseif ($has_default_value) {
            $definition_parts[] = 'DEFAULT '.schema_compile_internal_value_literal($modifiers['default']);
        } elseif ((bool) ($modifiers['use_current'] ?? false)) {
            $definition_parts[] = 'DEFAULT CURRENT_TIMESTAMP';
        }

        if ((bool) ($modifiers['use_current_on_update'] ?? false)) {
            if ('sqlite' === $driver) {
                throw new \InvalidArgumentException('SQLite driver does not support ON UPDATE CURRENT_TIMESTAMP columns.');
            }

            $definition_parts[] = 'ON UPDATE CURRENT_TIMESTAMP';
        }

        if (is_string($modifiers['comment'] ?? null) && ! harbor_is_blank($modifiers['comment'])) {
            if ('sqlite' === $driver) {
                throw new \InvalidArgumentException('SQLite driver does not support column comments.');
            }

            $definition_parts[] = sprintf("COMMENT '%s'", str_replace("'", "''", trim($modifiers['comment'])));
        }

        if (is_string($modifiers['charset'] ?? null) && ! harbor_is_blank($modifiers['charset'])) {
            if ('sqlite' === $driver) {
                throw new \InvalidArgumentException('SQLite driver does not support column charset.');
            }

            $definition_parts[] = 'CHARACTER SET '.schema_compile_internal_validate_identifier(
                $modifiers['charset'],
                'column charset'
            );
        }

        if (is_string($modifiers['collation'] ?? null) && ! harbor_is_blank($modifiers['collation'])) {
            if ('sqlite' === $driver) {
                throw new \InvalidArgumentException('SQLite driver does not support column collation.');
            }

            $definition_parts[] = 'COLLATE '.schema_compile_internal_validate_identifier(
                $modifiers['collation'],
                'column collation'
            );
        }

        if (is_string($modifiers['check'] ?? null) && ! harbor_is_blank($modifiers['check'])) {
            $definition_parts[] = 'CHECK ('.trim($modifiers['check']).')';
        }

        $virtual_as = $modifiers['virtual_as'] ?? null;
        $stored_as = $modifiers['stored_as'] ?? null;

        if (is_string($virtual_as) && ! harbor_is_blank($virtual_as) && is_string($stored_as) && ! harbor_is_blank($stored_as)) {
            throw new \InvalidArgumentException('Column cannot define both virtual_as() and stored_as() modifiers.');
        }

        if (is_string($virtual_as) && ! harbor_is_blank($virtual_as)) {
            $definition_parts[] = 'GENERATED ALWAYS AS ('.trim($virtual_as).') VIRTUAL';
        }

        if (is_string($stored_as) && ! harbor_is_blank($stored_as)) {
            $definition_parts[] = 'GENERATED ALWAYS AS ('.trim($stored_as).') STORED';
        }

        if ((bool) ($modifiers['first'] ?? false)) {
            if ('alter' !== $operation_type && 'change' !== $operation_type) {
                throw new \InvalidArgumentException('Column first() modifier can only be used in alter/change operations.');
            }

            if ('sqlite' === $driver) {
                throw new \InvalidArgumentException('SQLite driver does not support FIRST column positioning.');
            }

            $definition_parts[] = 'FIRST';
        }

        if (is_string($modifiers['after'] ?? null) && ! harbor_is_blank($modifiers['after'])) {
            if ('alter' !== $operation_type && 'change' !== $operation_type) {
                throw new \InvalidArgumentException('Column after() modifier can only be used in alter/change operations.');
            }

            if ('sqlite' === $driver) {
                throw new \InvalidArgumentException('SQLite driver does not support AFTER column positioning.');
            }

            $definition_parts[] = 'AFTER '.schema_compile_internal_quote_identifier(
                schema_compile_internal_validate_identifier($modifiers['after'], 'column after target')
            );
        }
    }

    $implicit_index_operations = [];

    if (array_key_exists('unique', $modifiers)) {
        $implicit_unique_name = schema_compile_internal_resolve_column_index_name(
            table_name: $table_name,
            column_name: $column_name,
            configured_name: $modifiers['unique'],
            suffix: 'unique'
        );

        $implicit_index_operations[] = [
            'type' => 'add_unique',
            'name' => $implicit_unique_name,
            'columns' => [$column_name],
        ];
    }

    if (array_key_exists('index', $modifiers)) {
        $implicit_index_name = schema_compile_internal_resolve_column_index_name(
            table_name: $table_name,
            column_name: $column_name,
            configured_name: $modifiers['index'],
            suffix: 'index'
        );

        $implicit_index_operations[] = [
            'type' => 'add_index',
            'name' => $implicit_index_name,
            'columns' => [$column_name],
            'unique' => false,
            'if_not_exists' => false,
        ];
    }

    return [
        'definition' => implode(' ', $definition_parts),
        'implicit_primary' => $implicit_primary,
        'primary_inline' => $primary_inline,
        'implicit_index_operations' => $implicit_index_operations,
    ];
}

/**
 * @param array<string, mixed>      $modifiers
 * @param null|array<string, mixed> $version_payload
 */
function schema_compile_internal_assert_column_driver_support(
    string $driver,
    array $modifiers,
    string $operation_type,
    ?array $version_payload = null
): void {
    if (array_key_exists('virtual_as', $modifiers) || array_key_exists('stored_as', $modifiers)) {
        if ('sqlite' === $driver) {
            schema_compile_internal_assert_sqlite_feature_version(
                feature_name: 'generated columns',
                minimum_major: 3,
                minimum_minor: 31,
                minimum_patch: 0,
                version_payload: $version_payload
            );
        }
    }

    if (('alter' === $operation_type || 'change' === $operation_type) && array_key_exists('after', $modifiers) && 'sqlite' === $driver) {
        throw new \InvalidArgumentException('SQLite driver does not support AFTER column positioning.');
    }

    if (('alter' === $operation_type || 'change' === $operation_type) && array_key_exists('first', $modifiers) && 'sqlite' === $driver) {
        throw new \InvalidArgumentException('SQLite driver does not support FIRST column positioning.');
    }
}

/**
 * @param array<string, mixed> $modifiers
 */
function schema_compile_internal_assert_column_expression_safety(array $modifiers): void
{
    foreach (['default_expression', 'check', 'virtual_as', 'stored_as'] as $expression_modifier_name) {
        $expression = $modifiers[$expression_modifier_name] ?? null;
        if (! is_string($expression) || harbor_is_blank(trim($expression))) {
            continue;
        }

        schema_compile_internal_assert_safe_expression(trim($expression), sprintf('Column %s()', $expression_modifier_name));
    }
}

function schema_compile_internal_assert_safe_expression(string $expression, string $context): void
{
    foreach ([';', '--', '/*', '*/'] as $unsafe_token) {
        if (str_contains($expression, $unsafe_token)) {
            throw new \InvalidArgumentException(
                sprintf('%s contains unsafe SQL token "%s".', $context, $unsafe_token)
            );
        }
    }
}

/**
 * @param array<int, mixed> $arguments
 */
function schema_compile_internal_column_type_sql(string $type, array $arguments, string $driver): string
{
    return match ($type) {
        'tiny_int' => 'sqlite' === $driver ? 'INTEGER' : 'TINYINT',
        'small_int' => 'sqlite' === $driver ? 'INTEGER' : 'SMALLINT',
        'int' => 'sqlite' === $driver ? 'INTEGER' : 'INT',
        'big_int' => 'sqlite' === $driver ? 'INTEGER' : 'BIGINT',
        'decimal' => sprintf(
            'DECIMAL(%d, %d)',
            schema_compile_internal_positive_int_argument($arguments, 0, 'decimal precision'),
            schema_compile_internal_non_negative_int_argument($arguments, 1, 'decimal scale')
        ),
        'float' => 'FLOAT',
        'double' => 'DOUBLE',
        'bool' => 'sqlite' === $driver ? 'INTEGER' : 'TINYINT(1)',
        'char' => sprintf('CHAR(%d)', schema_compile_internal_positive_int_argument($arguments, 0, 'char length')),
        'varchar' => sprintf('VARCHAR(%d)', schema_compile_internal_positive_int_argument($arguments, 0, 'varchar length')),
        'text' => 'TEXT',
        'medium_text' => 'sqlite' === $driver ? 'TEXT' : 'MEDIUMTEXT',
        'long_text' => 'sqlite' === $driver ? 'TEXT' : 'LONGTEXT',
        'json' => 'sqlite' === $driver ? 'TEXT' : 'JSON',
        'date' => 'DATE',
        'time' => 'TIME',
        'datetime' => 'DATETIME',
        'timestamp' => 'TIMESTAMP',
        'year' => 'sqlite' === $driver ? 'INTEGER' : 'YEAR',
        'binary' => 'sqlite' === $driver
            ? 'BLOB'
            : sprintf('BINARY(%d)', schema_compile_internal_positive_int_argument($arguments, 0, 'binary length')),
        'varbinary' => 'sqlite' === $driver
            ? 'BLOB'
            : sprintf('VARBINARY(%d)', schema_compile_internal_positive_int_argument($arguments, 0, 'varbinary length')),
        'blob' => 'BLOB',
        'long_blob' => 'sqlite' === $driver ? 'BLOB' : 'LONGBLOB',
        'uuid' => 'CHAR(36)',
        'ulid' => 'CHAR(26)',
        'enum' => schema_compile_internal_enum_type($arguments, $driver),
        'set' => schema_compile_internal_set_type($arguments, $driver),
        default => throw new \InvalidArgumentException(
            sprintf('Unsupported column type "%s".', $type)
        ),
    };
}

/**
 * @param array<int, mixed> $arguments
 */
function schema_compile_internal_enum_type(array $arguments, string $driver): string
{
    $values = $arguments[0] ?? null;
    if (! is_array($values) || empty($values)) {
        throw new \InvalidArgumentException('Enum type requires at least one allowed value.');
    }

    $quoted_values = [];

    foreach ($values as $value) {
        if (! is_string($value) || harbor_is_blank(trim($value))) {
            throw new \InvalidArgumentException('Enum allowed values must contain only non-empty strings.');
        }

        $quoted_values[] = "'".str_replace("'", "''", $value)."'";
    }

    if ('sqlite' === $driver) {
        return 'TEXT';
    }

    return sprintf('ENUM(%s)', implode(', ', $quoted_values));
}

/**
 * @param array<int, mixed> $arguments
 */
function schema_compile_internal_set_type(array $arguments, string $driver): string
{
    if ('sqlite' === $driver) {
        throw new \InvalidArgumentException('SQLite driver does not support SET column type.');
    }

    $values = $arguments[0] ?? null;
    if (! is_array($values) || empty($values)) {
        throw new \InvalidArgumentException('Set type requires at least one allowed value.');
    }

    $quoted_values = [];

    foreach ($values as $value) {
        if (! is_string($value) || harbor_is_blank(trim($value))) {
            throw new \InvalidArgumentException('Set allowed values must contain only non-empty strings.');
        }

        $quoted_values[] = "'".str_replace("'", "''", $value)."'";
    }

    return sprintf('SET(%s)', implode(', ', $quoted_values));
}

function schema_compile_internal_is_integer_like_type(string $type): bool
{
    return in_array($type, ['tiny_int', 'small_int', 'int', 'big_int'], true);
}

/**
 * @param array<int, mixed> $arguments
 */
function schema_compile_internal_positive_int_argument(array $arguments, int $index, string $label): int
{
    $value = $arguments[$index] ?? null;
    if (! is_int($value) || $value <= 0) {
        throw new \InvalidArgumentException(sprintf('%s must be a positive integer.', $label));
    }

    return $value;
}

/**
 * @param array<int, mixed> $arguments
 */
function schema_compile_internal_non_negative_int_argument(array $arguments, int $index, string $label): int
{
    $value = $arguments[$index] ?? null;
    if (! is_int($value) || $value < 0) {
        throw new \InvalidArgumentException(sprintf('%s must be a non-negative integer.', $label));
    }

    return $value;
}

function schema_compile_internal_value_literal(mixed $value): string
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

    throw new \InvalidArgumentException('Unsupported default literal value type.');
}

/**
 * @param array<string, mixed>                                $operation
 * @param array<string, array{type: string, columns: string}> $index_registry
 * @param null|array<string, mixed>                           $version_payload
 */
function schema_compile_internal_index_statement(
    array $operation,
    string $table_name,
    string $driver,
    array &$index_registry,
    ?array $version_payload = null
): ?string {
    $type = (string) ($operation['type'] ?? '');
    $quoted_table = schema_compile_internal_quote_identifier($table_name);

    if ('add_unique' === $type || 'add_index' === $type) {
        $columns = schema_compile_internal_normalize_identifier_list($operation['columns'] ?? [], 'index columns');
        $is_unique = 'add_unique' === $type || (bool) ($operation['unique'] ?? false);
        $if_not_exists = (bool) ($operation['if_not_exists'] ?? false);

        $name = $operation['name'] ?? null;
        if (! is_string($name) || harbor_is_blank(trim($name))) {
            $name = schema_compile_internal_generate_index_name(
                table_name: $table_name,
                columns: $columns,
                unique: $is_unique
            );
        }

        $normalized_name = schema_compile_internal_validate_identifier($name, 'index name');
        $signature = [
            'type' => $is_unique ? 'unique' : 'index',
            'columns' => implode(',', $columns),
        ];

        if (! schema_compile_internal_register_index_signature($index_registry, $normalized_name, $signature)) {
            return null;
        }

        $if_not_exists_sql = '';
        if ($if_not_exists) {
            if ('sqlite' === $driver) {
                $if_not_exists_sql = ' IF NOT EXISTS';
            } else {
                throw new \InvalidArgumentException(
                    sprintf('Driver "%s" does not support CREATE INDEX IF NOT EXISTS.', $driver)
                );
            }
        }

        $quoted_columns = array_map(
            static fn (string $column): string => schema_compile_internal_quote_identifier($column),
            $columns
        );
        $quoted_name = schema_compile_internal_quote_identifier($normalized_name);
        $index_keyword = $is_unique ? 'CREATE UNIQUE INDEX' : 'CREATE INDEX';

        return sprintf(
            '%s%s %s ON %s (%s)',
            $index_keyword,
            $if_not_exists_sql,
            $quoted_name,
            $quoted_table,
            implode(', ', $quoted_columns)
        );
    }

    if ('drop_index' === $type) {
        $name = schema_compile_internal_validate_identifier((string) ($operation['name'] ?? ''), 'index name');
        $if_exists = (bool) ($operation['if_exists'] ?? false);
        $signature = [
            'type' => 'drop_index',
            'columns' => '',
        ];
        schema_compile_internal_register_index_signature($index_registry, 'drop:'.$name, $signature);

        if ('sqlite' === $driver) {
            $if_exists_sql = $if_exists ? ' IF EXISTS' : '';

            return sprintf('DROP INDEX%s %s', $if_exists_sql, schema_compile_internal_quote_identifier($name));
        }

        if ($if_exists) {
            throw new \InvalidArgumentException(
                sprintf('Driver "%s" does not support DROP INDEX IF EXISTS.', $driver)
            );
        }

        return sprintf('DROP INDEX %s ON %s', schema_compile_internal_quote_identifier($name), $quoted_table);
    }

    throw new \InvalidArgumentException(sprintf('Unsupported index operation type "%s".', $type));
}

/**
 * @param array<string, array{type: string, columns: string}> $index_registry
 * @param array{type: string, columns: string}                $signature
 */
function schema_compile_internal_register_index_signature(array &$index_registry, string $key, array $signature): bool
{
    if (! array_key_exists($key, $index_registry)) {
        $index_registry[$key] = $signature;

        return true;
    }

    $existing = $index_registry[$key];
    if ($existing['type'] === $signature['type'] && $existing['columns'] === $signature['columns']) {
        return false;
    }

    throw new \InvalidArgumentException(
        sprintf(
            'Index name collision for "%s". Existing definition [%s %s], new definition [%s %s].',
            $key,
            $existing['type'],
            $existing['columns'],
            $signature['type'],
            $signature['columns']
        )
    );
}

/**
 * @param array<int, string> $columns
 */
function schema_compile_internal_generate_index_name(string $table_name, array $columns, bool $unique = false): string
{
    $suffix = $unique ? 'unique' : 'index';

    return strtolower($table_name.'_'.implode('_', $columns).'_'.$suffix);
}

/**
 * @param array{
 *   columns: array<int, string>,
 *   references: array<int, string>,
 *   table: ?string,
 *   name: ?string,
 *   on_delete: ?string,
 *   on_update: ?string,
 *   deferrable: ?bool,
 *   initially_deferred: ?bool
 * } $foreign_key
 */
function schema_compile_internal_create_table_foreign_constraint(array $foreign_key, string $owner_table, string $driver): string
{
    return schema_compile_internal_foreign_constraint_sql($foreign_key, $owner_table, $driver);
}

/**
 * @param array{
 *   columns: array<int, string>,
 *   references: array<int, string>,
 *   table: ?string,
 *   name: ?string,
 *   on_delete: ?string,
 *   on_update: ?string,
 *   deferrable: ?bool,
 *   initially_deferred: ?bool
 * } $foreign_key
 */
function schema_compile_internal_alter_foreign_add_statement(array $foreign_key, string $table_name, string $driver): string
{
    return sprintf(
        'ALTER TABLE %s ADD %s',
        schema_compile_internal_quote_identifier($table_name),
        schema_compile_internal_foreign_constraint_sql($foreign_key, $table_name, $driver)
    );
}

/**
 * @param array{
 *   columns: array<int, string>,
 *   references: array<int, string>,
 *   table: ?string,
 *   name: ?string,
 *   on_delete: ?string,
 *   on_update: ?string,
 *   deferrable: ?bool,
 *   initially_deferred: ?bool
 * } $foreign_key
 */
function schema_compile_internal_foreign_constraint_sql(array $foreign_key, string $owner_table, string $driver): string
{
    $columns = schema_compile_internal_normalize_identifier_list($foreign_key['columns'], 'foreign key columns');
    $references = schema_compile_internal_normalize_identifier_list($foreign_key['references'], 'foreign key reference columns');

    if (count($columns) !== count($references)) {
        throw new \InvalidArgumentException('Foreign key column count must match reference column count.');
    }

    $reference_table = schema_compile_internal_validate_identifier((string) $foreign_key['table'], 'foreign key reference table');
    $constraint_name = is_string($foreign_key['name']) && ! harbor_is_blank(trim($foreign_key['name']))
        ? schema_compile_internal_validate_identifier($foreign_key['name'], 'foreign key name')
        : schema_compile_internal_generate_foreign_key_name($owner_table, $columns);

    $quoted_constraint_name = schema_compile_internal_quote_identifier($constraint_name);
    $quoted_columns = array_map(
        static fn (string $column): string => schema_compile_internal_quote_identifier($column),
        $columns
    );
    $quoted_references = array_map(
        static fn (string $column): string => schema_compile_internal_quote_identifier($column),
        $references
    );

    $parts = [
        sprintf(
            'CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s)',
            $quoted_constraint_name,
            implode(', ', $quoted_columns),
            schema_compile_internal_quote_identifier($reference_table),
            implode(', ', $quoted_references)
        ),
    ];

    if (is_string($foreign_key['on_delete']) && ! harbor_is_blank(trim($foreign_key['on_delete']))) {
        $parts[] = 'ON DELETE '.trim($foreign_key['on_delete']);
    }

    if (is_string($foreign_key['on_update']) && ! harbor_is_blank(trim($foreign_key['on_update']))) {
        $parts[] = 'ON UPDATE '.trim($foreign_key['on_update']);
    }

    $deferrable = $foreign_key['deferrable'];
    $initially_deferred = $foreign_key['initially_deferred'];

    if ((! harbor_is_null($deferrable) || ! harbor_is_null($initially_deferred)) && 'sqlite' !== $driver) {
        throw new \InvalidArgumentException(
            sprintf('Driver "%s" does not support deferrable foreign key modifiers.', $driver)
        );
    }

    if (true === $deferrable) {
        $parts[] = 'DEFERRABLE';
    } elseif (false === $deferrable) {
        $parts[] = 'NOT DEFERRABLE';
    }

    if (true === $initially_deferred) {
        $parts[] = 'INITIALLY DEFERRED';
    } elseif (false === $initially_deferred) {
        $parts[] = 'INITIALLY IMMEDIATE';
    }

    return implode(' ', $parts);
}

/**
 * @param array<int, string> $columns
 */
function schema_compile_internal_generate_foreign_key_name(string $owner_table, array $columns): string
{
    return strtolower($owner_table.'_'.implode('_', $columns).'_foreign');
}

function schema_compile_internal_validate_identifier(string $identifier, string $label = 'identifier'): string
{
    $normalized_identifier = trim($identifier);
    if (harbor_is_blank($normalized_identifier)) {
        throw new \InvalidArgumentException(sprintf('%s cannot be empty.', ucfirst($label)));
    }

    if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $normalized_identifier)) {
        throw new \InvalidArgumentException(
            sprintf('%s "%s" contains unsupported characters.', ucfirst($label), $identifier)
        );
    }

    return $normalized_identifier;
}

function schema_compile_internal_quote_identifier(string $identifier): string
{
    return sprintf('`%s`', str_replace('`', '``', $identifier));
}

/**
 * @return array<int, string>
 */
function schema_compile_internal_normalize_identifier_list(mixed $value, string $label): array
{
    if (! is_array($value) || empty($value)) {
        throw new \InvalidArgumentException(sprintf('%s cannot be empty.', ucfirst($label)));
    }

    $normalized = [];
    foreach ($value as $identifier) {
        if (! is_string($identifier)) {
            throw new \InvalidArgumentException(sprintf('%s must contain only strings.', ucfirst($label)));
        }

        $normalized[] = schema_compile_internal_validate_identifier($identifier, $label);
    }

    return $normalized;
}

/**
 * @param array<int, string> $left
 * @param array<int, string> $right
 */
function schema_compile_internal_identifier_lists_equal(array $left, array $right): bool
{
    if (count($left) !== count($right)) {
        return false;
    }

    foreach ($left as $index => $identifier) {
        if (($right[$index] ?? null) !== $identifier) {
            return false;
        }
    }

    return true;
}

/**
 * @param array<int, string> $column_definitions
 * @param array<int, string> $primary_columns
 */
function schema_compile_internal_primary_is_inline_single(array $column_definitions, array $primary_columns): bool
{
    if (1 !== count($primary_columns)) {
        return false;
    }

    $primary_column = schema_compile_internal_quote_identifier($primary_columns[0]);

    foreach ($column_definitions as $column_definition) {
        if (str_starts_with($column_definition, $primary_column.' ') && str_contains($column_definition, 'PRIMARY KEY AUTOINCREMENT')) {
            return true;
        }
    }

    return false;
}

function schema_compile_internal_resolve_column_index_name(
    string $table_name,
    string $column_name,
    mixed $configured_name,
    string $suffix
): string {
    if (is_string($configured_name) && ! harbor_is_blank(trim($configured_name))) {
        return schema_compile_internal_validate_identifier($configured_name, 'column index name');
    }

    if (true !== $configured_name) {
        throw new \InvalidArgumentException(
            sprintf('Column %s modifier must be true or a valid index name string.', $suffix)
        );
    }

    return schema_compile_internal_generate_index_name(
        table_name: $table_name,
        columns: [$column_name],
        unique: 'unique' === $suffix
    );
}
