<?php

declare(strict_types=1);

namespace Harbor\Database\Schema;

require_once __DIR__.'/../../Support/value.php';

use function Harbor\Support\harbor_is_blank;

/**
 * Class Column.
 */
final class Column
{
    /**
     * @param array<int, mixed>    $arguments
     * @param array<string, mixed> $modifiers
     */
    private function __construct(
        private readonly string $type,
        private readonly array $arguments = [],
        private array $modifiers = []
    ) {
        if (harbor_is_blank(trim($this->type))) {
            throw new \InvalidArgumentException('Column type cannot be empty.');
        }
    }

    public static function tiny_int(): self
    {
        return new self('tiny_int');
    }

    public static function small_int(): self
    {
        return new self('small_int');
    }

    public static function int(): self
    {
        return new self('int');
    }

    public static function big_int(): self
    {
        return new self('big_int');
    }

    public static function decimal(int $precision = 10, int $scale = 0): self
    {
        return new self('decimal', [$precision, $scale]);
    }

    public static function float(): self
    {
        return new self('float');
    }

    public static function double(): self
    {
        return new self('double');
    }

    public static function bool(): self
    {
        return new self('bool');
    }

    public static function char(int $length = 255): self
    {
        return new self('char', [$length]);
    }

    public static function varchar(int $length = 255): self
    {
        return new self('varchar', [$length]);
    }

    public static function text(): self
    {
        return new self('text');
    }

    public static function medium_text(): self
    {
        return new self('medium_text');
    }

    public static function long_text(): self
    {
        return new self('long_text');
    }

    public static function json(): self
    {
        return new self('json');
    }

    public static function date(): self
    {
        return new self('date');
    }

    public static function time(): self
    {
        return new self('time');
    }

    public static function datetime(): self
    {
        return new self('datetime');
    }

    public static function timestamp(): self
    {
        return new self('timestamp');
    }

    public static function year(): self
    {
        return new self('year');
    }

    public static function binary(int $length = 255): self
    {
        return new self('binary', [$length]);
    }

    public static function varbinary(int $length = 255): self
    {
        return new self('varbinary', [$length]);
    }

    public static function blob(): self
    {
        return new self('blob');
    }

    public static function long_blob(): self
    {
        return new self('long_blob');
    }

    public static function uuid(): self
    {
        return new self('uuid');
    }

    public static function ulid(): self
    {
        return new self('ulid');
    }

    /**
     * @param array<int, string> $allowed_values
     */
    public static function enum(array $allowed_values): self
    {
        if (empty($allowed_values)) {
            throw new \InvalidArgumentException('Column enum values cannot be empty.');
        }

        return new self('enum', [$allowed_values]);
    }

    /**
     * @param array<int, string> $allowed_values
     */
    public static function set(array $allowed_values): self
    {
        if (empty($allowed_values)) {
            throw new \InvalidArgumentException('Column set values cannot be empty.');
        }

        return new self('set', [$allowed_values]);
    }

    public function nullable(bool $value = true): self
    {
        $this->modifiers['nullable'] = $value;

        return $this;
    }

    public function default(mixed $value): self
    {
        $this->modifiers['default'] = $value;

        return $this;
    }

    public function default_expression(string $expression): self
    {
        if (harbor_is_blank(trim($expression))) {
            throw new \InvalidArgumentException('Default expression cannot be empty.');
        }

        $this->modifiers['default_expression'] = trim($expression);

        return $this;
    }

    public function after(string $column_name): self
    {
        if (harbor_is_blank(trim($column_name))) {
            throw new \InvalidArgumentException('Column "after" target cannot be empty.');
        }

        $this->modifiers['after'] = trim($column_name);

        return $this;
    }

    public function first(): self
    {
        $this->modifiers['first'] = true;

        return $this;
    }

    public function unsigned(): self
    {
        $this->modifiers['unsigned'] = true;

        return $this;
    }

    public function auto_increment(): self
    {
        $this->modifiers['auto_increment'] = true;

        return $this;
    }

    public function primary(): self
    {
        $this->modifiers['primary'] = true;

        return $this;
    }

    public function unique(?string $index_name = null): self
    {
        if (is_string($index_name) && harbor_is_blank(trim($index_name))) {
            throw new \InvalidArgumentException('Column unique index name cannot be blank.');
        }

        $this->modifiers['unique'] = $index_name ?? true;

        return $this;
    }

    public function index(?string $index_name = null): self
    {
        if (is_string($index_name) && harbor_is_blank(trim($index_name))) {
            throw new \InvalidArgumentException('Column index name cannot be blank.');
        }

        $this->modifiers['index'] = $index_name ?? true;

        return $this;
    }

    public function comment(string $text): self
    {
        if (harbor_is_blank(trim($text))) {
            throw new \InvalidArgumentException('Column comment cannot be empty.');
        }

        $this->modifiers['comment'] = trim($text);

        return $this;
    }

    public function charset(string $charset): self
    {
        if (harbor_is_blank(trim($charset))) {
            throw new \InvalidArgumentException('Column charset cannot be empty.');
        }

        $this->modifiers['charset'] = trim($charset);

        return $this;
    }

    public function collation(string $collation): self
    {
        if (harbor_is_blank(trim($collation))) {
            throw new \InvalidArgumentException('Column collation cannot be empty.');
        }

        $this->modifiers['collation'] = trim($collation);

        return $this;
    }

    public function use_current(): self
    {
        $this->modifiers['use_current'] = true;

        return $this;
    }

    public function use_current_on_update(): self
    {
        $this->modifiers['use_current_on_update'] = true;

        return $this;
    }

    public function check(string $expression): self
    {
        if (harbor_is_blank(trim($expression))) {
            throw new \InvalidArgumentException('Column check expression cannot be empty.');
        }

        $this->modifiers['check'] = trim($expression);

        return $this;
    }

    public function virtual_as(string $expression): self
    {
        if (harbor_is_blank(trim($expression))) {
            throw new \InvalidArgumentException('Column virtual expression cannot be empty.');
        }

        $this->modifiers['virtual_as'] = trim($expression);

        return $this;
    }

    public function stored_as(string $expression): self
    {
        if (harbor_is_blank(trim($expression))) {
            throw new \InvalidArgumentException('Column stored expression cannot be empty.');
        }

        $this->modifiers['stored_as'] = trim($expression);

        return $this;
    }

    public function type(): string
    {
        return $this->type;
    }

    /**
     * @return array<int, mixed>
     */
    public function arguments(): array
    {
        return $this->arguments;
    }

    /**
     * @return array<string, mixed>
     */
    public function modifiers(): array
    {
        return $this->modifiers;
    }

    /**
     * @return array{
     *   type: string,
     *   arguments: array<int, mixed>,
     *   modifiers: array<string, mixed>
     * }
     */
    public function to_array(): array
    {
        return [
            'type' => $this->type(),
            'arguments' => $this->arguments(),
            'modifiers' => $this->modifiers(),
        ];
    }
}
