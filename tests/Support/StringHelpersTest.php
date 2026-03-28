<?php

declare(strict_types=1);

namespace Harbor\Tests\Support;

use PHPUnit\Framework\TestCase;

use function Harbor\Support\harbor_is_null_or_string;
use function Harbor\Support\harbor_is_only_string;

require_once dirname(__DIR__, 2).'/src/Support/string.php';

/**
 * Class StringHelpersTest.
 */
final class StringHelpersTest extends TestCase
{
    public function test_harbor_is_only_string(): void
    {
        self::assertTrue(harbor_is_only_string('asdasdASdasdasd'));
        self::assertFalse(harbor_is_only_string(1233));
    }

    public function test_harbor_is_null_or_string(): void
    {
        self::assertTrue(harbor_is_null_or_string('asdasdASdasdasd'));
        self::assertTrue(harbor_is_null_or_string(null));
        self::assertFalse(harbor_is_null_or_string(1233));
    }
}
