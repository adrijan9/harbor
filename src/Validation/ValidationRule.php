<?php

declare(strict_types=1);

namespace Harbor\Validation;

require_once __DIR__.'/../Support/value.php';

use function Harbor\Support\harbor_is_blank;

final class ValidationRule
{
    /**
     * @var array<int, array{name: string, argument: mixed}>
     */
    private array $constraints = [];

    public function __construct(
        private readonly string $field,
    ) {
        if (harbor_is_blank(trim($this->field))) {
            throw new \InvalidArgumentException('Validation field cannot be empty.');
        }
    }

    public function field(): string
    {
        return $this->field;
    }

    /**
     * @return array<int, array{name: string, argument: mixed}>
     */
    public function constraints(): array
    {
        return $this->constraints;
    }

    public function required(): self
    {
        return $this->add_constraint('required');
    }

    public function nullable(): self
    {
        return $this->add_constraint('nullable');
    }

    public function string(): self
    {
        return $this->add_constraint('string');
    }

    public function int(): self
    {
        return $this->add_constraint('int');
    }

    public function float(): self
    {
        return $this->add_constraint('float');
    }

    public function bool(): self
    {
        return $this->add_constraint('bool');
    }

    public function array(): self
    {
        return $this->add_constraint('array');
    }

    public function email(): self
    {
        return $this->add_constraint('email');
    }

    public function min(int|float $value): self
    {
        return $this->add_constraint('min', $value);
    }

    public function max(int|float $value): self
    {
        return $this->add_constraint('max', $value);
    }

    public function in(array $allowed_values): self
    {
        if (empty($allowed_values)) {
            throw new \InvalidArgumentException('Validation "in" rule must include at least one allowed value.');
        }

        return $this->add_constraint('in', $allowed_values);
    }

    public function regex(string $pattern): self
    {
        if (harbor_is_blank(trim($pattern))) {
            throw new \InvalidArgumentException('Validation regex pattern cannot be empty.');
        }

        set_error_handler(static fn (): bool => true);

        try {
            if (false === preg_match($pattern, '')) {
                throw new \InvalidArgumentException(
                    sprintf('Validation regex pattern "%s" is invalid.', $pattern)
                );
            }
        } finally {
            restore_error_handler();
        }

        return $this->add_constraint('regex', $pattern);
    }

    public function validate_input(array $input, array $messages = []): ValidationResult
    {
        return ValidationValidator::validate_rule_input($this, $input, $messages);
    }

    public function validate_value(mixed $value, array $messages = []): ValidationResult
    {
        return ValidationValidator::validate_rule_value($this, $value, $messages);
    }

    public function is_valid_input(array $input, array $messages = []): bool
    {
        return $this->validate_input($input, $messages)->is_ok();
    }

    public function is_valid(mixed $value, array $messages = []): bool
    {
        return $this->validate_value($value, $messages)->is_ok();
    }

    private function add_constraint(string $name, mixed $argument = null): self
    {
        $this->constraints[] = [
            'name' => $name,
            'argument' => $argument,
        ];

        return $this;
    }
}
