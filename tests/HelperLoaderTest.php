<?php

declare(strict_types=1);

namespace Harbor\Tests;

use Carbon\Carbon;
use Harbor\HelperLoader;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;

/**
 * Class HelperLoaderTest.
 */
final class HelperLoaderTest extends TestCase
{
    private array $original_server = [];
    private bool $had_request = false;
    private mixed $original_request = null;

    public function test_available_contains_registered_helpers(): void
    {
        $helpers = HelperLoader::available();

        self::assertContains('route', $helpers);
        self::assertContains('route_segments', $helpers);
        self::assertContains('route_query', $helpers);
        self::assertContains('route_named', $helpers);
        self::assertContains('config', $helpers);
        self::assertContains('value', $helpers);
        self::assertContains('array', $helpers);
        self::assertContains('carbon', $helpers);
        self::assertContains('pipeline', $helpers);
        self::assertContains('middleware', $helpers);
        self::assertContains('request', $helpers);
        self::assertContains('cookie', $helpers);
        self::assertContains('session', $helpers);
        self::assertContains('password', $helpers);
        self::assertContains('auth_web', $helpers);
        self::assertContains('auth_api', $helpers);
        self::assertContains('auth', $helpers);
        self::assertContains('response', $helpers);
        self::assertContains('db', $helpers);
        self::assertContains('database', $helpers);
        self::assertContains('db_sqlite', $helpers);
        self::assertContains('db_mysql_pdo', $helpers);
        self::assertContains('db_mysqli', $helpers);
        self::assertContains('validation', $helpers);
        self::assertContains('performance', $helpers);
        self::assertContains('units', $helpers);
        self::assertContains('filesystem', $helpers);
        self::assertContains('cache_array', $helpers);
        self::assertContains('cache_file', $helpers);
        self::assertContains('cache_apc', $helpers);
        self::assertContains('cache', $helpers);
        self::assertContains('log', $helpers);
        self::assertContains('lang', $helpers);
        self::assertContains('language', $helpers);
        self::assertContains('translation', $helpers);
        self::assertContains('translations', $helpers);
    }

    public function test_load_throws_for_unknown_helper(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Helper "unknown_helper" is not registered.');

        HelperLoader::load('unknown_helper');
    }

    public function test_load_route_helper_registers_namespaced_functions(): void
    {
        HelperLoader::load('route');

        self::assertTrue(function_exists('Harbor\Router\route_segment'));
        self::assertTrue(function_exists('Harbor\Router\route_query'));
        self::assertTrue(function_exists('Harbor\Router\route_exists'));
        self::assertTrue(function_exists('Harbor\Router\route_name_is'));
        self::assertTrue(function_exists('Harbor\Router\route'));
    }

    public function test_load_request_helper_registers_namespaced_functions(): void
    {
        HelperLoader::load('request');

        self::assertTrue(function_exists('Harbor\Request\request'));
        self::assertTrue(function_exists('Harbor\Request\request_method'));
    }

    public function test_load_cookie_helper_registers_namespaced_functions(): void
    {
        HelperLoader::load('cookie');

        self::assertTrue(function_exists('Harbor\Cookie\cookie_set'));
        self::assertTrue(function_exists('Harbor\Cookie\cookie_get'));
        self::assertTrue(function_exists('Harbor\Cookie\cookie_forget'));
    }

    public function test_load_session_helper_registers_namespaced_functions(): void
    {
        HelperLoader::load('session');

        self::assertTrue(function_exists('Harbor\Session\session_set'));
        self::assertTrue(function_exists('Harbor\Session\session_get'));
        self::assertTrue(function_exists('Harbor\Session\session_forget'));
        self::assertTrue(function_exists('Harbor\Session\session_flash_set'));
        self::assertTrue(function_exists('Harbor\Session\session_flash_get'));
        self::assertTrue(function_exists('Harbor\Session\session_flash_pull'));
    }

    public function test_load_password_helper_registers_namespaced_functions(): void
    {
        HelperLoader::load('password');

        self::assertTrue(function_exists('Harbor\Password\password_hash'));
        self::assertTrue(function_exists('Harbor\Password\password_verify'));
        self::assertTrue(function_exists('Harbor\Password\password_needs_rehash'));
        self::assertTrue(function_exists('Harbor\Password\bcrypt'));
        self::assertTrue(function_exists('Harbor\Password\argon2i'));
        self::assertTrue(function_exists('Harbor\Password\argon2id'));
        self::assertTrue(enum_exists('Harbor\Password\PasswordAlgorithm'));
    }

    public function test_load_auth_helper_registers_namespaced_functions(): void
    {
        HelperLoader::load('auth');

        self::assertFalse(function_exists('Harbor\Auth\auth_init'));
        self::assertTrue(function_exists('Harbor\Auth\auth_attempt'));
        self::assertTrue(function_exists('Harbor\Auth\auth_web_exists'));
        self::assertTrue(function_exists('Harbor\Auth\auth_web_login'));
        self::assertTrue(function_exists('Harbor\Auth\auth_web_logout'));
        self::assertTrue(function_exists('Harbor\Auth\auth_api_token'));
        self::assertTrue(function_exists('Harbor\Auth\auth_api_exists'));
        self::assertTrue(function_exists('Harbor\Auth\auth_api_get'));
        self::assertTrue(function_exists('Harbor\Auth\auth_api_login'));
        self::assertTrue(function_exists('Harbor\Auth\auth_api_logout'));
        self::assertTrue(function_exists('Harbor\Auth\auth_token_issue'));
        self::assertTrue(function_exists('Harbor\Auth\auth_token_verify'));
        self::assertTrue(function_exists('Harbor\Auth\auth_token_revoke'));
    }

    public function test_load_auth_web_helper_registers_web_auth_functions(): void
    {
        HelperLoader::load('auth_web');

        self::assertTrue(function_exists('Harbor\Auth\auth_web_exists'));
        self::assertTrue(function_exists('Harbor\Auth\auth_web_get'));
        self::assertTrue(function_exists('Harbor\Auth\auth_web_login'));
        self::assertTrue(function_exists('Harbor\Auth\auth_web_logout'));
    }

    public function test_load_auth_api_helper_registers_api_auth_functions(): void
    {
        HelperLoader::load('auth_api');

        self::assertTrue(function_exists('Harbor\Auth\auth_api_token'));
        self::assertTrue(function_exists('Harbor\Auth\auth_api_exists'));
        self::assertTrue(function_exists('Harbor\Auth\auth_api_get'));
        self::assertTrue(function_exists('Harbor\Auth\auth_api_login'));
        self::assertTrue(function_exists('Harbor\Auth\auth_api_logout'));
    }

    public function test_load_response_helper_registers_namespaced_functions(): void
    {
        HelperLoader::load('response');

        self::assertTrue(function_exists('Harbor\Response\response_status'));
        self::assertTrue(function_exists('Harbor\Response\response_json'));
        self::assertTrue(function_exists('Harbor\Response\response_file'));
        self::assertTrue(function_exists('Harbor\Response\response_download'));
        self::assertTrue(function_exists('Harbor\Response\response_validation'));
        self::assertTrue(function_exists('Harbor\Response\abort'));
        self::assertTrue(class_exists('Harbor\Response\ResponseStatus'));
    }

    public function test_load_validation_helper_registers_namespaced_functions(): void
    {
        HelperLoader::load('validation');

        self::assertTrue(function_exists('Harbor\Validation\validation_rule'));
        self::assertTrue(function_exists('Harbor\Validation\validation_validate'));
        self::assertTrue(function_exists('Harbor\Validation\validation_errors'));
        self::assertTrue(function_exists('Harbor\Validation\validation_has_errors'));
        self::assertFalse(function_exists('Harbor\Validation\validator_has_error'));
    }

    public function test_load_db_helper_registers_namespaced_functions(): void
    {
        HelperLoader::load('db');

        self::assertTrue(function_exists('Harbor\Database\db_connect'));
        self::assertTrue(function_exists('Harbor\Database\db_driver'));
        self::assertTrue(function_exists('Harbor\Database\db_execute'));
        self::assertTrue(function_exists('Harbor\Database\db_array'));
        self::assertTrue(function_exists('Harbor\Database\db_first'));
        self::assertTrue(function_exists('Harbor\Database\db_last'));
        self::assertTrue(function_exists('Harbor\Database\db_objects'));
        self::assertTrue(function_exists('Harbor\Database\db_sqlite_connect'));
        self::assertTrue(function_exists('Harbor\Database\db_sqlite_connect_dto'));
        self::assertTrue(function_exists('Harbor\Database\db_sqlite_close'));
        self::assertTrue(function_exists('Harbor\Database\db_sqlite_first'));
        self::assertTrue(function_exists('Harbor\Database\db_sqlite_last'));
        self::assertTrue(function_exists('Harbor\Database\db_mysql_connect'));
        self::assertTrue(function_exists('Harbor\Database\db_mysql_connect_dto'));
        self::assertTrue(function_exists('Harbor\Database\db_mysql_pdo_close'));
        self::assertTrue(function_exists('Harbor\Database\db_mysql_first'));
        self::assertTrue(function_exists('Harbor\Database\db_mysql_last'));
        self::assertTrue(function_exists('Harbor\Database\db_mysqli_connect'));
        self::assertTrue(function_exists('Harbor\Database\db_mysqli_connect_dto'));
        self::assertTrue(function_exists('Harbor\Database\db_mysqli_close'));
        self::assertTrue(function_exists('Harbor\Database\db_mysqli_first'));
        self::assertTrue(function_exists('Harbor\Database\db_mysqli_last'));
        self::assertTrue(class_exists('Harbor\Database\SqliteDto'));
        self::assertTrue(class_exists('Harbor\Database\MysqlDto'));
    }

    public function test_load_performance_helper_registers_namespaced_functions(): void
    {
        HelperLoader::load('performance');

        self::assertTrue(function_exists('Harbor\Performance\performance_begin'));
        self::assertTrue(function_exists('Harbor\Performance\performance_end'));
        self::assertTrue(function_exists('Harbor\Performance\performance_end_log'));
        self::assertFalse(function_exists('Harbor\Performance\performance_start'));
        self::assertFalse(function_exists('Harbor\Performance\performace_end_log'));
        self::assertFalse(function_exists('Harbor\Performance\performace_start'));
        self::assertFalse(function_exists('Harbor\Performance\performace_end'));
    }

    public function test_load_units_helper_registers_namespaced_functions(): void
    {
        HelperLoader::load('units');

        self::assertTrue(function_exists('Harbor\Units\unit_kb_from_mb'));
        self::assertTrue(function_exists('Harbor\Units\unit_mb_from_kb'));
        self::assertTrue(function_exists('Harbor\Units\unit_bytes_to_human'));
        self::assertTrue(function_exists('Harbor\Units\unit_duration_ms_to_human'));
        self::assertFalse(function_exists('Harbor\Units\unit_mb_to_kg'));
    }

    public function test_load_config_helper_registers_namespaced_functions(): void
    {
        HelperLoader::load('config');

        self::assertTrue(function_exists('Harbor\Config\config'));
        self::assertTrue(function_exists('Harbor\Config\config_init'));
        self::assertTrue(function_exists('Harbor\Config\config_int'));
    }

    public function test_load_value_helper_registers_namespaced_functions(): void
    {
        HelperLoader::load('value');

        self::assertTrue(function_exists('Harbor\Support\harbor_is_blank'));
        self::assertTrue(function_exists('Harbor\Support\harbor_is_null'));
    }

    public function test_load_array_helper_registers_namespaced_functions(): void
    {
        HelperLoader::load('array');

        self::assertTrue(function_exists('Harbor\Support\array_forget'));
        self::assertTrue(function_exists('Harbor\Support\array_first'));
        self::assertTrue(function_exists('Harbor\Support\array_last'));
    }

    public function test_load_carbon_helper_registers_namespaced_functions(): void
    {
        if (! class_exists(Carbon::class)) {
            self::markTestSkipped('nesbot/carbon is not installed in this environment.');
        }

        HelperLoader::load('carbon');

        self::assertTrue(function_exists('Harbor\Date\carbon'));
        self::assertTrue(function_exists('Harbor\Date\date_now'));
        self::assertTrue(class_exists('Harbor\Date\Carbon'));
    }

    public function test_load_pipeline_helper_registers_namespaced_functions(): void
    {
        HelperLoader::load('pipeline');

        self::assertTrue(function_exists('Harbor\Pipeline\pipeline_new'));
        self::assertTrue(function_exists('Harbor\Pipeline\pipeline_send'));
        self::assertTrue(function_exists('Harbor\Pipeline\pipeline_through'));
        self::assertTrue(function_exists('Harbor\Pipeline\pipeline_clog'));
        self::assertTrue(function_exists('Harbor\Pipeline\pipeline_get'));
    }

    public function test_load_middleware_helper_registers_namespaced_functions(): void
    {
        HelperLoader::load('middleware');

        self::assertTrue(function_exists('Harbor\Middleware\middleware'));
        self::assertTrue(function_exists('Harbor\Middleware\csrf_token'));
        self::assertTrue(function_exists('Harbor\Middleware\csrf_field'));
        self::assertTrue(class_exists('Harbor\Middleware\ApiAuthMiddleware'));
        self::assertTrue(class_exists('Harbor\Middleware\WebAuthMiddleware'));
        self::assertTrue(class_exists('Harbor\Middleware\BasicAuthMiddleware'));
        self::assertTrue(class_exists('Harbor\Middleware\CsrfMiddleware'));
        self::assertTrue(class_exists('Harbor\Middleware\ThrottleMiddleware'));
        self::assertTrue(class_exists('Harbor\Middleware\CorsMiddleware'));
    }

    public function test_load_filesystem_helper_registers_namespaced_functions(): void
    {
        HelperLoader::load('filesystem');

        self::assertTrue(function_exists('Harbor\Filesystem\fs_read'));
        self::assertTrue(function_exists('Harbor\Filesystem\fs_dir_create'));
    }

    public function test_load_cache_array_helper_registers_namespaced_functions(): void
    {
        HelperLoader::load('cache_array');

        self::assertTrue(function_exists('Harbor\Cache\cache_array_set'));
        self::assertTrue(function_exists('Harbor\Cache\cache_array_get'));
    }

    public function test_load_cache_file_helper_registers_namespaced_functions(): void
    {
        HelperLoader::load('cache_file');

        self::assertTrue(function_exists('Harbor\Cache\cache_file_set'));
        self::assertTrue(function_exists('Harbor\Cache\cache_file_get'));
        self::assertTrue(function_exists('Harbor\Cache\cache_file_set_path'));
        self::assertTrue(function_exists('Harbor\Cache\cache_file_reset_path'));
    }

    public function test_load_cache_apc_helper_registers_namespaced_functions(): void
    {
        HelperLoader::load('cache_apc');

        self::assertTrue(function_exists('Harbor\Cache\cache_apc_available'));
        self::assertTrue(function_exists('Harbor\Cache\cache_apc_set'));
        self::assertTrue(function_exists('Harbor\Cache\cache_apc_get'));
    }

    public function test_load_cache_helper_registers_namespaced_functions(): void
    {
        HelperLoader::load('cache');

        self::assertTrue(function_exists('Harbor\Cache\cache_driver'));
        self::assertTrue(function_exists('Harbor\Cache\cache_is_array'));
        self::assertTrue(function_exists('Harbor\Cache\cache_is_file'));
        self::assertTrue(function_exists('Harbor\Cache\cache_is_apc'));
        self::assertTrue(function_exists('Harbor\Cache\cache_set'));
        self::assertTrue(function_exists('Harbor\Cache\cache_get'));
    }

    public function test_load_log_helper_registers_namespaced_functions(): void
    {
        HelperLoader::load('log');

        self::assertTrue(function_exists('Harbor\Log\log_init'));
        self::assertTrue(function_exists('Harbor\Log\log_error'));
    }

    public function test_load_lang_helper_registers_namespaced_functions(): void
    {
        HelperLoader::load('lang');

        self::assertTrue(function_exists('Harbor\Lang\lang_get'));
        self::assertTrue(function_exists('Harbor\Lang\lang_set'));
    }

    public function test_load_translation_helper_registers_namespaced_functions(): void
    {
        HelperLoader::load('translation');

        self::assertTrue(function_exists('Harbor\Lang\translation_init'));
        self::assertTrue(function_exists('Harbor\Lang\t'));
    }

    #[Before]
    protected function prepare_globals(): void
    {
        $this->original_server = $_SERVER;
        $this->had_request = array_key_exists('request', $GLOBALS);
        $this->original_request = $this->had_request ? $GLOBALS['request'] : null;

        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
        ];
    }

    #[After]
    protected function restore_globals(): void
    {
        $_SERVER = $this->original_server;

        if ($this->had_request) {
            $GLOBALS['request'] = $this->original_request;

            return;
        }

        unset($GLOBALS['request']);
    }
}
