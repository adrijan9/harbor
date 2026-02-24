<?php

declare(strict_types=1);

namespace Harbor\Tests\Support;

use PHPUnit\Framework\TestCase;

use function Harbor\Support\array_forget;

require_once dirname(__DIR__, 2).'/src/Support/array.php';

final class ArrayHelpersTest extends TestCase
{
    public function test_array_forget_removes_top_level_key(): void
    {
        $payload = ['first' => 'Ada', 'second' => 'Lovelace'];

        array_forget($payload, 'second');

        self::assertSame(['first' => 'Ada'], $payload);
    }

    public function test_array_forget_removes_nested_key_with_dot_notation(): void
    {
        $payload = [
            'filters' => [
                'owner' => ['id' => '44', 'name' => 'Ada'],
            ],
        ];

        array_forget($payload, 'filters.owner.id');

        self::assertSame(
            ['filters' => ['owner' => ['name' => 'Ada']]],
            $payload
        );
    }

    public function test_array_forget_prefers_exact_key_before_dot_path(): void
    {
        $payload = [
            'filters.owner.id' => 'top-level',
            'filters' => ['owner' => ['id' => 'nested']],
        ];

        array_forget($payload, 'filters.owner.id');

        self::assertSame(
            ['filters' => ['owner' => ['id' => 'nested']]],
            $payload
        );
    }

    public function test_array_forget_ignores_missing_path(): void
    {
        $payload = ['first' => 'Ada'];

        array_forget($payload, 'missing.key');

        self::assertSame(['first' => 'Ada'], $payload);
    }
}
