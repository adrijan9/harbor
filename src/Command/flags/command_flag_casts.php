<?php

declare(strict_types=1);

namespace Harbor\Command\Flags;

require_once __DIR__.'/../../Support/number.php';

use Harbor\Command\CommandInvalidFlagException;

use function Harbor\Support\harbor_is_null_or_string;
use function Harbor\Support\number_internal_value_to_ufloat;
use function Harbor\Support\number_internal_value_to_uint;

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

/**
 * @throws CommandInvalidFlagException
 */
function command_flags_internal_value_to_uint(mixed $value, int $default_value, string $flag): int
{
    $resolved_default_value = number_internal_value_to_uint($default_value, sprintf('%s default', $flag));

    if (is_null($value)) {
        return $resolved_default_value;
    }

    try {
        return number_internal_value_to_uint($value, $flag);
    } catch (\InvalidArgumentException $exception) {
        throw new CommandInvalidFlagException($exception->getMessage(), 0, $exception);
    }
}

/**
 * @throws CommandInvalidFlagException
 */
function command_flags_internal_value_to_ufloat(mixed $value, float $default_value, string $flag): float
{
    $resolved_default_value = number_internal_value_to_ufloat($default_value, sprintf('%s default', $flag));

    if (is_null($value)) {
        return $resolved_default_value;
    }

    try {
        return number_internal_value_to_ufloat($value, $flag);
    } catch (\InvalidArgumentException $exception) {
        throw new CommandInvalidFlagException($exception->getMessage(), 0, $exception);
    }
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
