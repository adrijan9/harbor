<?php

declare(strict_types=1);

namespace Harbor\View;

require_once __DIR__.'/../Config/config.php';

require_once __DIR__.'/../Support/value.php';

use function Harbor\Config\config_get;
use function Harbor\Support\harbor_is_blank;

/**
 * Class ViewRenderer.
 */
final class ViewRenderer
{
    private ?string $base_path_override = null;

    /**
     * @var array<string, mixed>
     */
    private array $shared_data = [];

    public function set_base_path(string $path): void
    {
        $normalized_path = $this->normalize_base_path($path);

        if (! is_dir($normalized_path)) {
            throw new ViewException(sprintf('View path "%s" does not exist.', $normalized_path));
        }

        $resolved_path = realpath($normalized_path);
        if (false === $resolved_path) {
            throw new ViewException(sprintf('View path "%s" could not be resolved.', $normalized_path));
        }

        $this->base_path_override = rtrim($resolved_path, '/\\');
    }

    public function reset_base_path(): void
    {
        $this->base_path_override = null;
    }

    public function base_path(): string
    {
        return $this->resolve_base_path(false);
    }

    public function share(string $key, mixed $value): void
    {
        $normalized_key = $this->normalize_data_key($key, 'shared data key');

        $this->shared_data[$normalized_key] = $value;
    }

    public function share_many(array $data): void
    {
        foreach ($data as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            $normalized_key = trim($key);
            if (harbor_is_blank($normalized_key)) {
                continue;
            }

            $this->shared_data[$normalized_key] = $value;
        }
    }

    public function shared(?string $key = null, mixed $default = null): mixed
    {
        if (harbor_is_blank($key)) {
            return $this->shared_data;
        }

        return $this->shared_data[$key] ?? $default;
    }

    public function clear_shared(): void
    {
        $this->shared_data = [];
    }

    public function exists(string $template): bool
    {
        try {
            $resolved_template = $this->resolve_template_path($template);

            return is_file($resolved_template);
        } catch (ViewException) {
            return false;
        }
    }

    public function render(string $view, array $data = [], ?string $layout = null, array $layout_data = []): string
    {
        $this->assert_reserved_content_key($layout, $data, $layout_data);

        $view_template_path = $this->resolve_template_path($view);
        $view_context = $this->merge_data($this->shared_data, $data);
        $view_output = $this->render_file($view_template_path, $view_context);

        if (null === $layout) {
            return $view_output;
        }

        $layout_template_path = $this->resolve_template_path($layout);
        $layout_context = $this->merge_data($this->shared_data, $data, $layout_data);
        $layout_context['content'] = $view_output;

        return $this->render_file($layout_template_path, $layout_context);
    }

    public function render_partial(string $partial, array $data = []): string
    {
        $partial_template_path = $this->resolve_template_path($partial);
        $partial_context = $this->merge_data($this->shared_data, $data);

        return $this->render_file($partial_template_path, $partial_context);
    }

    /**
     * @param array<string, mixed> ...$layers
     *
     * @return array<string, mixed>
     */
    private function merge_data(array ...$layers): array
    {
        $merged_data = [];

        foreach ($layers as $layer) {
            foreach ($layer as $key => $value) {
                if (! is_string($key)) {
                    continue;
                }

                $normalized_key = trim($key);
                if (harbor_is_blank($normalized_key)) {
                    continue;
                }

                $merged_data[$normalized_key] = $value;
            }
        }

        return $merged_data;
    }

    private function assert_reserved_content_key(?string $layout, array $data, array $layout_data): void
    {
        if (null === $layout) {
            return;
        }

        if (array_key_exists('content', $data) || array_key_exists('content', $layout_data)) {
            throw new ViewException('View key "content" is reserved when rendering with a layout.');
        }
    }

    private function resolve_template_path(string $template): string
    {
        $normalized_template = $this->normalize_template_name($template);
        $base_path = $this->resolve_base_path(true);

        $template_path = $base_path.'/'.$normalized_template.'.php';

        if (! is_file($template_path)) {
            throw new ViewException(sprintf(
                'View template "%s" not found. Expected file: %s',
                $template,
                $template_path
            ));
        }

        $resolved_template_path = realpath($template_path);
        if (false === $resolved_template_path) {
            throw new ViewException(sprintf('View template "%s" could not be resolved.', $template_path));
        }

        $resolved_base_path = realpath($base_path);
        if (false !== $resolved_base_path) {
            $normalized_base_prefix = rtrim($resolved_base_path, '/\\').DIRECTORY_SEPARATOR;

            if (! str_starts_with($resolved_template_path, $normalized_base_prefix)) {
                throw new ViewException(sprintf('View template "%s" escapes the views directory.', $template));
            }
        }

        return $resolved_template_path;
    }

    private function resolve_base_path(bool $must_exist): string
    {
        $base_path = $this->base_path_override;

        if (! is_string($base_path) || harbor_is_blank($base_path)) {
            $configured_path = $this->resolve_configured_base_path();
            $base_path = is_string($configured_path)
                ? $configured_path
                : $this->resolve_default_base_path();
        }

        $normalized_base_path = $this->normalize_base_path($base_path);

        if (! is_dir($normalized_base_path)) {
            if ($must_exist) {
                throw new ViewException(sprintf('View path "%s" does not exist.', $normalized_base_path));
            }

            return $normalized_base_path;
        }

        $resolved_base_path = realpath($normalized_base_path);
        if (false === $resolved_base_path) {
            if ($must_exist) {
                throw new ViewException(sprintf('View path "%s" could not be resolved.', $normalized_base_path));
            }

            return $normalized_base_path;
        }

        return rtrim($resolved_base_path, '/\\');
    }

    private function resolve_configured_base_path(): ?string
    {
        $configured_path = config_get('view.path');
        if (! is_string($configured_path)) {
            return null;
        }

        $normalized_configured_path = trim($configured_path);
        if (harbor_is_blank($normalized_configured_path)) {
            return null;
        }

        if ($this->is_absolute_path($normalized_configured_path)) {
            return $normalized_configured_path;
        }

        return $this->resolve_project_root().'/'.ltrim($normalized_configured_path, '/\\');
    }

    private function resolve_default_base_path(): string
    {
        return $this->resolve_project_root().'/views';
    }

    private function resolve_project_root(): string
    {
        $script_filename = $_SERVER['SCRIPT_FILENAME'] ?? null;

        if (is_string($script_filename) && ! harbor_is_blank($script_filename)) {
            $script_directory = dirname($script_filename);
            if ('public' === basename($script_directory)) {
                return dirname($script_directory);
            }

            return $script_directory;
        }

        $document_root = $_SERVER['DOCUMENT_ROOT'] ?? null;

        if (is_string($document_root) && ! harbor_is_blank($document_root)) {
            return dirname(rtrim($document_root, '/\\'));
        }

        $current_working_directory = getcwd();

        if (is_string($current_working_directory) && ! harbor_is_blank($current_working_directory)) {
            return rtrim($current_working_directory, '/\\');
        }

        throw new ViewException('Unable to resolve project root for view path discovery.');
    }

    private function normalize_base_path(string $path): string
    {
        $normalized_path = trim($path);

        if (harbor_is_blank($normalized_path)) {
            throw new ViewException('View path cannot be empty.');
        }

        return rtrim($normalized_path, '/\\');
    }

    private function normalize_template_name(string $template): string
    {
        $normalized_template = trim($template);

        if (harbor_is_blank($normalized_template)) {
            throw new ViewException('View template name cannot be empty.');
        }

        if ($this->is_absolute_path($normalized_template)) {
            throw new ViewException('View template name must be relative to the views directory.');
        }

        $normalized_template = str_replace('\\', '/', $normalized_template);

        if (1 === preg_match('#(^|/)\.\.(/|$)#', $normalized_template) || str_contains($normalized_template, "\0")) {
            throw new ViewException(sprintf('View template name "%s" is invalid.', $template));
        }

        $normalized_template = str_replace('.', '/', $normalized_template);
        $normalized_template = preg_replace('#/+#', '/', $normalized_template) ?? $normalized_template;
        $normalized_template = trim($normalized_template, '/');

        if (harbor_is_blank($normalized_template)) {
            throw new ViewException('View template name cannot be empty.');
        }

        $segments = explode('/', $normalized_template);

        foreach ($segments as $segment) {
            if (harbor_is_blank($segment) || '.' === $segment || '..' === $segment || str_contains($segment, "\0")) {
                throw new ViewException(sprintf('View template name "%s" is invalid.', $template));
            }
        }

        return implode('/', $segments);
    }

    private function normalize_data_key(string $key, string $label): string
    {
        $normalized_key = trim($key);

        if (harbor_is_blank($normalized_key)) {
            throw new ViewException(sprintf('%s cannot be empty.', ucfirst($label)));
        }

        return $normalized_key;
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function render_file(string $file_path, array $variables): string
    {
        $buffer_level = ob_get_level();
        ob_start();

        try {
            (static function (string $__file_path, array $__variables): void {
                extract($__variables, EXTR_SKIP);

                require $__file_path;
            })($file_path, $variables);
        } catch (\Throwable $throwable) {
            while (ob_get_level() > $buffer_level) {
                ob_end_clean();
            }

            throw new ViewException(
                sprintf('Failed to render view file "%s": %s', $file_path, $throwable->getMessage()),
                0,
                $throwable
            );
        }

        $output = ob_get_clean();

        return is_string($output) ? $output : '';
    }

    private function is_absolute_path(string $path): bool
    {
        if (str_starts_with($path, '/')) {
            return true;
        }

        return 1 === preg_match('/^[A-Za-z]:[\\\\\/]/', $path);
    }
}
