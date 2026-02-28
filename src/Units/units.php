<?php

declare(strict_types=1);

namespace Harbor\Units;

const UNIT_BYTES_PER_KB = 1024;
const UNIT_BYTES_PER_MB = 1024 * UNIT_BYTES_PER_KB;
const UNIT_BYTES_PER_GB = 1024 * UNIT_BYTES_PER_MB;
const UNIT_BYTES_PER_TB = 1024 * UNIT_BYTES_PER_GB;

function unit_kb_from_bytes(int|float $bytes, int $precision = 3): float
{
    return unit_round(((float) $bytes) / UNIT_BYTES_PER_KB, $precision);
}

function unit_mb_from_bytes(int|float $bytes, int $precision = 3): float
{
    return unit_round(((float) $bytes) / UNIT_BYTES_PER_MB, $precision);
}

function unit_gb_from_bytes(int|float $bytes, int $precision = 3): float
{
    return unit_round(((float) $bytes) / UNIT_BYTES_PER_GB, $precision);
}

function unit_tb_from_bytes(int|float $bytes, int $precision = 3): float
{
    return unit_round(((float) $bytes) / UNIT_BYTES_PER_TB, $precision);
}

function unit_bytes_from_kb(int|float $kilobytes, int $precision = 3): float
{
    return unit_round(((float) $kilobytes) * UNIT_BYTES_PER_KB, $precision);
}

function unit_bytes_from_mb(int|float $megabytes, int $precision = 3): float
{
    return unit_round(((float) $megabytes) * UNIT_BYTES_PER_MB, $precision);
}

function unit_bytes_from_gb(int|float $gigabytes, int $precision = 3): float
{
    return unit_round(((float) $gigabytes) * UNIT_BYTES_PER_GB, $precision);
}

function unit_bytes_from_tb(int|float $terabytes, int $precision = 3): float
{
    return unit_round(((float) $terabytes) * UNIT_BYTES_PER_TB, $precision);
}

function unit_kb_from_mb(int|float $megabytes, int $precision = 3): float
{
    return unit_round(((float) $megabytes) * UNIT_BYTES_PER_KB, $precision);
}

function unit_mb_from_kb(int|float $kilobytes, int $precision = 3): float
{
    return unit_round(((float) $kilobytes) / UNIT_BYTES_PER_KB, $precision);
}

function unit_mb_from_gb(int|float $gigabytes, int $precision = 3): float
{
    return unit_round(((float) $gigabytes) * UNIT_BYTES_PER_KB, $precision);
}

function unit_gb_from_mb(int|float $megabytes, int $precision = 3): float
{
    return unit_round(((float) $megabytes) / UNIT_BYTES_PER_KB, $precision);
}

function unit_gb_from_tb(int|float $terabytes, int $precision = 3): float
{
    return unit_round(((float) $terabytes) * UNIT_BYTES_PER_KB, $precision);
}

function unit_tb_from_gb(int|float $gigabytes, int $precision = 3): float
{
    return unit_round(((float) $gigabytes) / UNIT_BYTES_PER_KB, $precision);
}

function unit_bytes_to_human(int|float $bytes, int $precision = 3): string
{
    $normalized_precision = unit_normalize_precision($precision);
    $absolute_value = abs((float) $bytes);
    $unit_labels = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    $unit_index = 0;

    while ($absolute_value >= UNIT_BYTES_PER_KB && $unit_index < count($unit_labels) - 1) {
        $absolute_value /= UNIT_BYTES_PER_KB;
        ++$unit_index;
    }

    $sign = ((float) $bytes) < 0 ? '-' : '';
    $formatted_value = unit_format_number($absolute_value, $normalized_precision);

    return $sign.$formatted_value.' '.$unit_labels[$unit_index];
}

function unit_duration_ms_to_human(int|float $duration_ms, int $precision = 3): string
{
    $normalized_precision = unit_normalize_precision($precision);
    $absolute_duration_ms = abs((float) $duration_ms);
    $sign = ((float) $duration_ms) < 0 ? '-' : '';

    $value = $absolute_duration_ms;
    $unit = 'ms';

    if ($absolute_duration_ms >= 86_400_000) {
        $value = $absolute_duration_ms / 86_400_000;
        $unit = 'd';
    } elseif ($absolute_duration_ms >= 3_600_000) {
        $value = $absolute_duration_ms / 3_600_000;
        $unit = 'h';
    } elseif ($absolute_duration_ms >= 60_000) {
        $value = $absolute_duration_ms / 60_000;
        $unit = 'min';
    } elseif ($absolute_duration_ms >= 1_000) {
        $value = $absolute_duration_ms / 1_000;
        $unit = 's';
    }

    return $sign.unit_format_number($value, $normalized_precision).' '.$unit;
}

function unit_round(int|float $value, int $precision = 3): float
{
    return round((float) $value, unit_normalize_precision($precision));
}

function unit_format_number(float $value, int $precision): string
{
    $formatted_value = number_format($value, unit_normalize_precision($precision), '.', '');

    if (str_contains($formatted_value, '.')) {
        $formatted_value = rtrim(rtrim($formatted_value, '0'), '.');
    }

    return '' === $formatted_value ? '0' : $formatted_value;
}

function unit_normalize_precision(int $precision): int
{
    if ($precision < 0) {
        throw new \InvalidArgumentException('Precision cannot be negative.');
    }

    return $precision;
}
