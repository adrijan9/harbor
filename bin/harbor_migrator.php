<?php

declare(strict_types=1);

require_once __DIR__.'/../src/Support/value.php';

require_once __DIR__.'/../src/Database/db.php';

require_once __DIR__.'/../src/Database/DbDriver.php';

require_once __DIR__.'/harbor_site.php';

use Harbor\Database\DbDriver;

use function Harbor\Database\db_close;
use function Harbor\Database\db_connect;
use function Harbor\Support\harbor_is_blank;

/**
 * @param array<int, string> $arguments
 */
function harbor_migrator_run(string $kind, array $arguments): int
{
    $normalized_kind = harbor_migrator_normalize_kind($kind);
    $command = $arguments[1] ?? null;
    $normalized_command = is_string($command) ? strtolower(trim($command)) : '';

    try {
        if (in_array($normalized_command, ['-h', '--help'], true)) {
            harbor_migrator_print_usage($normalized_kind);

            return 0;
        }

        harbor_site_assert_selected();

        $config = harbor_migrator_load_config();

        if (harbor_is_blank($normalized_command)) {
            harbor_migrator_apply_pending($normalized_kind, $config);

            return 0;
        }

        if ('new' === $normalized_command) {
            $name = $arguments[2] ?? null;
            if (! is_string($name) || harbor_is_blank($name)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Missing %s name. Usage: %s',
                        harbor_migrator_kind_label($normalized_kind),
                        harbor_migrator_new_usage($normalized_kind),
                    )
                );
            }

            $created_path = harbor_migrator_create_file($normalized_kind, $name, $config);
            fwrite(
                STDOUT,
                sprintf('%s created: %s%s', ucfirst(harbor_migrator_kind_label($normalized_kind)), $created_path, PHP_EOL)
            );

            return 0;
        }

        if (in_array($normalized_command, ['rollback', 'down'], true)) {
            harbor_migrator_rollback_last_batch($normalized_kind, $config);

            return 0;
        }

        throw new InvalidArgumentException(
            sprintf(
                'Unknown %s command: %s',
                harbor_migrator_kind_label($normalized_kind),
                is_string($command) ? $command : '(empty)'
            )
        );
    } catch (Throwable $throwable) {
        fwrite(STDERR, $throwable->getMessage().PHP_EOL);

        return 1;
    }
}

function harbor_migrator_print_usage(string $kind): void
{
    $normalized_kind = harbor_migrator_normalize_kind($kind);
    $binary_name = harbor_migrator_binary_name($normalized_kind);

    fwrite(STDOUT, sprintf('Usage: %s [new "name"|rollback|-h]%s', $binary_name, PHP_EOL));
    fwrite(STDOUT, PHP_EOL);
    fwrite(STDOUT, sprintf('%s commands:%s', ucfirst(harbor_migrator_kind_label($normalized_kind)), PHP_EOL));
    fwrite(STDOUT, sprintf('  %s                    Run pending %s files.%s', $binary_name, harbor_migrator_kind_key($normalized_kind), PHP_EOL));
    fwrite(STDOUT, sprintf('  %s new "name"         Create a timestamped %s file.%s', $binary_name, harbor_migrator_kind_label($normalized_kind), PHP_EOL));
    fwrite(STDOUT, sprintf('  %s rollback           Roll back the latest %s batch.%s', $binary_name, harbor_migrator_kind_label($normalized_kind), PHP_EOL));
    fwrite(STDOUT, sprintf('  %s -h                 Show this help message.%s', $binary_name, PHP_EOL));
}

function harbor_migrator_new_usage(string $kind): string
{
    return harbor_migrator_binary_name(harbor_migrator_normalize_kind($kind)).' new "name"';
}

function harbor_migrator_binary_name(string $kind): string
{
    return 'seeder' === harbor_migrator_normalize_kind($kind) ? 'harbor-seed' : 'harbor-migration';
}

function harbor_migrator_kind_label(string $kind): string
{
    return 'seeder' === harbor_migrator_normalize_kind($kind) ? 'seeder' : 'migration';
}

function harbor_migrator_kind_key(string $kind): string
{
    return 'seeder' === harbor_migrator_normalize_kind($kind) ? 'seeders' : 'migrations';
}

function harbor_migrator_normalize_kind(string $kind): string
{
    $normalized_kind = strtolower(trim($kind));

    return in_array($normalized_kind, ['seed', 'seeder', 'seeders'], true) ? 'seeder' : 'migration';
}

/**
 * @return array<string, mixed>
 */
function harbor_migrator_load_config(?string $working_directory = null): array
{
    $config_path = harbor_migrator_config_path($working_directory);
    if (! is_file($config_path)) {
        throw new RuntimeException(
            sprintf('Migration config not found: %s (publish it with "harbor-config").', $config_path)
        );
    }

    $config = require $config_path;
    if (! is_array($config)) {
        throw new RuntimeException(
            sprintf('Migration config must return an array: %s', $config_path)
        );
    }

    return $config;
}

function harbor_migrator_config_path(?string $working_directory = null): string
{
    $resolved_working_directory = harbor_migrator_resolve_working_directory($working_directory);

    return $resolved_working_directory.'/config/migration.php';
}

function harbor_migrator_resolve_working_directory(?string $working_directory = null): string
{
    if (is_string($working_directory) && ! harbor_is_blank($working_directory)) {
        return rtrim($working_directory, '/\\');
    }

    $resolved_working_directory = getcwd();
    if (false === $resolved_working_directory || harbor_is_blank($resolved_working_directory)) {
        throw new RuntimeException('Unable to resolve current working directory.');
    }

    return $resolved_working_directory;
}

/**
 * @param array<string, mixed> $config
 */
function harbor_migrator_apply_pending(string $kind, array $config): void
{
    $resolved_kind = harbor_migrator_normalize_kind($kind);
    $tracker = harbor_migrator_tracker($resolved_kind, $config);
    harbor_migrator_ensure_directory($tracker['directory']);

    $files = harbor_migrator_discover_files($tracker['directory']);
    if (empty($files)) {
        fwrite(STDOUT, sprintf('No %s files found in: %s%s', harbor_migrator_kind_key($resolved_kind), $tracker['directory'], PHP_EOL));

        return;
    }

    $connection = harbor_migrator_connect($config);

    try {
        harbor_migrator_ensure_tracking_table($connection, $tracker['table']);

        $executed_names = harbor_migrator_fetch_executed_names($connection, $tracker['table']);
        $pending_files = [];

        foreach ($files as $file_path) {
            $file_name = basename($file_path);
            if (in_array($file_name, $executed_names, true)) {
                continue;
            }

            $pending_files[] = $file_path;
        }

        if (empty($pending_files)) {
            fwrite(STDOUT, sprintf('No pending %s.%s', harbor_migrator_kind_key($resolved_kind), PHP_EOL));

            return;
        }

        $batch = harbor_migrator_next_batch($connection, $tracker['table']);

        foreach ($pending_files as $pending_file_path) {
            $pending_file_name = basename($pending_file_path);
            $instance = harbor_migrator_require_file_instance($pending_file_path);

            $instance->up();
            harbor_migrator_insert_executed($connection, $tracker['table'], $pending_file_name, $batch);

            fwrite(STDOUT, sprintf('%s: %s%s', harbor_migrator_applied_label($resolved_kind), $pending_file_name, PHP_EOL));
        }

        fwrite(
            STDOUT,
            sprintf(
                'Completed %s batch %d with %d file(s).%s',
                harbor_migrator_kind_label($resolved_kind),
                $batch,
                count($pending_files),
                PHP_EOL
            )
        );
    } finally {
        db_close($connection);
    }
}

/**
 * @param array<string, mixed> $config
 */
function harbor_migrator_rollback_last_batch(string $kind, array $config): void
{
    $resolved_kind = harbor_migrator_normalize_kind($kind);
    $tracker = harbor_migrator_tracker($resolved_kind, $config);
    harbor_migrator_ensure_directory($tracker['directory']);

    $connection = harbor_migrator_connect($config);

    try {
        harbor_migrator_ensure_tracking_table($connection, $tracker['table']);

        $last_batch = harbor_migrator_last_batch($connection, $tracker['table']);
        if (null === $last_batch) {
            fwrite(
                STDOUT,
                sprintf('No %s batch available for rollback.%s', harbor_migrator_kind_label($resolved_kind), PHP_EOL)
            );

            return;
        }

        $executed_names = harbor_migrator_fetch_batch_names($connection, $tracker['table'], $last_batch);
        if (empty($executed_names)) {
            fwrite(
                STDOUT,
                sprintf('No %s entries found in batch %d.%s', harbor_migrator_kind_key($resolved_kind), $last_batch, PHP_EOL)
            );

            return;
        }

        foreach ($executed_names as $executed_name) {
            $file_path = $tracker['directory'].'/'.$executed_name;
            if (! is_file($file_path)) {
                throw new RuntimeException(
                    sprintf(
                        'Cannot rollback %s. File missing: %s',
                        harbor_migrator_kind_label($resolved_kind),
                        $file_path
                    )
                );
            }

            $instance = harbor_migrator_require_file_instance($file_path);
            $instance->down();
            harbor_migrator_delete_executed($connection, $tracker['table'], $executed_name);

            fwrite(STDOUT, sprintf('%s: %s%s', harbor_migrator_rolled_back_label($resolved_kind), $executed_name, PHP_EOL));
        }

        fwrite(
            STDOUT,
            sprintf(
                'Rolled back %s batch %d with %d file(s).%s',
                harbor_migrator_kind_label($resolved_kind),
                $last_batch,
                count($executed_names),
                PHP_EOL
            )
        );
    } finally {
        db_close($connection);
    }
}

function harbor_migrator_applied_label(string $kind): string
{
    return 'seeder' === harbor_migrator_normalize_kind($kind) ? 'Seeded' : 'Migrated';
}

function harbor_migrator_rolled_back_label(string $kind): string
{
    return 'seeder' === harbor_migrator_normalize_kind($kind) ? 'Rolled back seeder' : 'Rolled back migration';
}

/**
 * @param array<string, mixed> $config
 */
function harbor_migrator_create_file(string $kind, string $name, array $config, ?DateTimeImmutable $now = null): string
{
    $resolved_kind = harbor_migrator_normalize_kind($kind);
    $tracker = harbor_migrator_tracker($resolved_kind, $config);
    harbor_migrator_ensure_directory($tracker['directory']);

    $normalized_name = harbor_migrator_slugify_name($name);
    $timestamp = ($now ?? new DateTimeImmutable('now'))->format('Y-m-d-H-i-s');
    $file_name = sprintf('%s_%s.php', $timestamp, $normalized_name);
    $file_path = $tracker['directory'].'/'.$file_name;

    if (is_file($file_path)) {
        throw new RuntimeException(
            sprintf('%s file already exists: %s', ucfirst(harbor_migrator_kind_label($resolved_kind)), $file_path)
        );
    }

    $written = file_put_contents($file_path, harbor_migrator_file_template());
    if (false === $written) {
        throw new RuntimeException(
            sprintf('Failed to write %s file: %s', harbor_migrator_kind_label($resolved_kind), $file_path)
        );
    }

    return $file_path;
}

function harbor_migrator_slugify_name(string $name): string
{
    $normalized_name = strtolower(trim($name));
    if (harbor_is_blank($normalized_name)) {
        throw new InvalidArgumentException('Name cannot be empty.');
    }

    $slug = preg_replace('/[^a-z0-9]+/', '_', $normalized_name);
    $resolved_slug = is_string($slug) ? trim($slug, '_') : '';

    if (harbor_is_blank($resolved_slug)) {
        throw new InvalidArgumentException('Name must contain letters or numbers.');
    }

    return $resolved_slug;
}

function harbor_migrator_file_template(): string
{
    return <<<'PHP'
        <?php

        declare(strict_types=1);

        require __DIR__.'/../../../vendor/autoload.php';

        return new class {
            public function up(): void
            {
                // Do the stuff
            }

            public function down(): void
            {
                // Do the stuff
            }
        };
        PHP;
}

function harbor_migrator_ensure_directory(string $directory_path): void
{
    if (is_dir($directory_path)) {
        return;
    }

    if (! mkdir($directory_path, 0o777, true) && ! is_dir($directory_path)) {
        throw new RuntimeException(sprintf('Failed to create directory: %s', $directory_path));
    }
}

/**
 * @return array<int, string>
 */
function harbor_migrator_discover_files(string $directory_path): array
{
    $glob_pattern = rtrim($directory_path, '/\\').'/*.php';
    $glob_result = glob($glob_pattern);
    if (false === $glob_result) {
        throw new RuntimeException(sprintf('Failed to read directory: %s', $directory_path));
    }

    $files = array_values(array_filter($glob_result, static fn (string $path): bool => is_file($path)));
    sort($files, SORT_STRING);

    return $files;
}

/**
 * @param array<string, mixed> $config
 *
 * @return array{directory: string, table: string}
 */
function harbor_migrator_tracker(string $kind, array $config): array
{
    $resolved_kind = harbor_migrator_normalize_kind($kind);
    $kind_key = harbor_migrator_kind_key($resolved_kind);
    $working_directory = harbor_migrator_resolve_working_directory();

    $kind_config = $config[$kind_key] ?? [];
    if (! is_array($kind_config)) {
        $kind_config = [];
    }

    $configured_directory = $kind_config['directory'] ?? $working_directory.'/database/'.$kind_key;
    if (! is_string($configured_directory) || harbor_is_blank($configured_directory)) {
        throw new RuntimeException(sprintf('Missing %s.directory in migration config.', $kind_key));
    }

    $directory = harbor_migrator_resolve_path($configured_directory, $working_directory);

    $configured_table = $kind_config['table'] ?? $kind_key;
    if (! is_string($configured_table) || harbor_is_blank($configured_table)) {
        throw new RuntimeException(sprintf('Missing %s.table in migration config.', $kind_key));
    }

    $table = trim($configured_table);
    if (! harbor_migrator_is_valid_identifier($table)) {
        throw new RuntimeException(
            sprintf('Invalid table name "%s". Use letters, numbers, and underscores only.', $table)
        );
    }

    return [
        'directory' => $directory,
        'table' => $table,
    ];
}

function harbor_migrator_resolve_path(string $path, string $working_directory): string
{
    $trimmed_path = trim($path);
    if (harbor_migrator_is_absolute_path($trimmed_path)) {
        return rtrim($trimmed_path, '/\\');
    }

    return rtrim($working_directory, '/\\').'/'.ltrim($trimmed_path, '/\\');
}

function harbor_migrator_is_absolute_path(string $path): bool
{
    return 1 === preg_match('#^([a-zA-Z]:[\\\/]|/)#', $path);
}

/**
 * @param array<string, mixed> $config
 */
function harbor_migrator_connect(array $config): mysqli|PDO
{
    $driver = harbor_migrator_connection_driver($config);

    /** @var array<string, mixed> $connection_config */
    $connection_config = [
        'driver' => $driver,
        'sqlite' => is_array($config['sqlite'] ?? null) ? $config['sqlite'] : [],
        'mysql' => is_array($config['mysql'] ?? null) ? $config['mysql'] : [],
    ];

    return db_connect($driver, $connection_config);
}

/**
 * @param array<string, mixed> $config
 */
function harbor_migrator_connection_driver(array $config): string
{
    $configured_driver = $config['driver'] ?? DbDriver::SQLITE->value;
    if ($configured_driver instanceof DbDriver) {
        return $configured_driver->value;
    }

    if (! is_string($configured_driver) || harbor_is_blank($configured_driver)) {
        return DbDriver::SQLITE->value;
    }

    $normalized_driver = strtolower(trim($configured_driver));
    if (! in_array($normalized_driver, [DbDriver::SQLITE->value, DbDriver::MYSQL->value, DbDriver::MYSQLI->value], true)) {
        throw new RuntimeException(
            sprintf('Invalid migration driver "%s". Supported drivers: sqlite, mysql, mysqli.', $configured_driver)
        );
    }

    return $normalized_driver;
}

function harbor_migrator_ensure_tracking_table(mysqli|PDO $connection, string $table): void
{
    $dialect = harbor_migrator_sql_dialect($connection);
    $table_identifier = harbor_migrator_quote_identifier($table, $dialect);

    if ('sqlite' === $dialect) {
        harbor_migrator_exec_sql(
            $connection,
            sprintf(
                'CREATE TABLE IF NOT EXISTS %s (name TEXT PRIMARY KEY, batch INTEGER NOT NULL)',
                $table_identifier
            )
        );

        return;
    }

    harbor_migrator_exec_sql(
        $connection,
        sprintf(
            'CREATE TABLE IF NOT EXISTS %s (name VARCHAR(255) PRIMARY KEY, batch INT NOT NULL)',
            $table_identifier
        )
    );
}

function harbor_migrator_next_batch(mysqli|PDO $connection, string $table): int
{
    $last_batch = harbor_migrator_last_batch($connection, $table);

    return null === $last_batch ? 1 : $last_batch + 1;
}

function harbor_migrator_last_batch(mysqli|PDO $connection, string $table): ?int
{
    $table_identifier = harbor_migrator_quote_identifier($table, harbor_migrator_sql_dialect($connection));
    $query = sprintf('SELECT MAX(batch) AS max_batch FROM %s', $table_identifier);

    if ($connection instanceof mysqli) {
        $result = $connection->query($query);
        if (false === $result) {
            throw new RuntimeException('Failed to read last batch: '.$connection->error);
        }

        $row = $result->fetch_assoc();
        $result->free();
        if (! is_array($row) || ! array_key_exists('max_batch', $row) || null === $row['max_batch']) {
            return null;
        }

        return (int) $row['max_batch'];
    }

    $statement = $connection->query($query);
    if (! $statement instanceof PDOStatement) {
        throw new RuntimeException('Failed to read last batch.');
    }

    $row = $statement->fetch(PDO::FETCH_ASSOC);
    if (! is_array($row) || ! array_key_exists('max_batch', $row) || null === $row['max_batch']) {
        return null;
    }

    return (int) $row['max_batch'];
}

/**
 * @return array<int, string>
 */
function harbor_migrator_fetch_executed_names(mysqli|PDO $connection, string $table): array
{
    $table_identifier = harbor_migrator_quote_identifier($table, harbor_migrator_sql_dialect($connection));
    $query = sprintf('SELECT name FROM %s ORDER BY name ASC', $table_identifier);

    if ($connection instanceof mysqli) {
        $result = $connection->query($query);
        if (false === $result) {
            throw new RuntimeException('Failed to fetch executed entries: '.$connection->error);
        }

        $names = [];
        while ($row = $result->fetch_assoc()) {
            if (is_array($row) && is_string($row['name'] ?? null)) {
                $names[] = $row['name'];
            }
        }

        $result->free();

        return $names;
    }

    $statement = $connection->query($query);
    if (! $statement instanceof PDOStatement) {
        throw new RuntimeException('Failed to fetch executed entries.');
    }

    $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
    if (! is_array($rows)) {
        return [];
    }

    $names = [];
    foreach ($rows as $row) {
        if (is_array($row) && is_string($row['name'] ?? null)) {
            $names[] = $row['name'];
        }
    }

    return $names;
}

/**
 * @return array<int, string>
 */
function harbor_migrator_fetch_batch_names(mysqli|PDO $connection, string $table, int $batch): array
{
    $table_identifier = harbor_migrator_quote_identifier($table, harbor_migrator_sql_dialect($connection));

    if ($connection instanceof mysqli) {
        $query = sprintf('SELECT name FROM %s WHERE batch = %d ORDER BY name DESC', $table_identifier, $batch);
        $result = $connection->query($query);
        if (false === $result) {
            throw new RuntimeException('Failed to fetch rollback batch entries: '.$connection->error);
        }

        $names = [];
        while ($row = $result->fetch_assoc()) {
            if (is_array($row) && is_string($row['name'] ?? null)) {
                $names[] = $row['name'];
            }
        }

        $result->free();

        return $names;
    }

    $query = sprintf('SELECT name FROM %s WHERE batch = :batch ORDER BY name DESC', $table_identifier);
    $statement = $connection->prepare($query);
    if (! $statement instanceof PDOStatement) {
        throw new RuntimeException('Failed to prepare rollback batch query.');
    }

    if (false === $statement->execute(['batch' => $batch])) {
        throw new RuntimeException('Failed to execute rollback batch query.');
    }

    $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
    if (! is_array($rows)) {
        return [];
    }

    $names = [];
    foreach ($rows as $row) {
        if (is_array($row) && is_string($row['name'] ?? null)) {
            $names[] = $row['name'];
        }
    }

    return $names;
}

function harbor_migrator_insert_executed(mysqli|PDO $connection, string $table, string $name, int $batch): void
{
    $table_identifier = harbor_migrator_quote_identifier($table, harbor_migrator_sql_dialect($connection));

    if ($connection instanceof mysqli) {
        $escaped_name = $connection->real_escape_string($name);
        $query = sprintf(
            "INSERT INTO %s (name, batch) VALUES ('%s', %d)",
            $table_identifier,
            $escaped_name,
            $batch
        );

        if (! $connection->query($query)) {
            throw new RuntimeException('Failed to insert executed entry: '.$connection->error);
        }

        return;
    }

    $query = sprintf('INSERT INTO %s (name, batch) VALUES (:name, :batch)', $table_identifier);
    $statement = $connection->prepare($query);
    if (! $statement instanceof PDOStatement) {
        throw new RuntimeException('Failed to prepare executed entry insert.');
    }

    if (false === $statement->execute(['name' => $name, 'batch' => $batch])) {
        throw new RuntimeException('Failed to insert executed entry.');
    }
}

function harbor_migrator_delete_executed(mysqli|PDO $connection, string $table, string $name): void
{
    $table_identifier = harbor_migrator_quote_identifier($table, harbor_migrator_sql_dialect($connection));

    if ($connection instanceof mysqli) {
        $escaped_name = $connection->real_escape_string($name);
        $query = sprintf(
            "DELETE FROM %s WHERE name = '%s'",
            $table_identifier,
            $escaped_name
        );

        if (! $connection->query($query)) {
            throw new RuntimeException('Failed to delete executed entry: '.$connection->error);
        }

        return;
    }

    $query = sprintf('DELETE FROM %s WHERE name = :name', $table_identifier);
    $statement = $connection->prepare($query);
    if (! $statement instanceof PDOStatement) {
        throw new RuntimeException('Failed to prepare executed entry delete.');
    }

    if (false === $statement->execute(['name' => $name])) {
        throw new RuntimeException('Failed to delete executed entry.');
    }
}

function harbor_migrator_sql_dialect(mysqli|PDO $connection): string
{
    if ($connection instanceof mysqli) {
        return 'mysql';
    }

    $driver_name = strtolower((string) $connection->getAttribute(PDO::ATTR_DRIVER_NAME));

    return 'sqlite' === $driver_name ? 'sqlite' : 'mysql';
}

function harbor_migrator_quote_identifier(string $identifier, string $dialect): string
{
    if (! harbor_migrator_is_valid_identifier($identifier)) {
        throw new RuntimeException(
            sprintf('Invalid SQL identifier "%s". Use letters, numbers, and underscores only.', $identifier)
        );
    }

    if ('sqlite' === $dialect) {
        return '"'.$identifier.'"';
    }

    return '`'.$identifier.'`';
}

function harbor_migrator_is_valid_identifier(string $identifier): bool
{
    return 1 === preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier);
}

function harbor_migrator_exec_sql(mysqli|PDO $connection, string $query): void
{
    if ($connection instanceof mysqli) {
        if (! $connection->query($query)) {
            throw new RuntimeException('SQL execution failed: '.$connection->error);
        }

        return;
    }

    $executed = $connection->exec($query);
    if (false === $executed) {
        throw new RuntimeException('SQL execution failed.');
    }
}

function harbor_migrator_require_file_instance(string $file_path): object
{
    $instance = require $file_path;
    if (! is_object($instance)) {
        throw new RuntimeException(sprintf('Migration file must return an object: %s', $file_path));
    }

    if (! method_exists($instance, 'up') || ! method_exists($instance, 'down')) {
        throw new RuntimeException(sprintf('Migration object must define up() and down(): %s', $file_path));
    }

    return $instance;
}
