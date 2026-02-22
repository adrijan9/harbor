<?php

declare(strict_types=1);

namespace Harbor\Tests\Support;

use PHPUnit\Framework\TestCase;

use function Harbor\Support\harbor_is_blank;
use function Harbor\Support\harbor_is_null;

require_once dirname(__DIR__, 2).'/src/Support/value.php';

final class ValueHelpersTest extends TestCase
{
    public function test_harbor_is_blank_handles_common_empty_values(): void
    {
        self::assertTrue(harbor_is_blank(null));
        self::assertTrue(harbor_is_blank(''));
        self::assertTrue(harbor_is_blank([]));
    }

    public function test_harbor_is_blank_keeps_zero_as_non_blank(): void
    {
        self::assertFalse(harbor_is_blank('0'));
        self::assertFalse(harbor_is_blank(0));
    }

    public function test_harbor_is_blank_returns_false_for_non_empty_values(): void
    {
        self::assertFalse(harbor_is_blank('harbor'));
        self::assertFalse(harbor_is_blank([1]));
        self::assertFalse(harbor_is_blank(true));
        self::assertFalse(harbor_is_blank(false));
    }

    public function test_harbor_is_null_only_matches_null(): void
    {
        self::assertTrue(harbor_is_null(null));
        self::assertFalse(harbor_is_null(''));
        self::assertFalse(harbor_is_null([]));
        self::assertFalse(harbor_is_null(0));
        self::assertFalse(harbor_is_null(false));
    }
}
