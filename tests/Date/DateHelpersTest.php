<?php

declare(strict_types=1);

namespace Harbor\Tests\Date;

use Harbor\Date\Carbon;
use Harbor\Helper;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\TestCase;

use function Harbor\Date\carbon;
use function Harbor\Date\date_now;

/**
 * Class DateHelpersTest.
 */
final class DateHelpersTest extends TestCase
{
    #[BeforeClass]
    public static function load_date_helpers(): void
    {
        if (! class_exists(\Carbon\Carbon::class)) {
            self::markTestSkipped('nesbot/carbon is not installed in this environment.');
        }

        Helper::load_many('carbon');
    }

    public function test_carbon_helper_returns_harbor_carbon_instance(): void
    {
        $date = carbon('2026-03-08 10:15:30', 'UTC');

        self::assertInstanceOf(Carbon::class, $date);
        self::assertSame('2026-03-08 10:15:30', $date->format('Y-m-d H:i:s'));
        self::assertSame('UTC', $date->getTimezone()->getName());
    }

    public function test_date_now_helper_returns_current_carbon_instance(): void
    {
        $date = date_now('UTC');

        self::assertInstanceOf(Carbon::class, $date);
        self::assertSame('UTC', $date->getTimezone()->getName());

        $now_timestamp = time();
        $difference = abs($date->getTimestamp() - $now_timestamp);
        self::assertLessThanOrEqual(2, $difference);
    }

    public function test_carbon_instances_are_chainable_for_common_date_operations(): void
    {
        $result = carbon('2026-03-08 10:00:00', 'UTC')
            ->addDays(2)
            ->subDay()
            ->startOfDay()
            ->toDateTimeString()
        ;

        self::assertSame('2026-03-09 00:00:00', $result);
    }
}
