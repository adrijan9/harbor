<?php

declare(strict_types=1);

use Harbor\Database\DbDriver;

return [
    /*
    |--------------------------------------------------------------------------
    | Migration + Seeder Tracker Connection
    |--------------------------------------------------------------------------
    |
    | This connection stores executed migrations/seeders in the tracking
    | tables configured below. Your migration/seeder files can still use
    | any manual connection inside up()/down().
    |
    */
    'driver' => DbDriver::SQLITE->value,

    /*
    |--------------------------------------------------------------------------
    | SQLite Tracker Connection
    |--------------------------------------------------------------------------
    |
    */
    'sqlite' => [
        'path' => __DIR__.'/../database/migration.sqlite',
    ],

    /*
    |--------------------------------------------------------------------------
    | MySQL / MySQLi Tracker Connection
    |--------------------------------------------------------------------------
    |
    */
    'mysql' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'user' => 'root',
        'password' => '',
        'database' => 'app_db',
        'charset' => 'utf8mb4',
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Files + Tracking Table
    |--------------------------------------------------------------------------
    |
    */
    'migrations' => [
        'directory' => __DIR__.'/../database/migrations',
        'table' => 'migrations',
    ],

    /*
    |--------------------------------------------------------------------------
    | Seeder Files + Tracking Table
    |--------------------------------------------------------------------------
    |
    */
    'seeders' => [
        'directory' => __DIR__.'/../database/seeders',
        'table' => 'seeders',
    ],
];
