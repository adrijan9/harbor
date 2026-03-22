<?php

declare(strict_types=1);

namespace Harbor\Tests\View;

use Harbor\View\ViewException;
use Harbor\View\ViewRenderer;
use PHPUnit\Framework\TestCase;

/**
 * Class ViewRendererTest.
 */
final class ViewRendererTest extends TestCase
{
    private string $views_path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->views_path = dirname(__DIR__).'/Fixtures/view/views';
    }

    public function test_set_base_path_and_base_path_returns_resolved_views_path(): void
    {
        $renderer = new ViewRenderer();
        $renderer->set_base_path($this->views_path);

        self::assertSame(
            realpath($this->views_path),
            $renderer->base_path()
        );
    }

    public function test_render_returns_view_output_without_layout(): void
    {
        $renderer = new ViewRenderer();
        $renderer->set_base_path($this->views_path);

        $output = $renderer->render('pages/welcome', [
            'title' => 'Hello',
            'message' => 'Ada',
        ]);

        self::assertStringContainsString('<h1>Hello</h1>', $output);
        self::assertStringContainsString('<p>Ada</p>', $output);
    }

    public function test_render_with_layout_wraps_content_and_layout_data(): void
    {
        $renderer = new ViewRenderer();
        $renderer->set_base_path($this->views_path);

        $output = $renderer->render(
            'pages/plain',
            ['name' => 'Ada'],
            'layouts/basic',
            ['footer' => 'Footer text']
        );

        self::assertStringContainsString('Plain view for Ada.', $output);
        self::assertStringContainsString('<footer>Footer text</footer>', $output);
    }

    public function test_render_partial_uses_shared_and_local_data(): void
    {
        $renderer = new ViewRenderer();
        $renderer->set_base_path($this->views_path);
        $renderer->share('text', 'Global');

        self::assertSame('[Global]', trim($renderer->render_partial('partials/chip_raw')));
        self::assertSame('[Local]', trim($renderer->render_partial('partials/chip_raw', ['text' => 'Local'])));
    }

    public function test_exists_reports_existing_and_missing_templates(): void
    {
        $renderer = new ViewRenderer();
        $renderer->set_base_path($this->views_path);

        self::assertTrue($renderer->exists('pages/welcome'));
        self::assertFalse($renderer->exists('pages/missing'));
    }

    public function test_render_throws_when_content_key_is_provided_with_layout(): void
    {
        $renderer = new ViewRenderer();
        $renderer->set_base_path($this->views_path);

        $this->expectException(ViewException::class);
        $this->expectExceptionMessage('View key "content" is reserved when rendering with a layout.');

        $renderer->render(
            'pages/plain',
            ['name' => 'Ada', 'content' => 'forbidden'],
            'layouts/basic',
            ['footer' => 'Footer text']
        );
    }

    public function test_render_throws_for_invalid_template_name_with_traversal(): void
    {
        $renderer = new ViewRenderer();
        $renderer->set_base_path($this->views_path);

        $this->expectException(ViewException::class);
        $this->expectExceptionMessage('View template name "../secret" is invalid.');

        $renderer->render('../secret');
    }

    public function test_clear_shared_removes_shared_values(): void
    {
        $renderer = new ViewRenderer();
        $renderer->set_base_path($this->views_path);
        $renderer->share('app_name', 'Harbor');

        self::assertSame('Harbor', $renderer->shared('app_name'));

        $renderer->clear_shared();

        self::assertSame([], $renderer->shared());
        self::assertSame('fallback', $renderer->shared('app_name', 'fallback'));
    }
}
