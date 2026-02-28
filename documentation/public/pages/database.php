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
function db_mysqli_execute(mysqli $connection, string $sql): bool
function db_mysqli_array(mysqli $connection, string $sql): array
function db_mysqli_first(mysqli $connection, string $sql): array
function db_mysqli_last(mysqli $connection, string $sql): array
function db_mysqli_objects(mysqli $connection, string $sql): array</code></pre>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
