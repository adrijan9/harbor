<?php

declare(strict_types=1);

namespace Harbor\Database\QueryBuilder;

require_once __DIR__.'/../../Support/value.php';

use function Harbor\Support\harbor_is_blank;

/**
 * Class QueryExpression.
 */
final class QueryExpression
{
    /**
     * @param array<int, mixed> $bindings
     */
    private function __construct(
        private readonly string $sql,
        private readonly array $bindings = [],
    ) {
        self::assert_safe_sql_fragment($this->sql, 'Query expression');
    }

    /**
     * @param array<int, mixed> $bindings
     */
    public static function raw(string $sql, array $bindings = []): self
    {
        $normalized_sql = trim($sql);
        if (harbor_is_blank($normalized_sql)) {
            throw new \InvalidArgumentException('Query raw expression cannot be empty.');
        }

        return new self($normalized_sql, array_values($bindings));
    }

    public function sql(): string
    {
        return $this->sql;
    }

    /**
     * @return array<int, mixed>
     */
    public function bindings(): array
    {
        return $this->bindings;
    }

    private static function assert_safe_sql_fragment(string $sql, string $context): void
    {
        foreach ([';', '--', '/*', '*/'] as $unsafe_token) {
            if (str_contains($sql, $unsafe_token)) {
                throw new \InvalidArgumentException(
                    sprintf('%s contains unsafe SQL token "%s".', $context, $unsafe_token)
                );
            }
        }
    }
}
