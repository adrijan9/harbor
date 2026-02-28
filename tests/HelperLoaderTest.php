<?php

declare(strict_types=1);

namespace Harbor\Tests;

use Harbor\HelperLoader;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;

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
        self::assertContains('request', $helpers);
        self::assertContains('response', $helpers);
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

    public function test_load_response_helper_registers_namespaced_functions(): void
    {
        HelperLoader::load('response');

        self::assertTrue(function_exists('Harbor\Response\response_status'));
        self::assertTrue(function_exists('Harbor\Response\response_json'));
        self::assertTrue(function_exists('Harbor\Response\response_file'));
        self::assertTrue(function_exists('Harbor\Response\response_download'));
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
