<?php

declare(strict_types=1);

namespace Harbor\Cache;

/**
 * Enum CacheDriver.
 */
enum CacheDriver: string
{
    case ARRAY = 'array';
    case FILE = 'file';
    case APC = 'apc';
}
