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
        self::assertContains('filesystem', $helpers);
        self::assertContains('log', $helpers);
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

    public function test_load_log_helper_registers_namespaced_functions(): void
    {
        HelperLoader::load('log');

        self::assertTrue(function_exists('Harbor\Log\log_init'));
        self::assertTrue(function_exists('Harbor\Log\log_error'));
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
