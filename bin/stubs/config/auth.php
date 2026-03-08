<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Web Auth Configuration
    |--------------------------------------------------------------------------
    |
    | session_key:
    | - Session key where the authenticated web user payload is stored.
    |
    | attempt_resolver:
    | - Optional callback for credential verification.
    | - Signature: fn(array $credentials): ?array
    | - Return authenticated user payload or null.
    |
    */
    'web' => [
        'session_key' => 'auth_web_user',
        'attempt_resolver' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Auth Configuration
    |--------------------------------------------------------------------------
    |
    | secret:
    | - Required for JWT signing and verification (minimum 32 bytes).
    |
    | attempt_resolver:
    | - Optional callback for API credential verification.
    | - Signature: fn(array $credentials): ?array
    |
    | user_resolver:
    | - Optional callback to map token payload into API user payload.
    | - Signature: fn(array $token_payload, ?array $request, string $token): ?array
    |
    */
    'api' => [
        'secret' => 'replace-with-strong-secret-at-least-32-bytes',
        'issuer' => 'harbor',
        'audience' => 'harbor-api',
        'ttl_seconds' => 3600,
        'leeway_seconds' => 0,
        'revoke_store_path' => __DIR__.'/../storage/auth/revoked_tokens.json',
        'attempt_resolver' => null,
        'user_resolver' => null,
    ],
];
