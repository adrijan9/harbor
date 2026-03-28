<?php

declare(strict_types=1);

namespace Harbor\Tests\Command;

use Harbor\Command\CommandInvalidFlagException;
use Harbor\Helper;
use Harbor\Validation\ValidationRule;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\TestCase;

use function Harbor\Command\Flags\command_flag;
use function Harbor\Command\Flags\command_flag_array;
use function Harbor\Command\Flags\command_flag_bool;
use function Harbor\Command\Flags\command_flag_float;
use function Harbor\Command\Flags\command_flag_int;
use function Harbor\Command\Flags\command_flag_string;
use function Harbor\Command\Flags\command_flags_init;

final class CommandFlagsHelpersTest extends TestCase
{
    #[BeforeClass]
    public static function load_command_helpers(): void
    {
        Helper::Command->load();
    }

    public function test_command_flag_returns_null_when_flag_is_missing(): void
    {
        $command = command_flags_init('harbor-flag', 1, ['harbor-flag']);

        $player = command_flag($command, '--player', 'Player name', default_value: 'Bob');

        self::assertNull($player);
        self::assertCount(1, $command['options']);
    }

    public function test_command_flag_returns_default_for_present_boolean_flag_without_value(): void
    {
        $command = command_flags_init('harbor-flag', 2, ['harbor-flag', '--force']);

        $force_mode = command_flag($command, '--force', 'Enable force mode', default_value: false);

        self::assertFalse($force_mode);
    }

    public function test_command_flag_returns_default_when_flag_is_present_without_value(): void
    {
        $command = command_flags_init('harbor-flag', 2, ['harbor-flag', '--player']);

        $player = command_flag($command, '--player', 'Player name', default_value: 'Bob');

        self::assertSame('Bob', $player);
    }

    public function test_command_flag_parses_value_from_equals_token(): void
    {
        $command = command_flags_init('harbor-flag', 2, ['harbor-flag', '--name=Harbor']);

        $name = command_flag($command, '--name', 'Name value');

        self::assertSame('Harbor', $name);
    }

    public function test_command_flag_does_not_parse_value_from_next_token(): void
    {
        $command = command_flags_init('harbor-flag', 3, ['harbor-flag', '-p', 'Alexander']);

        $player = command_flag($command, '-p', 'Player value');

        self::assertNull($player);
    }

    public function test_command_flag_does_not_match_partial_flag_tokens(): void
    {
        $command = command_flags_init('harbor-flag', 2, ['harbor-flag', '--help']);

        $short_help = command_flag($command, '-h', 'Display short help', default_value: false);
        $long_help = command_flag($command, '--help', 'Display long help', default_value: false);

        self::assertNull($short_help);
        self::assertFalse($long_help);
    }

    public function test_command_flag_returns_null_when_validator_requires_value_and_flag_has_no_value(): void
    {
        $command = command_flags_init('harbor-flag', 2, ['harbor-flag', '--player']);

        $player = command_flag(
            $command,
            '--player',
            'Player name',
            validator: new ValidationRule('player')->required()->string()
        );

        self::assertNull($player);
    }

    public function test_command_flag_throws_when_validator_rules_fail(): void
    {
        $command = command_flags_init('harbor-flag', 2, ['harbor-flag', '--player=Bob']);

        $this->expectException(CommandInvalidFlagException::class);
        $this->expectExceptionMessage('The player field must be at least 6.');

        command_flag(
            $command,
            '--player',
            'Player name',
            validator: new ValidationRule('player')->string()->min(6)
        );
    }

    public function test_command_flag_string_returns_typed_string(): void
    {
        $command = command_flags_init('harbor-flag', 2, ['harbor-flag', '--name=Harbor']);

        $name = command_flag_string($command, '--name', 'Name value', default_value: 'guest');

        self::assertSame('Harbor', $name);
    }

    public function test_command_flag_int_returns_typed_int(): void
    {
        $command = command_flags_init('harbor-flag', 2, ['harbor-flag', '--limit=10']);

        $limit = command_flag_int($command, '--limit', 'Limit value', default_value: 3);

        self::assertSame(10, $limit);
    }

    public function test_command_flag_int_falls_back_to_default_for_invalid_value_when_not_required(): void
    {
        $command = command_flags_init('harbor-flag', 2, ['harbor-flag', '--limit=invalid']);

        $limit = command_flag_int($command, '--limit', 'Limit value', default_value: 3);

        self::assertSame(3, $limit);
    }

    public function test_command_flag_int_throws_when_validator_rejects_invalid_input(): void
    {
        $command = command_flags_init('harbor-flag', 2, ['harbor-flag', '--limit=invalid']);

        $this->expectException(CommandInvalidFlagException::class);
        $this->expectExceptionMessage('The limit field must be an integer.');

        command_flag_int(
            $command,
            '--limit',
            'Limit value',
            validator: new ValidationRule('limit')->required()->int(),
            default_value: 3
        );
    }

    public function test_command_flag_float_returns_typed_float(): void
    {
        $command = command_flags_init('harbor-flag', 2, ['harbor-flag', '--ratio=2.75']);

        $ratio = command_flag_float($command, '--ratio', 'Ratio value', default_value: 1.0);

        self::assertSame(2.75, $ratio);
    }

    public function test_command_flag_bool_returns_typed_bool(): void
    {
        $command = command_flags_init('harbor-flag', 2, ['harbor-flag', '--force=false']);

        $force_mode = command_flag_bool($command, '--force', 'Force value', default_value: true);

        self::assertFalse($force_mode);
    }

    public function test_command_flag_array_parses_csv_values(): void
    {
        $command = command_flags_init('harbor-flag', 2, ['harbor-flag', '--players=1,2,3,4,5']);

        $players = command_flag_array($command, '--players', 'Player values', default_value: [9]);

        self::assertSame([1, 2, 3, 4, 5], $players);
    }

    public function test_command_flag_array_supports_array_default_value(): void
    {
        $command = command_flags_init('harbor-flag', 1, ['harbor-flag']);

        $players = command_flag_array($command, '-p', 'My command', default_value: [1, 2, 3, 4]);

        self::assertSame([1, 2, 3, 4], $players);
    }

    public function test_command_flag_array_throws_when_validator_rules_fail(): void
    {
        $command = command_flags_init('harbor-flag', 2, ['harbor-flag', '--players=1']);

        $this->expectException(CommandInvalidFlagException::class);
        $this->expectExceptionMessage('The players field must be at least 2.');

        command_flag_array(
            $command,
            '--players',
            'Player values',
            validator: new ValidationRule('players')->required()->array()->min(2),
            default_value: [9]
        );
    }

    public function test_command_flag_returns_empty_string_when_flag_value_is_empty(): void
    {
        $command = command_flags_init('harbor-flag', 2, ['harbor-flag', '--player=   ']);

        $player = command_flag($command, '--player', 'Player name');

        self::assertSame('', $player);
    }
}
