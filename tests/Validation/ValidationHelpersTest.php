<?php

declare(strict_types=1);

namespace Harbor\Tests\Validation;

use Harbor\HelperLoader;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\TestCase;

use function Harbor\Validation\validation_errors;
use function Harbor\Validation\validation_has_errors;
use function Harbor\Validation\validation_rule;
use function Harbor\Validation\validation_validate;

final class ValidationHelpersTest extends TestCase
{
    #[BeforeClass]
    public static function load_validation_helpers(): void
    {
        HelperLoader::load('validation');
    }

    public function test_validation_validate_accepts_fluent_rules_and_returns_ok_result(): void
    {
        $input = [
            'email' => 'ada@example.com',
            'age' => '32',
            'profile' => [
                'name' => 'Ada',
            ],
            'tags' => ['php', 'harbor'],
        ];

        $result = validation_validate($input, [
            validation_rule('email')->required()->email(),
            validation_rule('age')->required()->int()->min(18)->max(65),
            validation_rule('profile.name')->required()->string()->min(2)->max(50),
            validation_rule('tags')->array()->min(1)->max(5),
            validation_rule('role')->in(['admin', 'editor']),
        ]);

        self::assertTrue($result->is_ok());
        self::assertFalse(validation_has_errors($result));
        self::assertSame([], validation_errors($result));
    }

    public function test_validation_validate_returns_error_map_for_invalid_input(): void
    {
        $result = validation_validate([
            'email' => 'not-an-email',
            'age' => 'oops',
        ], [
            validation_rule('email')->required()->email(),
            validation_rule('age')->required()->int()->min(18),
        ]);

        self::assertFalse($result->is_ok());
        self::assertTrue(validation_has_errors($result));

        $errors = validation_errors($result);

        self::assertArrayHasKey('email', $errors);
        self::assertArrayHasKey('age', $errors);
        self::assertStringContainsString('email', $errors['email'][0]);
        self::assertStringContainsString('age', $errors['age'][0]);
    }

    public function test_validation_validate_supports_custom_messages(): void
    {
        $result = validation_validate([
            'name' => '',
        ], [
            validation_rule('name')->required()->string()->min(2),
        ], [
            'name.required' => 'Name is mandatory.',
        ]);

        self::assertFalse($result->is_ok());
        self::assertSame('Name is mandatory.', validation_errors($result)['name'][0]);
    }

    public function test_validation_rule_supports_direct_value_validation(): void
    {
        $rule = validation_rule('amount')->required()->float()->min(10)->max(20);

        self::assertTrue($rule->is_valid(15.5));
        self::assertFalse($rule->is_valid(7));
    }

    public function test_validation_rule_supports_direct_input_validation(): void
    {
        $rule = validation_rule('amount')->required()->float()->min(10)->max(20);

        self::assertTrue($rule->is_valid_input(['amount' => '12.25']));
        self::assertFalse($rule->is_valid_input(['amount' => '100']));
    }

    public function test_validation_nullable_allows_null_values(): void
    {
        $result = validation_validate([
            'nickname' => null,
        ], [
            validation_rule('nickname')->nullable()->string()->min(2),
        ]);

        self::assertTrue($result->is_ok());
    }

    public function test_validation_regex_and_in_rules_work_together(): void
    {
        $result = validation_validate([
            'slug' => 'harbor-123',
            'type' => 'article',
        ], [
            validation_rule('slug')->required()->regex('/^[a-z0-9-]+$/'),
            validation_rule('type')->required()->in(['article', 'video']),
        ]);

        self::assertTrue($result->is_ok());
    }

    public function test_validation_validate_throws_for_invalid_rule_item(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation rules must be ValidationRule instances. Invalid rule at index 0.');

        validation_validate([], [['required']]);
    }

    public function test_validation_rule_regex_throws_for_invalid_pattern(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation regex pattern "[" is invalid.');

        validation_rule('slug')->regex('[');
    }
}
