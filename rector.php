<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/bin',
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withoutParallel()
    ->withPhpVersion(PhpVersion::PHP_85)
    ->withCodeQualityLevel(13)
    ->withDeadCodeLevel(11)
    ->withSkip([
        __DIR__.'/bin/stubs/*',
        __DIR__.'/tests/Fixtures/*',
        __DIR__.'/public/routes.php',
        __DIR__.'/documentation/public/routes.php',
    ])
;
