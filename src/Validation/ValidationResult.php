<?php

declare(strict_types=1);

namespace Harbor\Validation;

final class ValidationResult
{
    /**
     * @param array<string, array<int, string>> $errors
     * @param array<string, mixed> $validated
     */
    private function __construct(
        private readonly bool $ok,
        private readonly array $errors = [],
        private readonly array $validated = [],
    ) {
    }

    /**
     * @param array<string, mixed> $validated
     */
    public static function ok(array $validated = []): self
    {
        return new self(true, [], $validated);
    }

    /**
     * @param array<string, array<int, string>> $errors
     * @param array<string, mixed> $validated
     */
    public static function failed(array $errors, array $validated = []): self
    {
        return new self(false, $errors, $validated);
    }

    public function is_ok(): bool
    {
        return $this->ok;
    }

    public function has_errors(): bool
    {
        return empty($this->errors) === false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @return array<string, mixed>
     */
    public function validated(): array
    {
        return $this->validated;
    }
}
