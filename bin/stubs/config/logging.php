<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This channel is used when no explicit channel override is passed to
    | log_write()/log_info()/log_error() and similar helper functions.
    |
    */
    'default' => 'stack',

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Supported drivers:
    | - single: write to one file path.
    | - daily: write to date-suffixed files and retain "days" files.
    | - stack: fan-out to multiple channels.
    |
    */
    'channels' => [
        'single' => [
            'driver' => 'single',
            'path' => __DIR__.'/../logs/app.log',
            'channel' => 'app',
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => __DIR__.'/../logs/app.log',
            'days' => 14,
            'channel' => 'app',
        ],

        'stack' => [
            'driver' => 'stack',
            'channels' => ['single', 'daily'],
        ],
    ],
];
