<?php

declare(strict_types=1);

namespace Harbor\Tests\Support;

use PHPUnit\Framework\TestCase;

use function Harbor\Support\array_first;
use function Harbor\Support\array_forget;
use function Harbor\Support\array_last;

require_once dirname(__DIR__, 2).'/src/Support/array.php';

/**
 * Class ArrayHelpersTest.
 */
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

    public function test_array_first_returns_first_value_with_any_keys(): void
    {
        $payload = [
            'user_44' => ['name' => 'Ada'],
            'user_45' => ['name' => 'Linus'],
        ];

        self::assertSame(['name' => 'Ada'], array_first($payload));
    }

    public function test_array_last_returns_last_value_with_any_keys(): void
    {
        $payload = [
            'user_44' => ['name' => 'Ada'],
            'user_45' => ['name' => 'Linus'],
        ];

        self::assertSame(['name' => 'Linus'], array_last($payload));
    }

    public function test_array_first_and_last_return_default_for_empty_arrays(): void
    {
        self::assertSame('fallback', array_first([], 'fallback'));
        self::assertSame('fallback', array_last([], 'fallback'));
    }
}
