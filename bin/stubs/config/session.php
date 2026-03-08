<?php

declare(strict_types=1);

use Harbor\Session\SessionDriver;

return [
    /*
    |--------------------------------------------------------------------------
    | Session Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "cookie", "array", "file"
    |
    */
    'driver' => SessionDriver::COOKIE->value,

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Prefix
    |--------------------------------------------------------------------------
    |
    | Used for every session cookie key.
    | Example key: harbor_user_id
    |
    */
    'prefix' => 'harbor',

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Lifetime (seconds)
    |--------------------------------------------------------------------------
    |
    | 0 means browser-session cookie.
    |
    */
    'ttl_seconds' => 7200,

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Scope
    |--------------------------------------------------------------------------
    |
    */
    'path' => '/',
    'domain' => null,

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Security
    |--------------------------------------------------------------------------
    |
    */
    'secure' => false,
    'http_only' => true,
    'same_site' => 'Lax',

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Signing / Encryption
    |--------------------------------------------------------------------------
    |
    | Enable signing and/or encryption for session cookie payloads.
    | "key" is a shared fallback for both signing_key and encryption_key.
    |
    */
    'signed' => false,
    'encrypted' => false,
    'key' => null,
    'signing_key' => null,
    'encryption_key' => null,

    /*
    |--------------------------------------------------------------------------
    | File Driver Options
    |--------------------------------------------------------------------------
    |
    | "file_path" stores server-side session payload files.
    | "id_cookie" stores the session identifier cookie name.
    |
    */
    'file_path' => __DIR__.'/../storage/session',
    'id_cookie' => 'harbor-session-id',
];
