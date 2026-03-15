<?php

declare(strict_types=1);

namespace Harbor\CommandSystem;

final class CommandException extends \RuntimeException
{
    public function __construct(string $message, private readonly int $exit_code = 1)
    {
        parent::__construct($message);
    }

    public function exit_code(): int
    {
        return $this->exit_code;
    }
}
