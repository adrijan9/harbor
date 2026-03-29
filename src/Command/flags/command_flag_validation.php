<?php

declare(strict_types=1);

namespace Harbor\Command\Flags;

use Harbor\Command\CommandInvalidFlagException;
use Harbor\Validation\ValidationRule;

use function Harbor\Support\harbor_is_blank;

function command_flags_internal_assert_required_value(
    string $flag,
    array $flag_payload,
    bool $require_value
): void {
    if (! $require_value) {
        return;
    }

    if (! ($flag_payload['present'] ?? false)) {
        return;
    }

    if ($flag_payload['has_value'] ?? false) {
        return;
    }

    throw new CommandInvalidFlagException(
        sprintf('%s: value is required. Use %s=<value>.', $flag, $flag)
    );
}

/**
 * @throws CommandInvalidFlagException
 */
function command_flags_internal_assert_validated_value(string $flag, mixed $value, ?ValidationRule $validator): void
{
    if (is_null($value)) {
        return;
    }

    if (! $validator instanceof ValidationRule) {
        return;
    }

    $validation_result = $validator->validate_value($value);
    if (! $validation_result->has_errors()) {
        return;
    }

    $validation_messages = command_flags_internal_validation_error_messages($validation_result->errors());
    $validation_message = implode(PHP_EOL, $validation_messages);

    if (harbor_is_blank($validation_message)) {
        $validation_message = sprintf('%s: value is invalid.', $flag);
    }

    throw new CommandInvalidFlagException($validation_message);
}

/**
 * @param array<string, array<int, string>> $errors
 *
 * @return array<int, string>
 */
function command_flags_internal_validation_error_messages(array $errors): array
{
    $messages = [];

    foreach ($errors as $field_errors) {
        if (! is_array($field_errors)) {
            continue;
        }

        foreach ($field_errors as $field_error) {
            if (! is_string($field_error) || harbor_is_blank($field_error)) {
                continue;
            }

            $messages[] = trim($field_error);
        }
    }

    return $messages;
}
