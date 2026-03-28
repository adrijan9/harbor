<?php

declare(strict_types=1);

namespace Harbor\Command\Flags;

use function Harbor\Support\harbor_is_blank;

/**
 * @param array<int, string> $argv
 *
 * @return array{present: bool, has_value: bool, value: null|bool|float|int|string}
 */
function command_flags_internal_find_flag_payload(array $argv, string $flag): array
{
    foreach ($argv as $argument) {
        if (! is_string($argument)) {
            continue;
        }

        $normalized_argument = trim($argument);
        if (! command_flags_internal_is_same_flag($normalized_argument, $flag)) {
            continue;
        }

        $inline_value = command_flags_internal_inline_value($normalized_argument, $flag);

        return [
            'present' => true,
            'has_value' => is_string($inline_value),
            'value' => $inline_value,
        ];
    }

    return [
        'present' => false,
        'has_value' => false,
        'value' => null,
    ];
}

function command_flags_internal_inline_value(string $token, string $flag): ?string
{
    if (! str_starts_with($token, $flag.'=')) {
        return null;
    }

    $value = substr($token, strlen($flag) + 1);
    if (false === $value) {
        return null;
    }

    return $value;
}

/**
 * @param array<int, mixed>                 $value
 * @param array<int, bool|float|int|string> $default_value
 *
 * @return array<int, bool|float|int|string>
 */
function command_flags_internal_normalize_array_value(mixed $value, array $default_value = []): array
{
    if (! is_array($value)) {
        return $default_value;
    }

    $normalized_values = [];

    foreach ($value as $segment) {
        if (is_bool($segment) || is_float($segment) || is_int($segment) || is_string($segment)) {
            $normalized_values[] = $segment;
        }
    }

    return $normalized_values;
}

/**
 * @param array<int, bool|float|int|string> $default_value
 *
 * @return array<int, bool|float|int|string>
 */
function command_flags_internal_parse_csv_array_value(
    mixed $value,
    array $default_value = []
): array {
    if (is_null($value)) {
        return $default_value;
    }

    if (is_array($value)) {
        return command_flags_internal_normalize_array_value($value, $default_value);
    }

    $string_value = command_flags_internal_value_to_string($value);
    if (! is_string($string_value) || harbor_is_blank($string_value)) {
        return $default_value;
    }

    $segments = explode(',', $string_value);
    $parsed_values = [];

    foreach ($segments as $segment) {
        $normalized_segment = trim($segment);
        if (harbor_is_blank($normalized_segment)) {
            continue;
        }

        $parsed_values[] = command_flags_internal_parse_csv_segment($normalized_segment);
    }

    if (empty($parsed_values)) {
        return $default_value;
    }

    return $parsed_values;
}

function command_flags_internal_parse_csv_segment(string $segment): bool|float|int|string
{
    if (1 === preg_match('/^-?[0-9]+$/', $segment)) {
        return (int) $segment;
    }

    if (is_numeric($segment)) {
        return (float) $segment;
    }

    $normalized_segment = strtolower(trim($segment));

    if (in_array($normalized_segment, ['true', 'yes', 'on', 'y'], true)) {
        return true;
    }

    if (in_array($normalized_segment, ['false', 'no', 'off', 'n'], true)) {
        return false;
    }

    return $segment;
}
