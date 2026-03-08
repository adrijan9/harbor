<?php

declare(strict_types=1);

namespace Harbor\Password;

require_once __DIR__.'/PasswordAlgorithm.php';

/** Public */
function password_hash(string $password, ?PasswordAlgorithm $algorithm = null, array $options = []): string
{
    $resolved_algorithm = password_resolve_algorithm($algorithm);
    $hashed_password = \password_hash($password, $resolved_algorithm, $options);

    if (! is_string($hashed_password)) {
        throw new \RuntimeException('Failed to hash password.');
    }

    return $hashed_password;
}

function password_verify(string $password, string $hash): bool
{
    return \password_verify($password, $hash);
}

function password_needs_rehash(string $hash, ?PasswordAlgorithm $algorithm = null, array $options = []): bool
{
    return \password_needs_rehash($hash, password_resolve_algorithm($algorithm), $options);
}

function password_info(string $hash): array
{
    return \password_get_info($hash);
}

function password_algorithms(): array
{
    return \password_algos();
}

function bcrypt(string $password, array $options = []): string
{
    return password_hash($password, PasswordAlgorithm::BCRYPT, $options);
}

function argon2i(string $password, array $options = []): string
{
    return password_hash($password, PasswordAlgorithm::ARGON2I, $options);
}

function argon2id(string $password, array $options = []): string
{
    return password_hash($password, PasswordAlgorithm::ARGON2ID, $options);
}

/** Private */
function password_resolve_algorithm(?PasswordAlgorithm $algorithm): int|string
{
    $resolved_algorithm = $algorithm ?? PasswordAlgorithm::DEFAULT;
    $resolved_algorithm->assert_supported();

    return $resolved_algorithm->constant();
}
