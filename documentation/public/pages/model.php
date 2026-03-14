<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Model Pattern';
$page_description = 'Harbor model approach: no built-in ORM, optional plain PHP model classes.';
$page_id = 'model';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Pattern</span>
    <h1>Model</h1>
    <p>Harbor does not ship an ORM. Use small, explicit PHP classes when you want model-like structure.</p>
</section>

<section class="docs-section">
    <h2>Why No ORM</h2>
    <p>Harbor is designed for simplicity, readability, and an open, non-gatekeeping environment.</p>
    <p>A full ORM usually combines connection handling, query building, state tracking, relations, events, and more inside one abstraction layer. Harbor keeps those concerns explicit instead of forcing a heavy model base class.</p>
</section>

<section class="docs-section">
    <h2>Simple Model Class</h2>
    <h3>Example</h3>
    <pre><code class="language-php">&lt;?php

declare(strict_types=1);

namespace App\Models;

use function Harbor\Database\db_first;
use function Harbor\Database\query_select;

final class User
{
    public function __construct(
        private readonly \PDO|\mysqli $connection,
    ) {
    }

    public function find(int $id): array
    {
        $query = query_select('users')
            ->columns('id', 'email', 'status')
            ->where('id', '=', $id)
            ->limit(1);

        return db_first(
            $this->connection,
            $query->get_sql(),
            $query->get_bindings()
        );
    }

    public function find_by_email(string $email): array
    {
        $query = query_select('users')
            ->columns('id', 'email', 'status')
            ->where('email', '=', $email)
            ->limit(1);

        return db_first(
            $this->connection,
            $query->get_sql(),
            $query->get_bindings()
        );
    }
}</code></pre>
    <h3>What it does</h3>
    <p>Keeps domain methods close to your table logic without adding ORM lifecycle complexity, while still using Harbor QueryBuilder for readable SQL composition.</p>
</section>

<section class="docs-section">
    <h2>Optional Abstract Base</h2>
    <h3>Example</h3>
    <pre><code class="language-php">&lt;?php

declare(strict_types=1);

namespace App\Models;

use Harbor\Database\QueryBuilder\QueryBuilder;
use function Harbor\Database\db_array;
use function Harbor\Database\db_first;
use function Harbor\Database\query_select;

abstract class BaseModel
{
    public function __construct(
        protected readonly \PDO|\mysqli $connection,
    ) {
    }

    abstract protected function table(): string;

    protected function query(): QueryBuilder
    {
        return query_select($this->table());
    }

    protected function one(QueryBuilder $query): array
    {
        return db_first(
            $this->connection,
            $query->get_sql(),
            $query->get_bindings()
        );
    }

    protected function many(QueryBuilder $query): array
    {
        return db_array(
            $this->connection,
            $query->get_sql(),
            $query->get_bindings()
        );
    }
}

final class UserModel extends BaseModel
{
    protected function table(): string
    {
        return 'users';
    }

    public function find(int $id): array
    {
        return $this->one(
            $this->query()
                ->columns('id', 'email', 'status')
                ->where('id', '=', $id)
                ->limit(1)
        );
    }

    public function active_users(): array
    {
        return $this->many(
            $this->query()
                ->columns('id', 'email')
                ->where('status', '=', 'active')
                ->order_by('id', 'desc')
        );
    }
}</code></pre>
    <h3>What it does</h3>
    <p>Shares connection and helper behavior while keeping each model class small and explicit.</p>
</section>

<section class="docs-section">
    <h2>Guidelines</h2>
    <ul>
        <li>Keep models focused on one table or aggregate boundary.</li>
        <li>Return simple arrays/DTOs; avoid hidden global state.</li>
        <li>Use parameter bindings for user input.</li>
        <li>If logic grows large, move shared parts into small services instead of building a full ORM layer.</li>
    </ul>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
