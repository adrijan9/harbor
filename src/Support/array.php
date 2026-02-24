<?php

declare(strict_types=1);

namespace Harbor\Support;

/**
 * Remove one key from an array by exact key or dot notation path.
 */
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
