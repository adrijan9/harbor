<?php

declare(strict_types=1);

namespace Harbor\Support;

/** Public */

/**
 * Checks if value is only string.
 */
function harbor_is_only_string(mixed $value): bool
{
    return is_string($value) && ! is_numeric(trim($value));
}

/**
 * Checks if value is null or string.
 */
function harbor_is_null_or_string(mixed $value): bool
{
    return is_null($value) || (is_string($value) && ! is_numeric(trim($value)));
}
