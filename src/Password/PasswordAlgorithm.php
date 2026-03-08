<?php

declare(strict_types=1);

namespace Harbor\Password;

/**
 * Enum PasswordAlgorithm.
 */
enum PasswordAlgorithm: string
{
    case DEFAULT = 'default';
    case BCRYPT = 'bcrypt';
    case ARGON2I = 'argon2i';
    case ARGON2ID = 'argon2id';

    public function constant(): int|string
    {
        return match ($this) {
            self::DEFAULT => PASSWORD_DEFAULT,
            self::BCRYPT => PASSWORD_BCRYPT,
            self::ARGON2I => defined('PASSWORD_ARGON2I') ? PASSWORD_ARGON2I : self::ARGON2I->value,
            self::ARGON2ID => defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : self::ARGON2ID->value,
        };
    }

    public function is_supported(): bool
    {
        return match ($this) {
            self::DEFAULT, self::BCRYPT => true,
            self::ARGON2I, self::ARGON2ID => in_array($this->value, \password_algos(), true),
        };
    }

    public function assert_supported(): void
    {
        if ($this->is_supported()) {
            return;
        }

        throw new \RuntimeException(sprintf('Password algorithm "%s" is not supported in this PHP runtime.', $this->value));
    }
}
