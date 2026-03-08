<?php

declare(strict_types=1);

return [
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
];
