<?php

declare(strict_types=1);

namespace Harbor\Date;

require_once __DIR__.'/Carbon.php';

function carbon(mixed $time = null, \DateTimeZone|string|null $timezone = null): Carbon
{
    return Carbon::parse($time ?? 'now', date_resolve_timezone($timezone));
}

function date_now(\DateTimeZone|string|null $timezone = null): Carbon
{
    return Carbon::now(date_resolve_timezone($timezone));
}

function date_resolve_timezone(\DateTimeZone|string|null $timezone = null): ?\DateTimeZone
{
    if ($timezone instanceof \DateTimeZone) {
        return $timezone;
    }

    if (! is_string($timezone)) {
        return null;
    }

    $normalized_timezone = trim($timezone);
    if ('' === $normalized_timezone) {
        return null;
    }

    return new \DateTimeZone($normalized_timezone);
}
