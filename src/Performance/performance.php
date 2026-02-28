<?php

declare(strict_types=1);

namespace Harbor\Performance;

require_once __DIR__.'/PerformanceTracker.php';

function performance_begin(?string $marker = null): void
{
    PerformanceTracker::begin($marker);
}

function performance_end(?string $marker = null): array
{
    return PerformanceTracker::end($marker);
}

function performance_end_log(?string $marker = null, ?string $prefix = null): array
{
    return PerformanceTracker::end_log($marker, $prefix);
}
