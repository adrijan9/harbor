<?php

declare(strict_types=1);

namespace Harbor\Log;

enum LogLevel: string
{
    case DEBUG = 'debug';
    case INFO = 'info';
    case NOTICE = 'notice';
    case WARNING = 'warning';
    case ERROR = 'error';
    case CRITICAL = 'critical';
    case ALERT = 'alert';
    case EMERGENCY = 'emergency';

    public static function values(): array
    {
        return array_map(
            static fn (self $log_level): string => $log_level->value,
            self::cases()
        );
    }
}

function log_levels(): array
{
    return LogLevel::values();
}
