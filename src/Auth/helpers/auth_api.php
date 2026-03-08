<?php

declare(strict_types=1);

namespace Harbor\Auth;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\SignedWith;

require_once __DIR__.'/../auth.php';

use function Harbor\Config\config_get;
use function Harbor\Support\harbor_is_blank;

/** Public */
function auth_api_token(?array $request = null): ?string
{
    return auth_resolve_bearer_token($request);
}

function auth_api_exists(?array $request = null): bool
{
    return is_array(auth_api_get($request));
}

function auth_api_get(?array $request = null): ?array
{
    $token = auth_api_token($request);
    if (! is_string($token)) {
        return null;
    }

    $token_payload = auth_token_payload($token, true);
    if (empty($token_payload)) {
        return null;
    }

    $api_user_resolver = auth_api_runtime_config()['user_resolver'] ?? null;
    if (! ($api_user_resolver instanceof \Closure)) {
        return $token_payload;
    }

    $resolved_user = $api_user_resolver($token_payload, $request, $token);

    return auth_normalize_user($resolved_user);
}

function auth_api_login(array $user, array $claims = [], ?int $ttl_seconds = null): array
{
    $normalized_user = auth_normalize_user($user);

    if (null === $normalized_user) {
        throw new \InvalidArgumentException('API auth user payload must be a non-empty array.');
    }

    $subject = auth_user_subject($normalized_user);
    $access_token = auth_token_issue($subject, $claims, $ttl_seconds);
    $resolved_ttl_seconds = is_int($ttl_seconds)
        ? $ttl_seconds
        : auth_value_to_int(auth_api_runtime_config()['ttl_seconds'] ?? null, 3600);

    return [
        'token_type' => 'Bearer',
        'access_token' => $access_token,
        'expires_in' => $resolved_ttl_seconds,
    ];
}

function auth_api_logout(?string $token = null, ?array $request = null): bool
{
    $resolved_token = is_string($token) && ! harbor_is_blank(trim($token))
        ? trim($token)
        : auth_api_token($request);

    if (! is_string($resolved_token) || harbor_is_blank($resolved_token)) {
        return false;
    }

    return auth_token_revoke($resolved_token);
}

function auth_token_issue(int|string $subject, array $claims = [], ?int $ttl_seconds = null): string
{
    $normalized_subject = trim((string) $subject);

    if (harbor_is_blank($normalized_subject)) {
        throw new \InvalidArgumentException('Auth subject cannot be empty.');
    }

    $resolved_config = auth_api_runtime_config();
    $resolved_ttl_seconds = is_int($ttl_seconds)
        ? $ttl_seconds
        : auth_value_to_int($resolved_config['ttl_seconds'] ?? null, 3600);

    $token_payload = auth_build_token_payload($normalized_subject, $claims, $resolved_ttl_seconds, $resolved_config);

    if (auth_lcobucci_available()) {
        return auth_issue_lcobucci($token_payload, $resolved_config);
    }

    return auth_issue_native($token_payload, $resolved_config);
}

function auth_token_verify(string $token): bool
{
    return ! empty(auth_token_payload($token, true));
}

function auth_token_payload(string $token, bool $verify = true): array
{
    $normalized_token = trim($token);

    if (harbor_is_blank($normalized_token)) {
        return [];
    }

    $resolved_config = auth_api_runtime_config();
    $decoded_payload = auth_lcobucci_available()
        ? auth_decode_lcobucci($normalized_token, $verify, $resolved_config)
        : auth_decode_native($normalized_token, $verify, $resolved_config);

    if (empty($decoded_payload)) {
        return [];
    }

    if (! $verify) {
        return $decoded_payload;
    }

    if (! auth_payload_has_valid_registered_claims($decoded_payload, $resolved_config)) {
        return [];
    }

    if (auth_payload_is_revoked($decoded_payload, $resolved_config)) {
        return [];
    }

    return $decoded_payload;
}

function auth_token_revoke(string $token): bool
{
    $token_payload = auth_token_payload($token, true);

    $token_jti = auth_payload_string($token_payload, 'jti');
    if (harbor_is_blank($token_jti)) {
        return false;
    }

    $token_expires_at = auth_payload_int($token_payload, 'exp', time());

    return auth_revoke_store_add($token_jti, $token_expires_at, auth_revoke_store_path(auth_api_runtime_config()));
}

function auth_token_revoked(string $token): bool
{
    $token_payload = auth_token_payload($token, false);
    $token_jti = auth_payload_string($token_payload, 'jti');

    if (harbor_is_blank($token_jti)) {
        return false;
    }

    return auth_revoke_store_has($token_jti, auth_revoke_store_path(auth_api_runtime_config()));
}

/** Private */
function auth_api_runtime_config(): array
{
    $api_config = config_get('auth.api', []);
    $normalized_api_config = is_array($api_config) ? $api_config : [];

    return [
        'secret' => auth_value_to_string($normalized_api_config['secret'] ?? null),
        'issuer' => auth_value_to_string($normalized_api_config['issuer'] ?? 'harbor') ?? 'harbor',
        'audience' => auth_value_to_string($normalized_api_config['audience'] ?? 'harbor-api') ?? 'harbor-api',
        'ttl_seconds' => auth_value_to_int($normalized_api_config['ttl_seconds'] ?? 3600, 3600),
        'leeway_seconds' => max(0, auth_value_to_int($normalized_api_config['leeway_seconds'] ?? 0, 0)),
        'algorithm' => 'HS256',
        'revoke_store_path' => auth_value_to_string($normalized_api_config['revoke_store_path'] ?? null)
            ?? rtrim(sys_get_temp_dir(), '/').'/harbor_auth_revoked_tokens.json',
        'user_resolver' => auth_value_to_callable($normalized_api_config['user_resolver'] ?? null),
    ];
}

function auth_resolve_bearer_token(?array $request = null): ?string
{
    $authorization_header = '';

    if (is_array($request)) {
        $request_headers = is_array($request['headers'] ?? null) ? $request['headers'] : [];
        $request_authorization = $request_headers['authorization'] ?? $request_headers['Authorization'] ?? null;
        if (is_string($request_authorization)) {
            $authorization_header = $request_authorization;
        }
    }

    if (harbor_is_blank($authorization_header)) {
        $authorization_header = auth_server_authorization_header();
    }

    $normalized_header = trim($authorization_header);
    if (! str_starts_with(strtolower($normalized_header), 'bearer ')) {
        return null;
    }

    $token = trim(substr($normalized_header, 7));

    return harbor_is_blank($token) ? null : $token;
}

function auth_user_subject(array $user): string
{
    $subject_candidates = [
        $user['id'] ?? null,
        $user['sub'] ?? null,
        $user['subject'] ?? null,
        $user['uuid'] ?? null,
    ];

    foreach ($subject_candidates as $subject_candidate) {
        $subject = auth_value_to_string($subject_candidate);

        if (is_string($subject)) {
            return $subject;
        }
    }

    throw new \InvalidArgumentException('API auth user payload must include one of: id, sub, subject, uuid.');
}

function auth_build_token_payload(string $subject, array $claims, int $ttl_seconds, array $config): array
{
    $now = time();
    $expires_at = $now + $ttl_seconds;
    $token_jti = auth_generate_jti();

    $normalized_claims = [];

    foreach ($claims as $claim_key => $claim_value) {
        if (! is_string($claim_key) || harbor_is_blank(trim($claim_key))) {
            continue;
        }

        $normalized_claim_key = trim($claim_key);

        if (in_array($normalized_claim_key, ['iss', 'aud', 'sub', 'iat', 'nbf', 'exp', 'jti'], true)) {
            continue;
        }

        $normalized_claims[$normalized_claim_key] = $claim_value;
    }

    return [
        ...$normalized_claims,
        'iss' => auth_value_to_string($config['issuer'] ?? 'harbor') ?? 'harbor',
        'aud' => auth_value_to_string($config['audience'] ?? 'harbor-api') ?? 'harbor-api',
        'sub' => $subject,
        'iat' => $now,
        'nbf' => $now,
        'exp' => $expires_at,
        'jti' => $token_jti,
    ];
}

function auth_generate_jti(): string
{
    try {
        return bin2hex(random_bytes(16));
    } catch (\Throwable $exception) {
        throw new \RuntimeException('Failed to generate token identifier.', previous: $exception);
    }
}

function auth_lcobucci_available(): bool
{
    return class_exists(Configuration::class)
        && class_exists(Sha256::class)
        && class_exists(InMemory::class);
}

function auth_issue_lcobucci(array $payload, array $config): string
{
    $lcobucci_config = auth_lcobucci_configuration($config);
    $builder = $lcobucci_config->builder();

    $issued_at = auth_timestamp_to_immutable(auth_payload_int($payload, 'iat', time()));
    $not_before = auth_timestamp_to_immutable(auth_payload_int($payload, 'nbf', $issued_at->getTimestamp()));
    $expires_at = auth_timestamp_to_immutable(auth_payload_int($payload, 'exp', $issued_at->getTimestamp() + 3600));

    $builder = $builder
        ->issuedBy(auth_payload_string($payload, 'iss', 'harbor'))
        ->permittedFor(auth_payload_string($payload, 'aud', 'harbor-api'))
        ->relatedTo(auth_payload_string($payload, 'sub'))
        ->identifiedBy(auth_payload_string($payload, 'jti'))
        ->issuedAt($issued_at)
        ->canOnlyBeUsedAfter($not_before)
        ->expiresAt($expires_at)
    ;

    foreach ($payload as $claim_key => $claim_value) {
        if (in_array($claim_key, ['iss', 'aud', 'sub', 'iat', 'nbf', 'exp', 'jti'], true)) {
            continue;
        }

        $builder = $builder->withClaim($claim_key, $claim_value);
    }

    $token = $builder->getToken($lcobucci_config->signer(), $lcobucci_config->signingKey());

    return $token->toString();
}

function auth_decode_lcobucci(string $token, bool $verify, array $config): array
{
    try {
        $lcobucci_config = auth_lcobucci_configuration($config);
        $parsed_token = $lcobucci_config->parser()->parse($token);
    } catch (\Throwable $exception) {
        return [];
    }

    if (! $parsed_token instanceof UnencryptedToken) {
        return [];
    }

    if ($verify) {
        try {
            $signed_constraint = new SignedWith(
                $lcobucci_config->signer(),
                $lcobucci_config->verificationKey()
            );
        } catch (\Throwable $exception) {
            return [];
        }

        if (! $lcobucci_config->validator()->validate($parsed_token, $signed_constraint)) {
            return [];
        }
    }

    return auth_payload_normalize($parsed_token->claims()->all());
}

function auth_lcobucci_configuration(array $config): Configuration
{
    $secret = auth_require_secret($config);

    return Configuration::forSymmetricSigner(
        new Sha256(),
        InMemory::plainText($secret)
    );
}

function auth_issue_native(array $payload, array $config): string
{
    $secret = auth_require_secret($config);

    $header_segment = auth_base64_url_encode(json_encode([
        'alg' => 'HS256',
        'typ' => 'JWT',
    ], JSON_THROW_ON_ERROR));
    $payload_segment = auth_base64_url_encode(json_encode($payload, JSON_THROW_ON_ERROR));

    $signature = hash_hmac('sha256', $header_segment.'.'.$payload_segment, $secret, true);
    $signature_segment = auth_base64_url_encode($signature);

    return $header_segment.'.'.$payload_segment.'.'.$signature_segment;
}

function auth_decode_native(string $token, bool $verify, array $config): array
{
    $parts = explode('.', $token);

    if (3 !== count($parts)) {
        return [];
    }

    $decoded_header = auth_decode_json(auth_base64_url_decode($parts[0]));
    $decoded_payload = auth_decode_json(auth_base64_url_decode($parts[1]));
    $signature = auth_base64_url_decode($parts[2]);

    if (! is_array($decoded_header) || ! is_array($decoded_payload) || ! is_string($signature)) {
        return [];
    }

    if ($verify) {
        $secret = auth_require_secret($config);
        $header_algorithm = is_string($decoded_header['alg'] ?? null) ? strtoupper(trim($decoded_header['alg'])) : '';

        if ('HS256' !== $header_algorithm) {
            return [];
        }

        $expected_signature = hash_hmac('sha256', $parts[0].'.'.$parts[1], $secret, true);

        if (! hash_equals($expected_signature, $signature)) {
            return [];
        }
    }

    return auth_payload_normalize($decoded_payload);
}

function auth_payload_normalize(array $payload): array
{
    $normalized_payload = [];

    foreach ($payload as $claim_key => $claim_value) {
        if (! is_string($claim_key) || harbor_is_blank(trim($claim_key))) {
            continue;
        }

        $normalized_key = trim($claim_key);

        if ($claim_value instanceof \DateTimeInterface) {
            $normalized_payload[$normalized_key] = $claim_value->getTimestamp();

            continue;
        }

        $normalized_payload[$normalized_key] = $claim_value;
    }

    return $normalized_payload;
}

function auth_payload_has_valid_registered_claims(array $payload, array $config): bool
{
    $issued_by = auth_payload_string($payload, 'iss');
    $audience = $payload['aud'] ?? null;
    $subject = auth_payload_string($payload, 'sub');
    $issued_at = auth_payload_int($payload, 'iat', -1);
    $not_before = auth_payload_int($payload, 'nbf', -1);
    $expires_at = auth_payload_int($payload, 'exp', -1);
    $token_jti = auth_payload_string($payload, 'jti');

    if (
        harbor_is_blank($issued_by)
        || harbor_is_blank($subject)
        || harbor_is_blank($token_jti)
        || $issued_at < 0
        || $not_before < 0
        || $expires_at < 0
    ) {
        return false;
    }

    $expected_issuer = auth_value_to_string($config['issuer'] ?? null);
    if (! harbor_is_blank($expected_issuer) && $issued_by !== $expected_issuer) {
        return false;
    }

    $expected_audience = auth_value_to_string($config['audience'] ?? null);
    if (! harbor_is_blank($expected_audience) && ! auth_payload_audience_matches($audience, $expected_audience)) {
        return false;
    }

    $leeway_seconds = max(0, auth_value_to_int($config['leeway_seconds'] ?? 0, 0));
    $current_time = time();

    if ($current_time + $leeway_seconds < $issued_at) {
        return false;
    }

    if ($current_time + $leeway_seconds < $not_before) {
        return false;
    }

    if ($current_time - $leeway_seconds >= $expires_at) {
        return false;
    }

    return true;
}

function auth_payload_audience_matches(mixed $audience, string $expected_audience): bool
{
    if (is_string($audience)) {
        return trim($audience) === $expected_audience;
    }

    if (is_array($audience)) {
        foreach ($audience as $audience_value) {
            if (is_string($audience_value) && trim($audience_value) === $expected_audience) {
                return true;
            }
        }
    }

    return false;
}

function auth_payload_is_revoked(array $payload, array $config): bool
{
    $token_jti = auth_payload_string($payload, 'jti');

    if (harbor_is_blank($token_jti)) {
        return false;
    }

    return auth_revoke_store_has($token_jti, auth_revoke_store_path($config));
}

function auth_revoke_store_path(array $config): string
{
    return auth_value_to_string($config['revoke_store_path'] ?? null)
        ?? rtrim(sys_get_temp_dir(), '/').'/harbor_auth_revoked_tokens.json';
}

function auth_revoke_store_has(string $token_jti, string $store_path): bool
{
    $revoke_store = auth_revoke_store_read($store_path);

    return array_key_exists($token_jti, $revoke_store);
}

function auth_revoke_store_add(string $token_jti, int $expires_at, string $store_path): bool
{
    $store_directory = dirname($store_path);

    if (! is_dir($store_directory) && ! @mkdir($store_directory, 0o775, true) && ! is_dir($store_directory)) {
        return false;
    }

    $revoke_store = auth_revoke_store_read($store_path);
    $revoke_store[$token_jti] = max(time(), $expires_at);
    $revoke_store = auth_revoke_store_prune($revoke_store);

    return false !== file_put_contents(
        $store_path,
        json_encode($revoke_store, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );
}

function auth_revoke_store_read(string $store_path): array
{
    if (! is_file($store_path)) {
        return [];
    }

    $raw_store_content = file_get_contents($store_path);
    if (! is_string($raw_store_content) || harbor_is_blank(trim($raw_store_content))) {
        return [];
    }

    try {
        $decoded_store = json_decode($raw_store_content, true, 512, JSON_THROW_ON_ERROR);
    } catch (\Throwable $exception) {
        return [];
    }

    if (! is_array($decoded_store)) {
        return [];
    }

    return auth_revoke_store_prune($decoded_store);
}

function auth_revoke_store_prune(array $store): array
{
    $pruned_store = [];
    $current_time = time();

    foreach ($store as $token_jti => $expires_at) {
        if (! is_string($token_jti) || harbor_is_blank(trim($token_jti))) {
            continue;
        }

        $normalized_expires_at = auth_value_to_int($expires_at, 0);

        if ($normalized_expires_at <= $current_time) {
            continue;
        }

        $pruned_store[$token_jti] = $normalized_expires_at;
    }

    return $pruned_store;
}

function auth_server_authorization_header(): string
{
    $server = is_array($_SERVER) ? $_SERVER : [];

    $authorization = $server['HTTP_AUTHORIZATION']
        ?? $server['REDIRECT_HTTP_AUTHORIZATION']
        ?? $server['Authorization']
        ?? '';

    return is_string($authorization) ? $authorization : '';
}

function auth_require_secret(array $config): string
{
    $secret = auth_value_to_string($config['secret'] ?? null);

    if (harbor_is_blank($secret)) {
        throw new \RuntimeException('Auth API secret is required. Set auth.api.secret in config/auth.php.');
    }

    if (strlen($secret) < 32) {
        throw new \RuntimeException('Auth secret must be at least 32 bytes for HS256 signing.');
    }

    return $secret;
}

function auth_decode_json(?string $value): mixed
{
    if (! is_string($value) || harbor_is_blank(trim($value))) {
        return null;
    }

    try {
        return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
    } catch (\Throwable $exception) {
        return null;
    }
}

function auth_base64_url_encode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function auth_base64_url_decode(string $value): ?string
{
    $padded_value = strtr($value, '-_', '+/');
    $padding_length = strlen($padded_value) % 4;

    if (0 !== $padding_length) {
        $padded_value .= str_repeat('=', 4 - $padding_length);
    }

    $decoded = base64_decode($padded_value, true);

    return is_string($decoded) ? $decoded : null;
}

function auth_timestamp_to_immutable(int $timestamp): \DateTimeImmutable
{
    return new \DateTimeImmutable('@'.$timestamp)->setTimezone(new \DateTimeZone('UTC'));
}

function auth_payload_string(array $payload, string $key, string $default = ''): string
{
    $value = $payload[$key] ?? null;

    if (! is_scalar($value) && ! (is_object($value) && method_exists($value, '__toString'))) {
        return $default;
    }

    $normalized_value = trim((string) $value);

    return harbor_is_blank($normalized_value) ? $default : $normalized_value;
}

function auth_payload_int(array $payload, string $key, int $default = 0): int
{
    $value = $payload[$key] ?? null;

    return auth_value_to_int($value, $default);
}
