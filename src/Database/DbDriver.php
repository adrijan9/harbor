<?php

declare(strict_types=1);

namespace Harbor\Database;

/**
 * Enum DbDriver.
 */
enum DbDriver: string
{
    case SQLITE = 'sqlite';
    case MYSQL = 'mysql';
    case MYSQLI = 'mysqli';
}
