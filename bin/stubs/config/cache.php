<?php

declare(strict_types=1);

use Harbor\Cache\CacheDriver;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Cache Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "array", "file", "apc"
    | - array: in-memory cache for current PHP process
    | - file: serialized cache files under file_path
    | - apc: APCu shared memory cache
    |
    */
    'driver' => CacheDriver::FILE->value,

    /*
    |--------------------------------------------------------------------------
    | File Cache Path
    |--------------------------------------------------------------------------
    |
    | Used when driver is "file".
    | Keep this path writable by PHP.
    |
    */
    'file_path' => __DIR__.'/../cache',
];
