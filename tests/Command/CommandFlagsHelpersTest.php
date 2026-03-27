<?php

declare(strict_types=1);

namespace Harbor\Tests\Command;

use Harbor\Command\CommandValueRequiredException;
use Harbor\Exceptions\EmptyStringException;
use Harbor\Helper;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\TestCase;

use function Harbor\Command\command_flag;
use function Harbor\Command\command_flags_init;

final class CommandFlagsHelpersTest extends TestCase
{
    #[BeforeClass]
    public static function load_command_helpers(): void
    {
        Helper::Command->load();
    }

    public function test_command_flag_returns_default_value_when_flag_is_missing(): void
    {
        $command = command_flags_init('harbor-flag', 1, ['harbor-flag']);

        $player = command_flag($command, '--player', 'Player name', default_value: 'Bob');

        self::assertSame('Bob', $player);
        self::assertCount(1, $command['options']);
    }

    public function test_command_flag_returns_true_for_present_boolean_flag(): void
    {
        $command = command_flags_init('harbor-flag', 2, ['harbor-flag', '--force']);

        $force_mode = command_flag($command, '--force', 'Enable force mode', default_value: false);

        self::assertTrue($force_mode);
    }

    public function test_command_flag_parses_value_from_equals_token(): void
    {
        $command = command_flags_init('harbor-flag', 2, ['harbor-flag', '--name=Harbor']);

        $name = command_flag($command, '--name', 'Name value');

        self::assertSame('Harbor', $name);
    }

    public function test_command_flag_parses_value_from_next_token(): void
    {
        $command = command_flags_init('harbor-flag', 3, ['harbor-flag', '-p', 'Alexander']);

        $player = command_flag($command, '-p', 'Player value');

        self::assertSame('Alexander', $player);
    }

    public function test_command_flag_does_not_match_partial_flag_tokens(): void
    {
        $command = command_flags_init('harbor-flag', 2, ['harbor-flag', '--help']);

        $short_help = command_flag($command, '-h', 'Display short help', default_value: false);
        $long_help = command_flag($command, '--help', 'Display long help', default_value: false);

        self::assertFalse($short_help);
        self::assertTrue($long_help);
    }

    public function test_command_flag_throws_when_required_value_is_missing(): void
    {
        $command = command_flags_init('harbor-flag', 2, ['harbor-flag', '--player']);

        $this->expectException(CommandValueRequiredException::class);
        $this->expectExceptionMessage('--player: value is required.');

        command_flag($command, '--player', 'Player name', required: true);
    }

    public function test_command_flag_throws_when_custom_validator_fails(): void
    {
        $command = command_flags_init('harbor-flag', 2, ['harbor-flag', '--player=Bob']);

        $this->expectException(CommandValueRequiredException::class);
        $this->expectExceptionMessage('--player: value is required.');

        command_flag(
            $command,
            '--player',
            'Player name',
            required: static fn (mixed $value): bool => is_string($value) && strlen($value) > 5
        );
    }

    public function test_command_flag_throws_when_flag_value_is_empty(): void
    {
        $command = command_flags_init('harbor-flag', 2, ['harbor-flag', '--player=   ']);

        $this->expectException(EmptyStringException::class);
        $this->expectExceptionMessage('Flag cannot be empty.');

        command_flag($command, '--player', 'Player name');
    }
}
