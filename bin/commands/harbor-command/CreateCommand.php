<?php

declare(strict_types=1);

namespace Harbor\CommandSystem;

require_once __DIR__.'/../../../src/Support/value.php';

use function Harbor\Support\harbor_is_blank;

final class CreateCommand extends BaseCommand
{
    public function __construct(bool $debug_mode, private readonly CommandCompiler $compiler)
    {
        parent::__construct($debug_mode);
    }

    /**
     * @param array{
     *     entry: ?string,
     *     name: ?string,
     *     description: ?string,
     *     timeout_seconds: ?int,
     *     enabled: bool
     * } $options
     */
    public function execute(string $key, array $options, string $working_directory): int
    {
        $this->assert_site_selected($working_directory);
        $this->assert_valid_key($key);

        $source_path = $this->source_path($working_directory);
        $commands_directory_path = $this->commands_directory_path($working_directory);
        $registry_path = $this->registry_path($working_directory);

        $this->debug(sprintf('Using source path: %s', $source_path));
        $this->debug(sprintf('Using commands directory path: %s', $commands_directory_path));

        $this->initialize_source_file($source_path);
        $this->ensure_directory_exists($commands_directory_path);

        $definitions = $this->compiler->parse($source_path);
        foreach ($definitions as $definition) {
            $definition_key = $definition['key'] ?? null;
            if (is_string($definition_key) && $definition_key === $key) {
                throw new CommandException(sprintf('Command key already exists: %s', $key), 4);
            }
        }

        $entry_path = $options['entry'];
        if (! is_string($entry_path) || harbor_is_blank($entry_path)) {
            $entry_path = $this->default_entry_path($key);
        }

        $definition = [
            'key' => $key,
            'entry' => $entry_path,
            'enabled' => $options['enabled'],
        ];

        if (is_string($options['name']) && ! harbor_is_blank($options['name'])) {
            $definition['name'] = $options['name'];
        }

        if (is_string($options['description']) && ! harbor_is_blank($options['description'])) {
            $definition['description'] = $options['description'];
        }

        if (is_int($options['timeout_seconds']) && $options['timeout_seconds'] > 0) {
            $definition['timeout_seconds'] = $options['timeout_seconds'];
        }

        $this->append_definition($source_path, $definition);
        $this->create_entry_stub_if_missing($working_directory, $entry_path, $key);
        $this->compiler->compile($source_path, $registry_path);

        $this->info(sprintf('Command created: %s', $key));
        $this->info(sprintf('Entry: %s', $entry_path));
        $this->info(sprintf('Run: ./bin/harbor-command run %s', $key));

        return 0;
    }

    private function initialize_source_file(string $source_path): void
    {
        if (is_file($source_path)) {
            return;
        }

        if (file_exists($source_path)) {
            throw new CommandException(sprintf('Expected .commands file path but found directory: %s', $source_path), 4);
        }

        $this->write_file($source_path, "# Harbor command definitions\n");
    }

    private function default_entry_path(string $key): string
    {
        $normalized_key = str_replace(':', '_', $key);

        return 'commands/'.$normalized_key.'.php';
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function append_definition(string $source_path, array $definition): void
    {
        $existing_content = $this->read_file($source_path);
        $trimmed_content = rtrim($existing_content);
        $definition_block = $this->render_definition_block($definition);

        $new_content = $definition_block;
        if (! harbor_is_blank($trimmed_content)) {
            $new_content = $trimmed_content.PHP_EOL.PHP_EOL.$definition_block;
        }

        $this->write_file($source_path, $new_content.PHP_EOL);
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function render_definition_block(array $definition): string
    {
        $lines = ['<command>'];

        $ordered_fields = ['key', 'entry', 'name', 'description', 'timeout_seconds', 'enabled'];

        foreach ($ordered_fields as $field) {
            if (! array_key_exists($field, $definition)) {
                continue;
            }

            $value = $definition[$field];
            if (is_bool($value)) {
                $rendered_value = $value ? 'true' : 'false';
            } elseif (is_int($value)) {
                $rendered_value = (string) $value;
            } elseif (is_string($value)) {
                $rendered_value = $this->render_string_value($value);
            } else {
                continue;
            }

            $lines[] = sprintf('    %s: %s', $field, $rendered_value);
        }

        $lines[] = '</command>';

        return implode(PHP_EOL, $lines);
    }

    private function render_string_value(string $value): string
    {
        if (harbor_is_blank($value)) {
            return '""';
        }

        if (1 === preg_match('/\s/', $value) || str_contains($value, '"')) {
            return '"'.str_replace('"', '\"', $value).'"';
        }

        return $value;
    }

    private function create_entry_stub_if_missing(string $working_directory, string $entry_path, string $key): void
    {
        $resolved_entry_path = $this->resolve_entry_path($entry_path, $working_directory);

        if (is_file($resolved_entry_path)) {
            $this->debug(sprintf('Entry script already exists: %s', $resolved_entry_path));

            return;
        }

        $entry_directory = dirname($resolved_entry_path);
        $this->ensure_directory_exists($entry_directory);

        $template = <<<PHP
            <?php

            declare(strict_types=1);

            \$arguments = \$argv ?? [];
            array_shift(\$arguments);

            // Implement command logic for key: {$key}
            fwrite(STDOUT, sprintf("Command \\"{$key}\\" executed.%s", PHP_EOL));
            PHP;

        $this->write_file($resolved_entry_path, $template.PHP_EOL);
    }
}
