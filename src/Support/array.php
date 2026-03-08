<?php

declare(strict_types=1);

namespace Harbor\Support;

/**
 * Remove one key from an array by exact key or dot notation path.
 */
/** Public */
function array_forget(array &$array, string $key): void
{
    if (array_key_exists($key, $array)) {
        unset($array[$key]);

        return;
    }

    $keys = explode('.', $key);
    $current = &$array;
    $last_index = count($keys) - 1;

    foreach ($keys as $index => $segment_key) {
        if (! is_array($current) || ! array_key_exists($segment_key, $current)) {
            return;
        }

        if ($index === $last_index) {
            unset($current[$segment_key]);

            return;
        }

        $current = &$current[$segment_key];
    }
}

/**
 * Return the first array element value.
 */
function array_first(array $array, mixed $default = null): mixed
{
    $first_key = array_key_first($array);
    if (null === $first_key) {
        return $default;
    }

    return $array[$first_key];
}

/**
 * Return the last array element value.
 */
function array_last(array $array, mixed $default = null): mixed
{
    $last_key = array_key_last($array);
    if (null === $last_key) {
        return $default;
    }

    return $array[$last_key];
}
