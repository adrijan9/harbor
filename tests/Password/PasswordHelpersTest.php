<?php

declare(strict_types=1);

namespace Harbor\Tests\Password;

use Harbor\Helper;
use Harbor\Password\PasswordAlgorithm;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

use function Harbor\Password\argon2i;
use function Harbor\Password\argon2id;
use function Harbor\Password\bcrypt;
use function Harbor\Password\password_algorithms;
use function Harbor\Password\password_hash;
use function Harbor\Password\password_info;
use function Harbor\Password\password_needs_rehash;
use function Harbor\Password\password_verify;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
/**
 * Class PasswordHelpersTest.
 */
final class PasswordHelpersTest extends TestCase
{
    public function test_password_hash_and_verify_round_trip(): void
    {
        $hash = password_hash('super-secret-password');

        self::assertNotSame('super-secret-password', $hash);
        self::assertTrue(password_verify('super-secret-password', $hash));
        self::assertFalse(password_verify('wrong-password', $hash));
    }

    public function test_password_needs_rehash_detects_option_changes(): void
    {
        $hash = password_hash('super-secret-password', PasswordAlgorithm::BCRYPT, ['cost' => 10]);

        self::assertFalse(password_needs_rehash($hash, PasswordAlgorithm::BCRYPT, ['cost' => 10]));
        self::assertTrue(password_needs_rehash($hash, PasswordAlgorithm::BCRYPT, ['cost' => 12]));
    }

    public function test_password_info_returns_hash_metadata(): void
    {
        $hash = password_hash('super-secret-password');
        $hash_info = password_info($hash);

        self::assertArrayHasKey('algo', $hash_info);
        self::assertArrayHasKey('algoName', $hash_info);
        self::assertArrayHasKey('options', $hash_info);
    }

    public function test_password_hash_accepts_password_algorithm_enum(): void
    {
        $hash = password_hash('super-secret-password', PasswordAlgorithm::BCRYPT, ['cost' => 10]);

        self::assertTrue(password_verify('super-secret-password', $hash));
        self::assertFalse(password_needs_rehash($hash, PasswordAlgorithm::BCRYPT, ['cost' => 10]));
    }

    public function test_password_algorithm_enum_exposes_php_algorithm_constants(): void
    {
        self::assertSame(PASSWORD_DEFAULT, PasswordAlgorithm::DEFAULT->constant());
        self::assertSame(PASSWORD_BCRYPT, PasswordAlgorithm::BCRYPT->constant());
        self::assertTrue(PasswordAlgorithm::DEFAULT->is_supported());
        self::assertTrue(PasswordAlgorithm::BCRYPT->is_supported());
    }

    public function test_bcrypt_helper_hashes_password(): void
    {
        $hash = bcrypt('super-secret-password', ['cost' => 10]);

        self::assertTrue(password_verify('super-secret-password', $hash));
        self::assertFalse(password_needs_rehash($hash, PasswordAlgorithm::BCRYPT, ['cost' => 10]));
    }

    public function test_argon2i_helper_hashes_password_when_supported(): void
    {
        if (! in_array('argon2i', password_algorithms(), true)) {
            self::markTestSkipped('Argon2i is not supported in this PHP runtime.');
        }

        $hash = argon2i('super-secret-password');

        self::assertTrue(password_verify('super-secret-password', $hash));
    }

    public function test_argon2id_helper_hashes_password_when_supported(): void
    {
        if (! in_array('argon2id', password_algorithms(), true)) {
            self::markTestSkipped('Argon2id is not supported in this PHP runtime.');
        }

        $hash = argon2id('super-secret-password');

        self::assertTrue(password_verify('super-secret-password', $hash));
    }

    public function test_password_hash_uses_default_algorithm_for_null_algorithm(): void
    {
        $hash_from_null = password_hash('super-secret-password', null);

        self::assertTrue(password_verify('super-secret-password', $hash_from_null));
    }

    public function test_password_algorithms_returns_available_algorithm_names(): void
    {
        $algorithms = password_algorithms();

        self::assertNotEmpty($algorithms);
        self::assertTrue(
            in_array('bcrypt', $algorithms, true)
            || in_array('2y', $algorithms, true)
            || in_array('argon2id', $algorithms, true)
            || in_array('argon2i', $algorithms, true)
        );
    }

    #[Before]
    protected function bootstrap_password_helpers(): void
    {
        Helper::load_many('password');
    }
}
