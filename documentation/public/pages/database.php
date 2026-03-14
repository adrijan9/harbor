<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Database Helpers';
$page_description = 'Database helper wrappers for SQLite, MySQL PDO, MySQLi, and config-driven resolver usage.';
$page_id = 'database';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">namespace: db</span>
    <h1>Database Helpers</h1>
    <p>Use small query wrappers for SQLite, MySQL PDO, and MySQLi with one optional driver resolver API.</p>
</section>

<section class="docs-section">
    <h2>Resolver Setup (Required)</h2>
    <p>Skip this setup if you only call driver-specific helpers (<code>db_sqlite_*</code>, <code>db_mysql_*</code>, <code>db_mysqli_*</code>) directly.</p>
    <p>You can create <code>config/database.php</code> manually, or publish it using <code>bin/harbor-config</code> from your site directory.</p>
    <h3>1. Create <code>config/database.php</code></h3>
    <pre><code class="language-bash">cd my-site
../bin/harbor-config
# choose: database.php</code></pre>
    <pre><code class="language-php">&lt;?php

declare(strict_types=1);

use Harbor\Database\DbDriver;

return [
    'driver' => DbDriver::SQLITE->value,
    'sqlite' => [
        'path' => __DIR__.'/../storage/app.sqlite',
    ],
    'mysql' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'user' => 'root',
        'password' => '',
        'database' => 'app_db',
        'charset' => 'utf8mb4',
    ],
];</code></pre>
    <h3>2. Load It In Bootstrap</h3>
    <pre><code class="language-php">use function Harbor\Config\config_init;

// Example: in public/index.php before db_connect()/db_driver()
config_init(__DIR__.'/../config/database.php');</code></pre>
    <h3>What it does</h3>
    <p>Resolver helpers read <code>db.*</code> keys (with <code>database.*</code> fallback) from runtime config.</p>
</section>

<section class="docs-section">
    <h2>Resolver Helpers</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\Database\DbDriver;
use function Harbor\Database\db_array;
use function Harbor\Database\db_connect;
use function Harbor\Database\db_execute;

$connection = db_connect(DbDriver::SQLITE, [
    'sqlite' => [
        'path' => __DIR__.'/../storage/app.sqlite',
    ],
]);

db_execute($connection, 'CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, name TEXT)');
db_execute($connection, 'INSERT INTO users (name) VALUES (:name)', ['name' => 'Ada']);
$rows = db_array($connection, 'SELECT id, name FROM users ORDER BY id ASC');</code></pre>
    <h3>What it does</h3>
    <p>Resolves the active driver from explicit input or config (<code>db.*</code>/<code>database.*</code>) and dispatches execute/array/object calls to the matching backend wrapper.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Resolver API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">use Harbor\Database\DbDriver;

function db_driver(string|DbDriver $default_driver = DbDriver::SQLITE): string
function db_is_sqlite(): bool
function db_is_mysql(): bool
function db_is_mysqli(): bool

function db_connect(string|DbDriver|null $driver = null, array $config = []): PDO|mysqli
function db_execute(PDO|mysqli $connection, string $sql, array $bindings = []): bool
function db_array(PDO|mysqli $connection, string $sql, array $bindings = []): array
function db_first(PDO|mysqli $connection, string $sql, array $bindings = []): array
function db_last(PDO|mysqli $connection, string $sql, array $bindings = []): array
function db_objects(PDO|mysqli $connection, string $sql, array $bindings = []): array
function db_close(PDO|mysqli $connection): bool</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Schema Builder</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\Database\Schema\Column;
use Harbor\Database\Schema\ForeignKey;
use function Harbor\Database\db_connect;
use function Harbor\Database\schema_add_column;
use function Harbor\Database\schema_add_foreign;
use function Harbor\Database\schema_builder_alter;
use function Harbor\Database\schema_builder_create;
use function Harbor\Database\schema_execute;

$connection = db_connect();

$builder = schema_builder_create('users', true);
$builder = schema_add_column($builder, 'id', Column::int()->primary()->auto_increment());
$builder = schema_add_column($builder, 'email', Column::varchar(190));
$builder = schema_add_column($builder, 'status', Column::varchar(30)->default('active'));

schema_execute($connection, $builder);

$foreign_builder = schema_builder_alter('posts');
$foreign_builder = schema_add_foreign(
    $foreign_builder,
    ForeignKey::from('user_id')
        ->references('id')
        ->on('users')
        ->on_delete('cascade')
);

schema_execute($connection, $foreign_builder);</code></pre>
    <h3>What it does</h3>
    <p>Provides a class-driven schema DSL for table creation and migration-like alter operations while keeping execution inside the Database module.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Schema API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">use Harbor\Database\Schema\Column;
use Harbor\Database\Schema\ForeignKey;

function schema_builder_alter(string $table): array
function schema_builder_create(string $table, bool $if_not_exists = false): array
function schema_builder_drop(string $table, bool $if_exists = true): array
function schema_builder_rename(string $from, string $to): array

function schema_add_column(array $builder, string $name, Column $column): array
function schema_change_column(array $builder, string $name, Column $column): array
function schema_drop_column(array $builder, string $name): array
function schema_rename_column(array $builder, string $from, string $to): array

function schema_add_primary(array $builder, array $columns, ?string $name = null): array
function schema_drop_primary(array $builder, ?string $name = null): array
function schema_add_unique(array $builder, string $name, array $columns): array
function schema_drop_unique(array $builder, string $name): array
function schema_add_index(
    array $builder,
    string $name,
    array $columns,
    bool $unique = false,
    bool $if_not_exists = false
): array
function schema_drop_index(array $builder, string $name, bool $if_exists = false): array

function schema_add_foreign(array $builder, ForeignKey $foreign_key): array
function schema_drop_foreign(array $builder, string $name): array

function schema_statements(array $builder, ?string $driver = null): array
function schema_execute(PDO|mysqli $connection, array $builder, ?string $driver = null): bool</code></pre>
        </div>
    </details>
    <details class="api-details">
        <summary class="api-summary">
            <span>Column &amp; ForeignKey DSL</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">final class Column
{
    public static function int(): self
    public static function big_int(): self
    public static function varchar(int $length = 255): self
    public static function text(): self
    public static function json(): self
    public static function datetime(): self
    public static function enum(array $allowed_values): self
    public static function set(array $allowed_values): self

    public function nullable(bool $value = true): self
    public function default(mixed $value): self
    public function default_expression(string $expression): self
    public function after(string $column_name): self
    public function first(): self
    public function unsigned(): self
    public function auto_increment(): self
    public function primary(): self
    public function unique(?string $index_name = null): self
    public function index(?string $index_name = null): self
}

final class ForeignKey
{
    public static function from(string|array $columns): self
    public function references(string|array $columns): self
    public function on(string $table): self
    public function name(string $constraint_name): self
    public function on_delete(string $action): self
    public function on_update(string $action): self
}</code></pre>
        </div>
    </details>
    <p>Important: modifiers like <code>after()</code>, <code>first()</code>, generated columns, and some idempotent index clauses are driver/version dependent and may throw explicit exceptions when unsupported.</p>
</section>

<section class="docs-section">
    <h2>Query Builder</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\Database\QueryBuilder\QueryBuilder;
use function Harbor\Database\db_array;
use function Harbor\Database\db_driver;
use function Harbor\Database\db_execute;
use function Harbor\Database\query_insert;
use function Harbor\Database\query_select;

$insert = query_insert('users')->values([
    'email' => 'ada@example.com',
    'status' => 'active',
]);

db_execute($connection, $insert->get_sql(db_driver()), $insert->get_bindings());

$select = query_select('users')
    ->columns('id', 'email')
    ->where('status', '=', 'active')
    ->order_by('id', 'desc')
    ->limit(20);

$rows = db_array($connection, $select->get_sql(db_driver()), $select->get_bindings());

$sql_string_mode = QueryBuilder::update('users')
    ->set('status', 'inactive')
    ->where('id', '=', 10)
    ->build(db_driver());

db_execute($connection, $sql_string_mode);</code></pre>
    <h3>What it does</h3>
    <p>Provides a fluent SQL builder for <code>SELECT</code>, <code>INSERT</code>, <code>UPDATE</code>, and <code>DELETE</code> with one-statement output per build and optional binding-aware execution.</p>
    <h3>Outputs</h3>
    <ul>
        <li><code>get_sql(?string $driver = null): string</code> returns SQL with <code>?</code> placeholders.</li>
        <li><code>get_bindings(): array</code> returns ordered bindings for that SQL.</li>
        <li><code>build(?string $driver = null): string</code> returns a fully interpolated SQL string.</li>
    </ul>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Query Entry Points</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">use Harbor\Database\QueryBuilder\QueryBuilder;

function query_select(?string $table = null): QueryBuilder
function query_insert(?string $table = null): QueryBuilder
function query_update(?string $table = null): QueryBuilder
function query_delete(?string $table = null): QueryBuilder

QueryBuilder::select(?string $table = null)
QueryBuilder::insert(?string $table = null)
QueryBuilder::update(?string $table = null)
QueryBuilder::delete(?string $table = null)</code></pre>
        </div>
    </details>
    <details class="api-details">
        <summary class="api-summary">
            <span>Core Fluent API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">// shared
->from(string $table)
->into(string $table)
->table(string $table)
->as(string $alias)
->when(bool|callable $condition, callable $then, ?callable $else = null)

->where(string $column, string $operator, mixed $value)
->or_where(string $column, string $operator, mixed $value)
->where_column(string $left, string $operator, string $right)
->or_where_column(string $left, string $operator, string $right)
->where_like(string $column, string $pattern, bool $case_sensitive = false)
->or_where_like(string $column, string $pattern, bool $case_sensitive = false)
->where_group(callable $callback)
->or_where_group(callable $callback)
->where_in(string $column, array $values)
->where_not_in(string $column, array $values)
->where_null(string $column)
->where_not_null(string $column)
->where_between(string $column, mixed $from, mixed $to)
->where_raw(QueryExpression|string $sql, array $bindings = [])
->where_exists(QueryBuilder $sub)
->where_in_sub(string $column, QueryBuilder $sub)

// date-part predicates
->where_date(string $column, string $operator, string $date_ymd)
->where_year(string $column, string $operator, int $year)
->where_month(string $column, string $operator, int $month)
->where_day(string $column, string $operator, int $day)

// select
->columns(string ...$columns)
->select_raw(QueryExpression|string $sql, array $bindings = [])
->select_sub(QueryBuilder $sub, string $alias)
->from_sub(QueryBuilder $sub, string $alias)
->join(string $table, string $left, string $operator, string $right)
->left_join(string $table, string $left, string $operator, string $right)
->right_join(string $table, string $left, string $operator, string $right)
->cross_join(string $table)
->join_sub(QueryBuilder $sub, string $alias, string $left, string $operator, string $right)
->left_join_sub(QueryBuilder $sub, string $alias, string $left, string $operator, string $right)
->right_join_sub(QueryBuilder $sub, string $alias, string $left, string $operator, string $right)
->group_by(string ...$columns)
->having(string $column, string $operator, mixed $value)
->having_raw(QueryExpression|string $sql, array $bindings = [])
->order_by(string $column, string $direction = 'asc')
->order_by_raw(QueryExpression|string $sql)
->distinct(bool $value = true)
->count(string $column = '*', string $alias = 'count')
->sum(string $column, string $alias = 'sum')
->avg(string $column, string $alias = 'avg')
->min(string $column, string $alias = 'min')
->max(string $column, string $alias = 'max')
->union(QueryBuilder $query)
->union_all(QueryBuilder $query)
->limit(int $limit)
->offset(int $offset)
->for_page(int $page, int $per_page)

// locks
->lock_for_update()
->lock_for_share()
->lock_prefix(QueryExpression|string $fragment)
->lock_suffix(QueryExpression|string $fragment)
->clear_lock()

// insert
->values(array $row)
->rows(array $rows)
->ignore(bool $value = true)
->upsert(array $rows, array $unique_by, array $update_columns = [])
->on_conflict(array $columns)
->do_nothing()
->do_update(array $columns)
->on_duplicate_key_update(array $columns)

// update
->set(string $column, mixed $value)
->set_many(array $values)
->increment(string $column, int|float $by = 1)
->decrement(string $column, int|float $by = 1)

// delete safety
->allow_full_table(bool $allow = true)

// output
->get_sql(?string $driver = null): string
->get_bindings(): array
->build(?string $driver = null): string</code></pre>
        </div>
    </details>
    <details class="api-details">
        <summary class="api-summary">
            <span>QueryExpression</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">use Harbor\Database\QueryBuilder\QueryExpression;

QueryExpression::raw(string $sql, array $bindings = []): QueryExpression</code></pre>
        </div>
    </details>
    <p>Important: <code>QueryExpression::raw()</code> is trusted SQL only and rejects unsafe tokens such as statement delimiters/comments. Prefer placeholders + bindings for user input.</p>
    <p>Important: <code>RETURNING</code> is supported for SQLite and rejected for MySQL/MySQLi. Row lock clauses are rejected for SQLite.</p>
</section>

<section class="docs-section">
    <h2>DTO Connection Setup</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\Database\MysqlDto;
use Harbor\Database\SqliteDto;
use function Harbor\Database\db_mysqli_connect_dto;
use function Harbor\Database\db_mysql_connect_dto;
use function Harbor\Database\db_sqlite_connect_dto;

$sqlite = SqliteDto::from_config();
$sqlite_connection = db_sqlite_connect_dto($sqlite);

$mysql = MysqlDto::from_config();
$mysql_pdo_connection = db_mysql_connect_dto($mysql);
$mysqli_connection = db_mysqli_connect_dto($mysql);</code></pre>
    <h3>What it does</h3>
    <p>Encapsulates connection parameters in DTOs so connection creation is reusable and config resolution can happen in one place.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>DTO API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">final class SqliteDto
{
    public static function make(string $database_path, array $options = []): self
    public static function from_config(array $config = []): self
}

final class MysqlDto
{
    public static function make(
        string $host,
        string $user,
        string $password,
        string $database,
        int $port = 3306,
        string $charset = 'utf8mb4',
        array $options = []
    ): self

    public static function from_config(array $config = []): self
}</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>SQLite (PDO)</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Database\db_sqlite_array;
use function Harbor\Database\db_sqlite_connect;
use function Harbor\Database\db_sqlite_execute;
use function Harbor\Database\db_sqlite_objects;

$connection = db_sqlite_connect(__DIR__.'/../storage/harbor.sqlite');

db_sqlite_execute($connection, 'CREATE TABLE IF NOT EXISTS posts (id INTEGER PRIMARY KEY, title TEXT)');
db_sqlite_execute($connection, 'INSERT INTO posts (title) VALUES (:title)', ['title' => 'Hello']);

$array_rows = db_sqlite_array($connection, 'SELECT id, title FROM posts ORDER BY id ASC');
$object_rows = db_sqlite_objects($connection, 'SELECT id, title FROM posts ORDER BY id ASC');</code></pre>
    <h3>What it does</h3>
    <p>Creates a SQLite PDO connection and runs prepared statements with optional bindings, returning associative arrays or objects.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>SQLite API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function db_sqlite_connect(string $database_path, array $options = []): PDO
function db_sqlite_connect_dto(SqliteDto $dto): PDO
function db_sqlite_close(PDO $connection): bool
function db_sqlite_execute(PDO $connection, string $sql, array $bindings = []): bool
function db_sqlite_array(PDO $connection, string $sql, array $bindings = []): array
function db_sqlite_first(PDO $connection, string $sql, array $bindings = []): array
function db_sqlite_last(PDO $connection, string $sql, array $bindings = []): array
function db_sqlite_objects(PDO $connection, string $sql, array $bindings = []): array</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>MySQL (PDO)</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Database\db_mysql_array;
use function Harbor\Database\db_mysql_connect;
use function Harbor\Database\db_mysql_execute;
use function Harbor\Database\db_mysql_objects;

$connection = db_mysql_connect('127.0.0.1', 'root', '', 'app_db');

db_mysql_execute($connection, 'INSERT INTO users (name) VALUES (:name)', ['name' => 'Linus']);
$array_rows = db_mysql_array($connection, 'SELECT id, name FROM users ORDER BY id ASC');
$object_rows = db_mysql_objects($connection, 'SELECT id, name FROM users ORDER BY id ASC');</code></pre>
    <h3>What it does</h3>
    <p>Provides a focused MySQL PDO wrapper with parameter bindings and query-result conversion helpers.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>MySQL PDO API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function db_mysql_connect(
    string $host,
    string $user,
    string $password,
    string $database,
    int $port = 3306,
    string $charset = 'utf8mb4',
    array $options = []
): PDO

function db_mysql_connect_dto(MysqlDto $dto): PDO
function db_mysql_pdo_close(PDO $connection): bool
function db_mysql_execute(PDO $connection, string $sql, array $bindings = []): bool
function db_mysql_array(PDO $connection, string $sql, array $bindings = []): array
function db_mysql_first(PDO $connection, string $sql, array $bindings = []): array
function db_mysql_last(PDO $connection, string $sql, array $bindings = []): array
function db_mysql_objects(PDO $connection, string $sql, array $bindings = []): array</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>MySQLi</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Database\db_mysqli_array;
use function Harbor\Database\db_mysqli_connect;
use function Harbor\Database\db_mysqli_execute;
use function Harbor\Database\db_mysqli_objects;

$connection = db_mysqli_connect('127.0.0.1', 'root', '', 'app_db');

db_mysqli_execute($connection, "INSERT INTO users (name) VALUES ('Grace')");
$array_rows = db_mysqli_array($connection, 'SELECT id, name FROM users ORDER BY id ASC');
$object_rows = db_mysqli_objects($connection, 'SELECT id, name FROM users ORDER BY id ASC');</code></pre>
    <h3>What it does</h3>
    <p>Wraps raw MySQLi query execution and row mapping. This wrapper currently executes SQL directly and does not accept bindings.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>MySQLi API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function db_mysqli_connect(
    string $host,
    string $user,
    string $password,
    string $database,
    int $port = 3306,
    string $charset = 'utf8mb4'
): mysqli

function db_mysqli_connect_dto(MysqlDto $dto): mysqli
function db_mysqli_close(mysqli $connection): bool
function db_mysqli_execute(mysqli $connection, string $sql): bool
function db_mysqli_array(mysqli $connection, string $sql): array
function db_mysqli_first(mysqli $connection, string $sql): array
function db_mysqli_last(mysqli $connection, string $sql): array
function db_mysqli_objects(mysqli $connection, string $sql): array</code></pre>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
