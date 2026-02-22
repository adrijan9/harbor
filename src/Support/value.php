<?php

declare(strict_types=1);

namespace Harbor\Support;

/**
 * Treat null, empty string, and empty array as blank while preserving "0".
 */
function harbor_is_blank(mixed $value): bool
{
    if (is_string($value)) {
        return empty($value) && '0' !== $value;
    }

    if (is_array($value)) {
        return empty($value);
    }

    return harbor_is_null($value);
}

/**
 * Null check helper using the project's empty()/isset() style.
 */
function harbor_is_null(mixed $value): bool
{
    return empty($value) && ! isset($value);
}
