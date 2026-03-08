<?php

declare(strict_types=1);

namespace Harbor\Tests\Auth;

use Harbor\HelperLoader;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

use function Harbor\Auth\auth_api_exists;
use function Harbor\Auth\auth_api_get;
use function Harbor\Auth\auth_api_login;
use function Harbor\Auth\auth_api_logout;
use function Harbor\Auth\auth_api_token;
use function Harbor\Auth\auth_attempt;
use function Harbor\Auth\auth_token_issue;
use function Harbor\Auth\auth_token_payload;
use function Harbor\Auth\auth_token_revoked;
use function Harbor\Auth\auth_token_verify;
use function Harbor\Auth\auth_web_exists;
use function Harbor\Auth\auth_web_get;
use function Harbor\Auth\auth_web_login;
use function Harbor\Auth\auth_web_logout;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
/**
 * Class AuthHelpersTest.
 */
final class AuthHelpersTest extends TestCase
{
    private array $original_server = [];
    private array $original_cookie = [];
    private array $original_env = [];
    private string $revoke_store_path = '';

    public function test_auth_token_issue_and_verify_round_trip(): void
    {
        $token = auth_token_issue('42', [
            'role' => 'admin',
        ]);

        self::assertTrue(auth_token_verify($token));

        $payload = auth_token_payload($token);

        self::assertSame('42', $payload['sub'] ?? null);
        self::assertSame('admin', $payload['role'] ?? null);
        self::assertIsString($payload['jti'] ?? null);
    }

    public function test_auth_api_token_resolves_from_request_headers(): void
    {
        $request = [
            'headers' => [
                'authorization' => 'Bearer token-value',
            ],
        ];

        self::assertSame('token-value', auth_api_token($request));
    }

    public function test_auth_api_token_resolves_from_server_header(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token-value';

        self::assertSame('token-value', auth_api_token());
    }

    public function test_auth_api_login_returns_bearer_payload_and_api_get_resolves_token_claims(): void
    {
        $login_result = auth_api_login([
            'id' => 42,
            'email' => 'ada@example.test',
        ], [
            'role' => 'admin',
        ]);

        self::assertSame('Bearer', $login_result['token_type'] ?? null);
        self::assertIsString($login_result['access_token'] ?? null);
        self::assertSame(3600, $login_result['expires_in'] ?? null);

        $request = [
            'headers' => [
                'authorization' => 'Bearer '.$login_result['access_token'],
            ],
        ];

        self::assertTrue(auth_api_exists($request));

        $user = auth_api_get($request);

        self::assertIsArray($user);
        self::assertSame('42', $user['sub'] ?? null);
        self::assertSame('admin', $user['role'] ?? null);
    }

    public function test_auth_api_logout_revokes_token_and_prevents_future_validation(): void
    {
        $token = auth_token_issue('42');

        self::assertFalse(auth_token_revoked($token));
        self::assertTrue(auth_api_logout($token));
        self::assertTrue(auth_token_revoked($token));
        self::assertFalse(auth_token_verify($token));
    }

    public function test_auth_web_login_get_exists_and_logout_flow(): void
    {
        self::assertFalse(auth_web_exists());
        self::assertNull(auth_web_get());

        auth_web_login([
            'id' => 7,
            'name' => 'Ada',
        ]);

        self::assertTrue(auth_web_exists());
        self::assertSame(7, auth_web_get()['id'] ?? null);
        self::assertSame('Ada', auth_web_get()['name'] ?? null);

        self::assertTrue(auth_web_logout());
        self::assertFalse(auth_web_exists());
        self::assertNull(auth_web_get());
    }

    public function test_auth_attempt_uses_configured_attempt_resolver(): void
    {
        $this->set_auth_config(
            web_overrides: [
                'attempt_resolver' => static function (array $credentials): ?array {
                    if (
                        'ada@example.test' === ($credentials['email'] ?? null)
                        && 'secret' === ($credentials['password'] ?? null)
                    ) {
                        return [
                            'id' => 101,
                            'email' => 'ada@example.test',
                        ];
                    }

                    return null;
                },
            ],
        );

        $user = auth_attempt([
            'email' => 'ada@example.test',
            'password' => 'secret',
        ]);

        self::assertIsArray($user);
        self::assertSame(101, $user['id'] ?? null);

        self::assertNull(auth_attempt([
            'email' => 'ada@example.test',
            'password' => 'invalid',
        ]));
    }

    public function test_auth_api_get_uses_configured_api_user_resolver(): void
    {
        $this->set_auth_config(
            api_overrides: [
                'user_resolver' => static fn (array $token_payload): array => [
                    'id' => (int) ($token_payload['sub'] ?? 0),
                    'role' => $token_payload['role'] ?? null,
                ],
            ],
        );

        $token = auth_token_issue('77', [
            'role' => 'editor',
        ]);

        $user = auth_api_get([
            'headers' => [
                'authorization' => 'Bearer '.$token,
            ],
        ]);

        self::assertIsArray($user);
        self::assertSame(77, $user['id'] ?? null);
        self::assertSame('editor', $user['role'] ?? null);
    }

    public function test_auth_token_verify_fails_for_tampered_signature(): void
    {
        $token = auth_token_issue('42');
        $tampered_token = $token.'tampered';

        self::assertFalse(auth_token_verify($tampered_token));
    }

    public function test_auth_token_can_be_issued_as_already_expired_using_negative_ttl(): void
    {
        $token = auth_token_issue('42', [], -60);

        self::assertFalse(auth_token_verify($token));
    }

    public function test_auth_token_issue_throws_for_short_secret(): void
    {
        $this->set_auth_config(
            api_overrides: [
                'secret' => 'short-secret',
            ],
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Auth secret must be at least 32 bytes');

        auth_token_issue('42');
    }

    #[Before]
    protected function bootstrap_auth_helpers(): void
    {
        $this->original_server = $_SERVER;
        $this->original_cookie = $_COOKIE;
        $this->original_env = is_array($_ENV) ? $_ENV : [];
        $this->revoke_store_path = sys_get_temp_dir().'/harbor_auth_helpers_test_'.bin2hex(random_bytes(8)).'.json';

        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
        ];
        $_COOKIE = [];

        if (is_file($this->revoke_store_path)) {
            unlink($this->revoke_store_path);
        }

        HelperLoader::load('auth');
        $this->set_auth_config();
    }

    #[After]
    protected function cleanup_auth_helpers(): void
    {
        $_SERVER = $this->original_server;
        $_COOKIE = $this->original_cookie;
        $_ENV = $this->original_env;
        $GLOBALS['_ENV'] = $_ENV;

        if (is_file($this->revoke_store_path)) {
            unlink($this->revoke_store_path);
        }
    }

    private function set_auth_config(array $web_overrides = [], array $api_overrides = []): void
    {
        $_ENV['auth'] = [
            'web' => [
                'session_key' => 'auth_web_user',
                'attempt_resolver' => null,
                ...$web_overrides,
            ],
            'api' => [
                'secret' => 'test-auth-secret-should-be-at-least-32-bytes',
                'issuer' => 'harbor-tests',
                'audience' => 'harbor-tests-api',
                'ttl_seconds' => 3600,
                'leeway_seconds' => 0,
                'revoke_store_path' => $this->revoke_store_path,
                'attempt_resolver' => null,
                'user_resolver' => null,
                ...$api_overrides,
            ],
        ];
        $GLOBALS['_ENV'] = $_ENV;
    }
}
