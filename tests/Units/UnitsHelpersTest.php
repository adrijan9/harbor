<?php

declare(strict_types=1);

namespace Harbor\Tests\Units;

use Harbor\HelperLoader;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\TestCase;

use function Harbor\Units\unit_bytes_from_gb;
use function Harbor\Units\unit_bytes_from_kb;
use function Harbor\Units\unit_bytes_from_mb;
use function Harbor\Units\unit_bytes_from_tb;
use function Harbor\Units\unit_bytes_to_human;
use function Harbor\Units\unit_duration_ms_to_human;
use function Harbor\Units\unit_gb_from_bytes;
use function Harbor\Units\unit_kb_from_bytes;
use function Harbor\Units\unit_kb_from_mb;
use function Harbor\Units\unit_mb_from_bytes;
use function Harbor\Units\unit_mb_from_kb;
use function Harbor\Units\unit_tb_from_bytes;

final class UnitsHelpersTest extends TestCase
{
    #[BeforeClass]
    public static function load_units_helper(): void
    {
        HelperLoader::load('units');
    }

    public function test_units_convert_between_kb_and_mb(): void
    {
        self::assertSame(1024.0, unit_kb_from_mb(1));
        self::assertSame(1.0, unit_mb_from_kb(1024));
        self::assertSame(1.5, unit_mb_from_kb(1536));
    }

    public function test_units_convert_between_bytes_and_main_units(): void
    {
        self::assertSame(1.0, unit_kb_from_bytes(1024));
        self::assertSame(1.0, unit_mb_from_bytes(1_048_576));
        self::assertSame(1.0, unit_gb_from_bytes(1_073_741_824));
        self::assertSame(1.0, unit_tb_from_bytes(1_099_511_627_776));
        self::assertSame(1024.0, unit_bytes_from_kb(1));
        self::assertSame(1_048_576.0, unit_bytes_from_mb(1));
        self::assertSame(1_073_741_824.0, unit_bytes_from_gb(1));
        self::assertSame(1_099_511_627_776.0, unit_bytes_from_tb(1));
    }

    public function test_units_bytes_to_human_formats_expected_output(): void
    {
        self::assertSame('0 B', unit_bytes_to_human(0));
        self::assertSame('1.5 KB', unit_bytes_to_human(1536, 2));
        self::assertSame('1 MB', unit_bytes_to_human(1_048_576));
        self::assertSame('-2 KB', unit_bytes_to_human(-2048));
    }

    public function test_units_duration_ms_to_human_formats_expected_output(): void
    {
        self::assertSame('500 ms', unit_duration_ms_to_human(500));
        self::assertSame('1.5 s', unit_duration_ms_to_human(1500, 2));
        self::assertSame('2 min', unit_duration_ms_to_human(120_000));
        self::assertSame('2 h', unit_duration_ms_to_human(7_200_000));
        self::assertSame('2 d', unit_duration_ms_to_human(172_800_000));
    }

    public function test_units_throw_for_negative_precision(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Precision cannot be negative.');

        unit_kb_from_bytes(1024, -1);
    }
}
