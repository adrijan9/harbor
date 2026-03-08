<?php

declare(strict_types=1);

namespace Harbor\Session;

/**
 * Enum SessionDriver.
 */
enum SessionDriver: string
{
    case COOKIE = 'cookie';
    case ARRAY = 'array';
    case FILE = 'file';
}
