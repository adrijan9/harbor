<?php

declare(strict_types=1);

namespace Harbor\Support;

/** Public */
function number_uint(mixed $value): int
{
    return number_internal_value_to_uint($value, 'number_uint()');
}

function number_ufloat(mixed $value): float
{
    return number_internal_value_to_ufloat($value, 'number_ufloat()');
}

/** Private */
function number_internal_value_to_uint(mixed $value, string $context = 'value'): int
{
    if (is_int($value)) {
        if ($value < 0) {
            throw number_internal_invalid_unsigned_exception($context, 'integer');
        }

        return $value;
    }

    if (is_string($value)) {
        $normalized_value = trim($value);

        if (1 === preg_match('/^\d+$/', $normalized_value)) {
            return (int) $normalized_value;
        }
    }

    throw number_internal_invalid_unsigned_exception($context, 'integer');
}

function number_internal_value_to_ufloat(mixed $value, string $context = 'value'): float
{
    if (is_int($value)) {
        if ($value < 0) {
            throw number_internal_invalid_unsigned_exception($context, 'float');
        }

        return (float) $value;
    }

    if (is_float($value)) {
        if (! is_finite($value) || $value < 0) {
            throw number_internal_invalid_unsigned_exception($context, 'float');
        }

        return $value;
    }

    if (is_string($value)) {
        $normalized_value = trim($value);

        if ('' !== $normalized_value && is_numeric($normalized_value)) {
            $parsed_value = (float) $normalized_value;

            if (is_finite($parsed_value) && $parsed_value >= 0) {
                return $parsed_value;
            }
        }
    }

    throw number_internal_invalid_unsigned_exception($context, 'float');
}

function number_internal_invalid_unsigned_exception(string $context, string $type): \InvalidArgumentException
{
    $normalized_context = trim($context);

    if ('' === $normalized_context) {
        $normalized_context = 'value';
    }

    return new \InvalidArgumentException(
        sprintf('%s expects an unsigned %s.', $normalized_context, $type)
    );
}
