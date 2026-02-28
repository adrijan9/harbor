<?php

declare(strict_types=1);

namespace Harbor\Database;

/**
 * Interface DatabaseConnectionDtoInterface.
 */
interface DatabaseConnectionDtoInterface
{
    public static function from_config(array $config = []): static;
}
