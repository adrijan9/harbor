<?php

declare(strict_types=1);

namespace Harbor\Tests\Response;

use Harbor\Response\ResponseStatus;
use PHPUnit\Framework\TestCase;

/**
 * Class ResponseStatusTest.
 */
final class ResponseStatusTest extends TestCase
{
    public function test_response_status_map_uses_numeric_code_keys_with_messages(): void
    {
        $status_map = ResponseStatus::map();

        self::assertCount(63, $status_map);
        self::assertSame('Continue', $status_map[100]);
        self::assertSame('Not Found', $status_map[404]);
        self::assertSame('(Unused)', $status_map[306]);
        self::assertSame('Unauthorized', $status_map[401]);
        self::assertSame('Internal Server Error', $status_map[500]);
        self::assertSame('Network Authentication Required', $status_map[511]);
    }

    public function test_response_status_message_for_resolves_from_int_and_enum(): void
    {
        self::assertSame('Not Found', ResponseStatus::message_for(404));
        self::assertSame('Forbidden', ResponseStatus::message_for(ResponseStatus::FORBIDDEN));
        self::assertSame('Error', ResponseStatus::message_for(999));
    }
}
