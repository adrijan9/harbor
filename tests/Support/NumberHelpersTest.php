<?php

declare(strict_types=1);

namespace Harbor\Tests\Support;

use PHPUnit\Framework\TestCase;

use function Harbor\Support\number_ufloat;
use function Harbor\Support\number_internal_value_to_ufloat;
use function Harbor\Support\number_internal_value_to_uint;
use function Harbor\Support\number_uint;

require_once dirname(__DIR__, 2).'/src/Support/number.php';

final class NumberHelpersTest extends TestCase
{
    public function test_number_uint_accepts_unsigned_integer_values(): void
    {
        self::assertSame(0, number_uint(0));
        self::assertSame(42, number_uint('42'));
    }

    public function test_number_uint_throws_for_invalid_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('number_uint() expects an unsigned integer.');

        number_uint('-1');
    }

    public function test_number_ufloat_accepts_unsigned_numeric_values(): void
    {
        self::assertSame(0.0, number_ufloat(0));
        self::assertSame(2.75, number_ufloat('2.75'));
    }

    public function test_number_ufloat_throws_for_invalid_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('number_ufloat() expects an unsigned float.');

        number_ufloat('-0.5');
    }

    public function test_number_internal_value_to_uint_accepts_unsigned_integer_values(): void
    {
        self::assertSame(0, number_internal_value_to_uint(0));
        self::assertSame(42, number_internal_value_to_uint('42'));
        self::assertSame(7, number_internal_value_to_uint(' 7 '));
    }

    public function test_number_internal_value_to_uint_rejects_negative_and_decimal_values(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('test uint expects an unsigned integer.');

        number_internal_value_to_uint('-1', 'test uint');
    }

    public function test_number_internal_value_to_ufloat_accepts_unsigned_numeric_values(): void
    {
        self::assertSame(0.0, number_internal_value_to_ufloat(0));
        self::assertSame(2.75, number_internal_value_to_ufloat('2.75'));
        self::assertSame(1000.0, number_internal_value_to_ufloat('1e3'));
    }

    public function test_number_internal_value_to_ufloat_rejects_negative_values(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('test ufloat expects an unsigned float.');

        number_internal_value_to_ufloat('-0.5', 'test ufloat');
    }
}
