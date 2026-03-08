<?php

declare(strict_types=1);

namespace Tests\Unit;

require_once __DIR__.'/../TestCase.php';

use Tests\TestCase;

final class ExampleTest extends TestCase
{
    public function test_global_configuration_includes_default_app_name(): void
    {
        $config = require $this->site_path('global.php');

        self::assertIsArray($config);
        self::assertSame('Harbor Site', $config['app_name'] ?? null);
    }
}
