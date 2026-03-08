<?php

declare(strict_types=1);

namespace Harbor\Middleware;

require_once __DIR__.'/../Pipeline/pipeline.php';

require_once __DIR__.'/../Request/request.php';

use function Harbor\Pipeline\pipeline_clog;
use function Harbor\Pipeline\pipeline_new;
use function Harbor\Pipeline\pipeline_send;
use function Harbor\Pipeline\pipeline_through;
use function Harbor\Request\request;

/** Public */
function middleware(callable ...$actions): void
{
    $pipeline = pipeline_new();

    pipeline_send($pipeline, request());
    pipeline_through($pipeline, ...$actions);
    pipeline_clog($pipeline);
}
