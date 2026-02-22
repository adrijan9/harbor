<?php

declare(strict_types=1);

namespace Harbor\Router;

/**
 * Segment functions.
 */
function route_segment(int $index, mixed $default = null): mixed
{
    $segments = route_segments();

    return $segments[$index] ?? $default;
}

function route_segment_int(int $index, int $default = 0): int
{
    $value = route_segment($index);

    if (is_int($value)) {
        return $value;
    }

    if (is_numeric($value)) {
        return (int) $value;
    }

    return $default;
}

function route_segment_float(int $index, float $default = 0.0): float
{
    $value = route_segment($index);

    if (is_float($value) || is_int($value)) {
        return (float) $value;
    }

    if (is_string($value) && is_numeric($value)) {
        return (float) $value;
    }

    return $default;
}

function route_segment_str(int $index, string $default = ''): string
{
    $value = route_segment($index);

    if (is_string($value)) {
        return $value;
    }

    if (is_scalar($value)) {
        return (string) $value;
    }

    if (is_object($value) && method_exists($value, '__toString')) {
        return (string) $value;
    }

    return $default;
}

function route_segment_bool(int $index, bool $default = false): bool
{
    $value = route_segment($index);

    if (is_bool($value)) {
        return $value;
    }

    if (is_int($value) || is_float($value)) {
        return 0.0 !== (float) $value;
    }

    if (is_string($value)) {
        $parsed = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        if (null !== $parsed) {
            return $parsed;
        }
    }

    return $default;
}

function route_segment_arr(int $index, array $default = []): array
{
    $value = route_segment($index);

    if (is_array($value)) {
        return $value;
    }

    if ($value instanceof \Traversable) {
        return iterator_to_array($value);
    }

    if (is_string($value)) {
        $decoded = route_segment_decode_json($value, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        $decoded_value = rawurldecode($value);
        if (str_contains($decoded_value, ',')) {
            return array_values(array_filter(
                array_map('trim', explode(',', $decoded_value)),
                static fn (string $item): bool => '' !== $item
            ));
        }
    }

    return $default;
}

function route_segment_obj(int $index, ?object $default = null): ?object
{
    $value = route_segment($index);

    if (is_object($value)) {
        return $value;
    }

    if (is_array($value)) {
        return (object) $value;
    }

    if (is_string($value)) {
        $decoded = route_segment_decode_json($value, false);

        if (is_object($decoded)) {
            return $decoded;
        }

        if (is_array($decoded)) {
            return (object) $decoded;
        }
    }

    return $default;
}

function route_segment_json(int $index, mixed $default = null): mixed
{
    $value = route_segment($index);

    if (! is_string($value)) {
        return $default;
    }

    $decoded = route_segment_decode_json($value, true);

    return null === $decoded ? $default : $decoded;
}

function route_segments(): array
{
    global $route;

    $segments = $route['segments'] ?? [];

    return is_array($segments) ? $segments : [];
}

function route_segments_count(): int
{
    return count(route_segments());
}

function route_segment_exists(int $index): bool
{
    $segments = route_segments();

    return array_key_exists($index, $segments);
}

function route_segment_decode_json(string $value, bool $assoc): mixed
{
    try {
        return json_decode(rawurldecode($value), $assoc, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException) {
        return null;
    }
}
