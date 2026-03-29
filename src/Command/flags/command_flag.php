<?php

declare(strict_types=1);

namespace Harbor\Command\Flags;

use function Harbor\Support\harbor_is_blank;

function command_flags_internal_is_same_flag(string $token, string $flag): bool
{
    if ($token === $flag) {
        return true;
    }

    return str_starts_with($token, $flag.'=');
}

function command_flags_internal_normalize_flag(string $flag): string
{
    $normalized_flag = trim($flag);
    if (harbor_is_blank($normalized_flag)) {
        return '';
    }

    if (1 !== preg_match('/^--?[a-zA-Z0-9][a-zA-Z0-9._-]*$/', $normalized_flag)) {
        return '';
    }

    return $normalized_flag;
}

function command_flags_internal_register_option(
    array &$command,
    string $flag,
    string $description,
    array|bool|float|int|string|null $default_value,
    string $value_requirement = 'optional'
): void {
    $normalized_value_requirement = command_flags_internal_normalize_value_requirement($value_requirement);

    if (! is_array($command['options'] ?? null)) {
        $command['options'] = [];
    }

    foreach ($command['options'] as $registered_option) {
        if (! is_array($registered_option)) {
            continue;
        }

        if (($registered_option['flag'] ?? null) === $flag) {
            return;
        }
    }

    $command['options'][] = [
        'flag' => $flag,
        'description' => $description,
        'default_value' => $default_value,
        'value_requirement' => $normalized_value_requirement,
    ];
}

function command_flags_internal_normalize_value_requirement(string $value_requirement): string
{
    $normalized_value_requirement = strtolower(trim($value_requirement));

    if (in_array($normalized_value_requirement, ['none', 'optional', 'required'], true)) {
        return $normalized_value_requirement;
    }

    return 'optional';
}

function command_flags_internal_default_label(array|bool|float|int|string|null $default_value): string
{
    if (is_array($default_value)) {
        if (empty($default_value)) {
            return '';
        }

        $segments = [];

        foreach ($default_value as $segment) {
            if (is_bool($segment)) {
                $segments[] = $segment ? 'true' : 'false';

                continue;
            }

            if (is_float($segment) || is_int($segment) || is_string($segment)) {
                $segments[] = (string) $segment;
            }
        }

        return implode(',', $segments);
    }

    if (null === $default_value) {
        return '';
    }

    if (is_bool($default_value)) {
        return $default_value ? 'true' : 'false';
    }

    if (is_scalar($default_value)) {
        return (string) $default_value;
    }

    return '';
}
