<?php

declare(strict_types=1);

namespace Harbor\Database\Schema;

require_once __DIR__.'/../../Support/value.php';

use function Harbor\Support\harbor_is_blank;

/**
 * Class ForeignKey.
 */
final class ForeignKey
{
    /**
     * @param array<int, string> $columns
     * @param array<int, string> $references
     */
    private function __construct(
        private readonly array $columns,
        private array $references = [],
        private ?string $table = null,
        private ?string $name = null,
        private ?string $on_delete = null,
        private ?string $on_update = null,
        private ?bool $deferrable = null,
        private ?bool $initially_deferred = null,
    ) {
        if (empty($this->columns)) {
            throw new \InvalidArgumentException('Foreign key columns cannot be empty.');
        }
    }

    public static function from(array|string $columns): self
    {
        $normalized_columns = self::normalize_columns($columns, 'Foreign key columns');

        return new self($normalized_columns);
    }

    public function references(array|string $columns): self
    {
        $this->references = self::normalize_columns($columns, 'Foreign key reference columns');

        return $this;
    }

    public function on(string $table): self
    {
        if (harbor_is_blank(trim($table))) {
            throw new \InvalidArgumentException('Foreign key target table cannot be empty.');
        }

        $this->table = trim($table);

        return $this;
    }

    public function name(string $constraint_name): self
    {
        if (harbor_is_blank(trim($constraint_name))) {
            throw new \InvalidArgumentException('Foreign key constraint name cannot be empty.');
        }

        $this->name = trim($constraint_name);

        return $this;
    }

    public function on_delete(string $action): self
    {
        $this->on_delete = self::normalize_action($action, 'on delete');

        return $this;
    }

    public function on_update(string $action): self
    {
        $this->on_update = self::normalize_action($action, 'on update');

        return $this;
    }

    public function deferrable(bool $value = true): self
    {
        $this->deferrable = $value;

        return $this;
    }

    public function initially_deferred(bool $value = true): self
    {
        $this->initially_deferred = $value;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function columns(): array
    {
        return $this->columns;
    }

    /**
     * @return array<int, string>
     */
    public function references_columns(): array
    {
        return $this->references;
    }

    public function table(): ?string
    {
        return $this->table;
    }

    public function constraint_name(): ?string
    {
        return $this->name;
    }

    public function on_delete_action(): ?string
    {
        return $this->on_delete;
    }

    public function on_update_action(): ?string
    {
        return $this->on_update;
    }

    public function is_deferrable(): ?bool
    {
        return $this->deferrable;
    }

    public function is_initially_deferred(): ?bool
    {
        return $this->initially_deferred;
    }

    /**
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
    public function to_array(): array
    {
        return [
            'columns' => $this->columns(),
            'references' => $this->references_columns(),
            'table' => $this->table(),
            'name' => $this->constraint_name(),
            'on_delete' => $this->on_delete_action(),
            'on_update' => $this->on_update_action(),
            'deferrable' => $this->is_deferrable(),
            'initially_deferred' => $this->is_initially_deferred(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function normalize_columns(array|string $columns, string $label): array
    {
        $source_columns = is_array($columns) ? $columns : [$columns];

        if (empty($source_columns)) {
            throw new \InvalidArgumentException($label.' cannot be empty.');
        }

        $normalized = [];

        foreach ($source_columns as $column) {
            if (! is_string($column) || harbor_is_blank(trim($column))) {
                throw new \InvalidArgumentException($label.' must contain only non-empty strings.');
            }

            $normalized[] = trim($column);
        }

        return $normalized;
    }

    private static function normalize_action(string $action, string $label): string
    {
        $normalized_action = strtolower(trim($action));
        if (harbor_is_blank($normalized_action)) {
            throw new \InvalidArgumentException(sprintf('Foreign key %s action cannot be empty.', $label));
        }

        return match ($normalized_action) {
            'restrict' => 'RESTRICT',
            'cascade' => 'CASCADE',
            'set_null' => 'SET NULL',
            'no_action' => 'NO ACTION',
            default => throw new \InvalidArgumentException(
                sprintf('Unsupported foreign key %s action "%s".', $label, $action)
            ),
        };
    }
}
