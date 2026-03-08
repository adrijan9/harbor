<?php

declare(strict_types=1);

namespace Tests\Feature;

require_once __DIR__.'/../TestCase.php';

use Tests\TestCase;

final class HomePageTest extends TestCase
{
    public function test_home_page_returns_success_response(): void
    {
        $response = $this->get('/');

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('Welcome to Harbor Site', $response['content']);
    }
}
