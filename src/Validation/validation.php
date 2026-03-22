<?php

declare(strict_types=1);

namespace Harbor\Validation;

require_once __DIR__.'/ValidationRule.php';

require_once __DIR__.'/ValidationResult.php';

require_once __DIR__.'/ValidationValidator.php';

require_once __DIR__.'/../Session/session.php';

require_once __DIR__.'/../Support/value.php';

use function Harbor\Session\session_flash_forget;
use function Harbor\Session\session_flash_get;
use function Harbor\Session\session_flash_set;
use function Harbor\Support\harbor_is_blank;

/** Public */
function validation_rule(string $field): ValidationRule
{
    return new ValidationRule($field);
}

/**
 * @param array<int, ValidationRule> $rules
 */
function validation_validate(array $input, array $rules, array $messages = []): ValidationResult
{
    return ValidationValidator::validate($input, $rules, $messages);
}

/**
 * @return array<string, array<int, string>>
 */
function validation_errors(ValidationResult $result): array
{
    return $result->errors();
}

function validation_has_errors(ValidationResult $result): bool
{
    return $result->has_errors();
}

function validation_form_flash(ValidationResult $result, string $bag = 'default'): bool
{
    $normalized_errors = validation_internal_form_errors_normalize($result->errors());

    if (empty($normalized_errors)) {
        return validation_form_clear($bag);
    }

    return session_flash_set(
        validation_internal_form_errors_flash_key($bag),
        $normalized_errors
    );
}

/**
 * @return array<string, array<int, string>>
 */
function validation_form_errors(string $bag = 'default'): array
{
    $errors = session_flash_get(
        validation_internal_form_errors_flash_key($bag),
        []
    );

    if (! is_array($errors)) {
        return [];
    }

    return validation_internal_form_errors_normalize($errors);
}

function validation_form_has_errors(string $bag = 'default'): bool
{
    return false === empty(validation_form_errors($bag));
}

/**
 * @return array<int, string>
 */
function validation_form_field_errors(string $field, string $bag = 'default'): array
{
    $normalized_field = trim($field);
    if (harbor_is_blank($normalized_field)) {
        return [];
    }

    $errors = validation_form_errors($bag);
    $field_errors = $errors[$normalized_field] ?? [];

    return is_array($field_errors) ? $field_errors : [];
}

function validation_form_first_error(string $field, string $bag = 'default', ?string $default = null): ?string
{
    $field_errors = validation_form_field_errors($field, $bag);
    if (empty($field_errors)) {
        return $default;
    }

    $first_error = $field_errors[0] ?? null;
    if (! is_string($first_error)) {
        return $default;
    }

    return $first_error;
}

function validation_form_clear(string $bag = 'default'): bool
{
    return session_flash_forget(validation_internal_form_errors_flash_key($bag));
}

/** Private */
function validation_internal_form_bag_name(string $bag): string
{
    $normalized_bag = strtolower(trim($bag));

    if (harbor_is_blank($normalized_bag)) {
        return 'default';
    }

    return $normalized_bag;
}

function validation_internal_form_errors_flash_key(string $bag): string
{
    return '__validation_form_errors_'.rawurlencode(
        validation_internal_form_bag_name($bag)
    );
}

/**
 * @param array<string, mixed> $errors
 *
 * @return array<string, array<int, string>>
 */
function validation_internal_form_errors_normalize(array $errors): array
{
    $normalized_errors = [];

    foreach ($errors as $field => $messages) {
        if (! is_string($field)) {
            continue;
        }

        $normalized_field = trim($field);
        if (harbor_is_blank($normalized_field)) {
            continue;
        }

        $normalized_messages = [];
        $raw_messages = is_array($messages) ? $messages : [$messages];

        foreach ($raw_messages as $message) {
            if (is_string($message)) {
                $normalized_message = trim($message);
            } elseif (is_scalar($message)) {
                $normalized_message = trim((string) $message);
            } elseif (is_object($message) && method_exists($message, '__toString')) {
                $normalized_message = trim((string) $message);
            } else {
                continue;
            }

            if (harbor_is_blank($normalized_message)) {
                continue;
            }

            $normalized_messages[] = $normalized_message;
        }

        if (empty($normalized_messages)) {
            continue;
        }

        $normalized_errors[$normalized_field] = $normalized_messages;
    }

    return $normalized_errors;
}
