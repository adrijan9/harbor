<?php

declare(strict_types=1);

namespace Harbor\Validation;

require_once __DIR__.'/ValidationRule.php';
require_once __DIR__.'/ValidationResult.php';
require_once __DIR__.'/ValidationValidator.php';

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
