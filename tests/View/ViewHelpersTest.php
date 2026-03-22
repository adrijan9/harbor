<?php

declare(strict_types=1);

namespace Harbor\Tests\View;

use Harbor\Helper;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;

use function Harbor\View\view;
use function Harbor\View\view_clear_shared;
use function Harbor\View\view_e;
use function Harbor\View\view_exists;
use function Harbor\View\view_partial;
use function Harbor\View\view_partial_render;
use function Harbor\View\view_path;
use function Harbor\View\view_render;
use function Harbor\View\view_reset_path;
use function Harbor\View\view_set_path;
use function Harbor\View\view_share;
use function Harbor\View\view_share_many;
use function Harbor\View\view_shared;

/**
 * Class ViewHelpersTest.
 */
final class ViewHelpersTest extends TestCase
{
    private string $views_path;

    public function test_view_render_and_view_helpers_return_same_output(): void
    {
        $rendered = view_render('pages/welcome', [
            'title' => 'Home',
            'message' => 'Welcome',
        ]);

        ob_start();

        try {
            view('pages/welcome', [
                'title' => 'Home',
                'message' => 'Welcome',
            ]);
            $echoed = ob_get_clean();
        } catch (\Throwable $throwable) {
            ob_end_clean();

            throw $throwable;
        }

        self::assertSame($rendered, $echoed);
    }

    public function test_view_partial_helpers_render_and_escape_values(): void
    {
        $rendered = trim(view_partial_render('partials/chip', [
            'text' => '<b>safe</b>',
        ]));

        ob_start();

        try {
            view_partial('partials/chip', [
                'text' => '<b>safe</b>',
            ]);
            $echoed = trim((string) ob_get_clean());
        } catch (\Throwable $throwable) {
            ob_end_clean();

            throw $throwable;
        }

        self::assertSame('[&lt;b&gt;safe&lt;/b&gt;]', $rendered);
        self::assertSame($rendered, $echoed);
    }

    public function test_view_share_helpers_store_and_clear_shared_values(): void
    {
        view_share('app_name', 'Harbor');
        view_share_many([
            'environment' => 'production',
            'release' => '1.0.0',
        ]);

        self::assertSame('Harbor', view_shared('app_name'));
        self::assertSame('production', view_shared('environment'));
        self::assertSame('fallback', view_shared('missing', 'fallback'));
        self::assertArrayHasKey('release', view_shared());

        view_clear_shared();

        self::assertSame([], view_shared());
        self::assertSame('fallback', view_shared('app_name', 'fallback'));
    }

    public function test_view_exists_and_view_path_helpers_work_with_runtime_path(): void
    {
        self::assertTrue(view_exists('pages/welcome'));
        self::assertFalse(view_exists('pages/missing'));
        self::assertSame(
            realpath($this->views_path),
            view_path()
        );
    }

    public function test_view_layout_supports_conditional_header_and_multi_region_partial(): void
    {
        $production_output = view_render(
            'pages/welcome',
            [
                'title' => 'Dashboard',
                'message' => 'Main body',
            ],
            'layouts/app',
            [
                'is_production' => true,
                'right_partial' => 'partials/right_box',
                'right_data' => ['label' => 'Insights'],
            ]
        );

        $development_output = view_render(
            'pages/welcome',
            [
                'title' => 'Dashboard',
                'message' => 'Main body',
            ],
            'layouts/app',
            [
                'is_production' => false,
                'right_partial' => 'partials/right_box',
                'right_data' => ['label' => 'Insights'],
            ]
        );

        self::assertStringContainsString('Header PROD', $production_output);
        self::assertStringContainsString('Header DEV', $development_output);
        self::assertStringContainsString('Right: Insights', $production_output);
        self::assertStringContainsString('<main><h1>Dashboard</h1>', $production_output);
    }

    public function test_view_e_escapes_scalar_and_array_values(): void
    {
        self::assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', view_e('<script>alert(1)</script>'));
        self::assertSame('42', view_e(42));
        self::assertStringContainsString('&quot;a&quot;:1', view_e(['a' => 1]));
    }

    #[Before]
    protected function bootstrap_view_helpers(): void
    {
        $this->views_path = dirname(__DIR__).'/Fixtures/view/views';

        Helper::load_many('view');
        view_clear_shared();
        view_set_path($this->views_path);
    }

    #[After]
    protected function restore_view_helpers(): void
    {
        view_clear_shared();
        view_reset_path();
    }
}
