<?php

declare(strict_types=1);

namespace Harbor\Validation;

require_once __DIR__.'/../Support/value.php';

use function Harbor\Support\harbor_is_blank;
use function Harbor\Support\harbor_is_null;

final class ValidationValidator
{
    /**
     * @param array<int, ValidationRule> $rules
     */
    public static function validate(array $input, array $rules, array $messages = []): ValidationResult
    {
        $errors = [];
        $validated = [];

        foreach ($rules as $index => $rule) {
            if (! $rule instanceof ValidationRule) {
                throw new \InvalidArgumentException(
                    sprintf('Validation rules must be ValidationRule instances. Invalid rule at index %d.', $index)
                );
            }

            [$value_exists, $value] = self::resolve_field_value($input, $rule->field());
            $rule_result = self::evaluate_rule($rule, $value, $value_exists, $messages);

            if ($rule_result->has_errors()) {
                $errors = self::merge_error_bags($errors, $rule_result->errors());

                continue;
            }

            if ($value_exists) {
                $validated[$rule->field()] = $value;
            }
        }

        if (empty($errors)) {
            return ValidationResult::ok($validated);
        }

        return ValidationResult::failed($errors, $validated);
    }

    public static function validate_rule_input(ValidationRule $rule, array $input, array $messages = []): ValidationResult
    {
        [$value_exists, $value] = self::resolve_field_value($input, $rule->field());

        return self::evaluate_rule($rule, $value, $value_exists, $messages);
    }

    public static function validate_rule_value(ValidationRule $rule, mixed $value, array $messages = []): ValidationResult
    {
        return self::evaluate_rule($rule, $value, true, $messages);
    }

    private static function evaluate_rule(
        ValidationRule $rule,
        mixed $value,
        bool $value_exists,
        array $messages = []
    ): ValidationResult {
        $constraints = $rule->constraints();
        $field = $rule->field();
        $is_required = self::constraints_have($constraints, 'required');
        $is_nullable = self::constraints_have($constraints, 'nullable');

        if (! $value_exists) {
            if ($is_required) {
                return self::failed_for_rule($field, 'required', null, $messages);
            }

            return ValidationResult::ok();
        }

        if ($is_nullable && harbor_is_null($value)) {
            return ValidationResult::ok([$field => $value]);
        }

        if (harbor_is_blank($value) && ! $is_required) {
            return ValidationResult::ok([$field => $value]);
        }

        foreach ($constraints as $constraint) {
            $name = $constraint['name'];
            $argument = $constraint['argument'];

            if ('required' === $name) {
                if (harbor_is_blank($value)) {
                    return self::failed_for_rule($field, $name, $argument, $messages);
                }

                continue;
            }

            if ('nullable' === $name) {
                continue;
            }

            if ('string' === $name && ! is_string($value)) {
                return self::failed_for_rule($field, $name, $argument, $messages);
            }

            if ('int' === $name && ! self::value_is_int($value)) {
                return self::failed_for_rule($field, $name, $argument, $messages);
            }

            if ('float' === $name && ! self::value_is_float($value)) {
                return self::failed_for_rule($field, $name, $argument, $messages);
            }

            if ('bool' === $name && ! self::value_is_bool($value)) {
                return self::failed_for_rule($field, $name, $argument, $messages);
            }

            if ('array' === $name && ! is_array($value)) {
                return self::failed_for_rule($field, $name, $argument, $messages);
            }

            if ('email' === $name && ! self::value_is_email($value)) {
                return self::failed_for_rule($field, $name, $argument, $messages);
            }

            if ('min' === $name && ! self::value_passes_min($value, $argument)) {
                return self::failed_for_rule($field, $name, $argument, $messages);
            }

            if ('max' === $name && ! self::value_passes_max($value, $argument)) {
                return self::failed_for_rule($field, $name, $argument, $messages);
            }

            if ('in' === $name && ! self::value_in_allowed_list($value, $argument)) {
                return self::failed_for_rule($field, $name, $argument, $messages);
            }

            if ('regex' === $name && ! self::value_matches_pattern($value, $argument)) {
                return self::failed_for_rule($field, $name, $argument, $messages);
            }
        }

        return ValidationResult::ok([$field => $value]);
    }

    /**
     * @param array<int, array{name: string, argument: mixed}> $constraints
     */
    private static function constraints_have(array $constraints, string $name): bool
    {
        foreach ($constraints as $constraint) {
            if (($constraint['name'] ?? null) === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{0: bool, 1: mixed}
     */
    private static function resolve_field_value(array $input, string $field): array
    {
        $trimmed_field = trim($field);
        if (harbor_is_blank($trimmed_field)) {
            return [false, null];
        }

        if (array_key_exists($trimmed_field, $input)) {
            return [true, $input[$trimmed_field]];
        }

        $segments = explode('.', $trimmed_field);
        $current = $input;

        foreach ($segments as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return [false, null];
            }

            $current = $current[$segment];
        }

        return [true, $current];
    }

    private static function value_is_int(mixed $value): bool
    {
        if (is_int($value)) {
            return true;
        }

        if (! is_string($value)) {
            return false;
        }

        return 1 === preg_match('/^-?\d+$/', trim($value));
    }

    private static function value_is_float(mixed $value): bool
    {
        if (is_int($value) || is_float($value)) {
            return true;
        }

        if (! is_string($value)) {
            return false;
        }

        return is_numeric(trim($value));
    }

    private static function value_is_bool(mixed $value): bool
    {
        if (is_bool($value)) {
            return true;
        }

        if (! is_string($value)) {
            return false;
        }

        return in_array(strtolower(trim($value)), ['1', '0', 'true', 'false', 'yes', 'no', 'on', 'off'], true);
    }

    private static function value_is_email(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $normalized_value = trim($value);
        if (harbor_is_blank($normalized_value)) {
            return false;
        }

        return false !== filter_var($normalized_value, FILTER_VALIDATE_EMAIL);
    }

    private static function value_passes_min(mixed $value, mixed $min): bool
    {
        if (! is_int($min) && ! is_float($min)) {
            throw new \InvalidArgumentException('Validation rule "min" requires a numeric argument.');
        }

        if (is_array($value)) {
            return count($value) >= $min;
        }

        if (is_int($value) || is_float($value)) {
            return $value >= $min;
        }

        if (is_string($value) && is_numeric($value)) {
            return (float) $value >= $min;
        }

        if (is_string($value)) {
            return self::string_length($value) >= $min;
        }

        if (is_numeric($value)) {
            return (float) $value >= $min;
        }

        return false;
    }

    private static function value_passes_max(mixed $value, mixed $max): bool
    {
        if (! is_int($max) && ! is_float($max)) {
            throw new \InvalidArgumentException('Validation rule "max" requires a numeric argument.');
        }

        if (is_array($value)) {
            return count($value) <= $max;
        }

        if (is_int($value) || is_float($value)) {
            return $value <= $max;
        }

        if (is_string($value) && is_numeric($value)) {
            return (float) $value <= $max;
        }

        if (is_string($value)) {
            return self::string_length($value) <= $max;
        }

        if (is_numeric($value)) {
            return (float) $value <= $max;
        }

        return false;
    }

    private static function value_in_allowed_list(mixed $value, mixed $allowed_values): bool
    {
        if (! is_array($allowed_values)) {
            throw new \InvalidArgumentException('Validation rule "in" requires an array argument.');
        }

        return in_array($value, $allowed_values, true);
    }

    private static function value_matches_pattern(mixed $value, mixed $pattern): bool
    {
        if (! is_string($pattern) || harbor_is_blank(trim($pattern))) {
            throw new \InvalidArgumentException('Validation rule "regex" requires a non-empty string pattern.');
        }

        if (! is_string($value)) {
            return false;
        }

        return 1 === preg_match($pattern, $value);
    }

    private static function string_length(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value);
        }

        return strlen($value);
    }

    private static function failed_for_rule(string $field, string $rule, mixed $argument, array $messages): ValidationResult
    {
        return ValidationResult::failed([
            $field => [
                self::resolve_message($field, $rule, $argument, $messages),
            ],
        ]);
    }

    private static function resolve_message(string $field, string $rule, mixed $argument, array $messages): string
    {
        $field_rule_key = $field.'.'.$rule;
        $field_specific_messages = $messages[$field] ?? null;

        if (isset($messages[$field_rule_key]) && is_string($messages[$field_rule_key])) {
            return $messages[$field_rule_key];
        }

        if (is_array($field_specific_messages) && isset($field_specific_messages[$rule]) && is_string($field_specific_messages[$rule])) {
            return $field_specific_messages[$rule];
        }

        if (isset($messages[$rule]) && is_string($messages[$rule])) {
            return $messages[$rule];
        }

        if (is_string($field_specific_messages)) {
            return $field_specific_messages;
        }

        $template = self::default_messages()[$rule] ?? 'The {field} field is invalid.';
        $argument_value = self::format_message_argument($rule, $argument);

        return str_replace(
            ['{field}', '{value}'],
            [$field, $argument_value],
            $template
        );
    }

    /**
     * @return array<string, string>
     */
    private static function default_messages(): array
    {
        return [
            'required' => 'The {field} field is required.',
            'string' => 'The {field} field must be a string.',
            'int' => 'The {field} field must be an integer.',
            'float' => 'The {field} field must be a float.',
            'bool' => 'The {field} field must be a boolean.',
            'array' => 'The {field} field must be an array.',
            'email' => 'The {field} field must be a valid email address.',
            'min' => 'The {field} field must be at least {value}.',
            'max' => 'The {field} field must be at most {value}.',
            'in' => 'The {field} field must be one of: {value}.',
            'regex' => 'The {field} field format is invalid.',
        ];
    }

    private static function format_message_argument(string $rule, mixed $argument): string
    {
        if ('in' === $rule && is_array($argument)) {
            $normalized_values = [];

            foreach ($argument as $value) {
                if (is_scalar($value)) {
                    $normalized_values[] = (string) $value;
                }
            }

            return implode(', ', $normalized_values);
        }

        if (is_scalar($argument)) {
            return (string) $argument;
        }

        return '';
    }

    /**
     * @param array<string, array<int, string>> $left
     * @param array<string, array<int, string>> $right
     *
     * @return array<string, array<int, string>>
     */
    private static function merge_error_bags(array $left, array $right): array
    {
        foreach ($right as $field => $messages) {
            if (! array_key_exists($field, $left)) {
                $left[$field] = [];
            }

            foreach ($messages as $message) {
                $left[$field][] = $message;
            }
        }

        return $left;
    }
}
