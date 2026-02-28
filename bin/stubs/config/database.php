<?php

declare(strict_types=1);

use Harbor\Database\DbDriver;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Database Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "sqlite", "mysql", "mysqli"
    | - sqlite: PDO SQLite driver using sqlite.path
    | - mysql: PDO MySQL driver using mysql.* keys
    | - mysqli: MySQLi driver using mysql.* keys
    |
    */
    'driver' => DbDriver::SQLITE->value,

    /*
    |--------------------------------------------------------------------------
    | SQLite Configuration
    |--------------------------------------------------------------------------
    |
    | Keep this path writable by PHP.
    |
    */
    'sqlite' => [
        'path' => __DIR__.'/../storage/app.sqlite',
    ],

    /*
    |--------------------------------------------------------------------------
    | MySQL / MySQLi Configuration
    |--------------------------------------------------------------------------
    |
    | Used by both PDO MySQL and MySQLi wrappers.
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
];
