<?php

declare(strict_types=1);

namespace Harbor\Command\Flags;

use function Harbor\Support\harbor_is_null_or_string;

function command_flags_internal_value_to_string(mixed $value, ?string $default_value = null): ?string
{
    if (is_null($value)) {
        return $default_value;
    }

    return (string) $value;
}

function command_flags_internal_value_to_int(mixed $value, int $default_value): int
{
    if (harbor_is_null_or_string($value)) {
        return $default_value;
    }

    return (int) $value;
}

function command_flags_internal_value_to_float(mixed $value, float $default_value): float
{
    if (harbor_is_null_or_string($value)) {
        return $default_value;
    }

    return (float) $value;
}

function command_flags_internal_value_to_bool(mixed $value, bool $default_value): bool
{
    if (is_null($value)) {
        return $default_value;
    }

    if (is_bool($value) || is_numeric($value)) {
        return (bool) $value;
    }

    return is_string($value)
        ? match (strtolower(trim($value))) {
            '1','true','yes','on','y' => true,
            '0','false','no','off','n' => false,
            default => $default_value,
        }
        : $default_value;
}
