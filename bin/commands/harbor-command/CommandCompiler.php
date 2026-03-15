<?php

declare(strict_types=1);

namespace Harbor\CommandSystem;

require_once __DIR__.'/../../../src/Support/value.php';

use function Harbor\Support\harbor_is_blank;

final class CommandCompiler
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function compile(string $source_path, string $output_path): array
    {
        $definitions = $this->parse($source_path);
        $registry = [];

        foreach ($definitions as $definition) {
            $key = $definition['key'];
            if (! is_string($key)) {
                throw new CommandException('Command definition key must be a string.', 4);
            }

            if (array_key_exists($key, $registry)) {
                throw new CommandException(sprintf('Duplicate command key found: %s', $key), 4);
            }

            $registry[$key] = $definition;
        }

        ksort($registry);

        $directory = dirname($output_path);
        if (! is_dir($directory) && ! mkdir($directory, 0o777, true) && ! is_dir($directory)) {
            throw new CommandException(sprintf('Failed to create directory for registry: %s', $directory), 1);
        }

        $written = file_put_contents($output_path, $this->render_registry_file_content($registry));
        if (false === $written) {
            throw new CommandException(sprintf('Failed to write registry file: %s', $output_path), 1);
        }

        return $registry;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function parse(string $source_path): array
    {
        if (! is_file($source_path)) {
            throw new CommandException(sprintf('Command source file not found: %s', $source_path), 4);
        }

        $content = $this->pre_process_source_file($source_path);
        $line_parts = preg_split('/\R/u', $content);
        $lines = is_array($line_parts) ? $line_parts : [];

        $definitions = [];
        $current_definition = [];
        $inside_command = false;
        $line_number = 0;

        foreach ($lines as $line) {
            ++$line_number;
            $normalized_line = trim($line);

            if (! $inside_command && (harbor_is_blank($normalized_line) || str_starts_with($normalized_line, '#'))) {
                continue;
            }

            if ('<command>' === $normalized_line) {
                if ($inside_command) {
                    throw new CommandException(sprintf('Nested <command> tag is not allowed (line %d).', $line_number), 4);
                }

                $inside_command = true;
                $current_definition = [];

                continue;
            }

            if ('</command>' === $normalized_line) {
                if (! $inside_command) {
                    throw new CommandException(sprintf('Unexpected </command> tag (line %d).', $line_number), 4);
                }

                $definitions[] = $this->normalize_definition($current_definition);
                $inside_command = false;
                $current_definition = [];

                continue;
            }

            if (! $inside_command) {
                throw new CommandException(sprintf('Invalid line outside command block (line %d): %s', $line_number, $normalized_line), 4);
            }

            $parts = explode(':', $normalized_line, 2);
            if (2 !== count($parts)) {
                throw new CommandException(sprintf('Invalid command field syntax (line %d): %s', $line_number, $normalized_line), 4);
            }

            $field = trim($parts[0]);
            $value = trim($parts[1]);

            if (harbor_is_blank($field)) {
                throw new CommandException(sprintf('Invalid command field name (line %d).', $line_number), 4);
            }

            $current_definition[$field] = $this->normalize_scalar_value($value);
        }

        if ($inside_command) {
            throw new CommandException('Command block is not closed. Expected </command>.', 4);
        }

        return $definitions;
    }

    /**
     * @param array<string, mixed> $definition
     *
     * @return array<string, mixed>
     */
    private function normalize_definition(array $definition): array
    {
        $key_value = $definition['key'] ?? null;
        $entry_value = $definition['entry'] ?? null;

        if (! is_string($key_value) || harbor_is_blank($key_value)) {
            throw new CommandException('Missing required command field: key', 4);
        }

        if (! is_string($entry_value) || harbor_is_blank($entry_value)) {
            throw new CommandException(sprintf('Missing required command field: entry (key: %s)', $key_value), 4);
        }

        $enabled_value = $definition['enabled'] ?? true;
        $enabled = $this->normalize_bool_value($enabled_value, 'enabled', $key_value);

        $timeout_seconds = null;
        if (array_key_exists('timeout_seconds', $definition)) {
            $timeout_seconds = $this->normalize_timeout_value($definition['timeout_seconds'], $key_value);
        }

        $normalized = [
            'key' => $key_value,
            'entry' => $entry_value,
            'enabled' => $enabled,
        ];

        $name_value = $definition['name'] ?? null;
        if (is_string($name_value) && ! harbor_is_blank($name_value)) {
            $normalized['name'] = $name_value;
        }

        $description_value = $definition['description'] ?? null;
        if (is_string($description_value) && ! harbor_is_blank($description_value)) {
            $normalized['description'] = $description_value;
        }

        if (is_int($timeout_seconds)) {
            $normalized['timeout_seconds'] = $timeout_seconds;
        }

        $created_at_value = $definition['created_at'] ?? null;
        if (is_string($created_at_value) && ! harbor_is_blank($created_at_value)) {
            $normalized['created_at'] = $created_at_value;
        }

        $updated_at_value = $definition['updated_at'] ?? null;
        if (is_string($updated_at_value) && ! harbor_is_blank($updated_at_value)) {
            $normalized['updated_at'] = $updated_at_value;
        }

        return $normalized;
    }

    private function normalize_scalar_value(string $value): bool|int|string
    {
        $trimmed_value = trim($value);

        if (1 === preg_match('/^"(.*)"$/', $trimmed_value, $double_quotes_match)) {
            return $double_quotes_match[1];
        }

        if (1 === preg_match('/^\'(.*)\'$/', $trimmed_value, $single_quotes_match)) {
            return $single_quotes_match[1];
        }

        $lower = strtolower($trimmed_value);
        if ('true' === $lower) {
            return true;
        }

        if ('false' === $lower) {
            return false;
        }

        if (1 === preg_match('/^-?[0-9]+$/', $trimmed_value)) {
            return (int) $trimmed_value;
        }

        return $trimmed_value;
    }

    private function normalize_bool_value(bool|int|string $value, string $field, string $key): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            if (0 === $value) {
                return false;
            }

            if (1 === $value) {
                return true;
            }
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ['true', '1', 'yes', 'on'], true)) {
                return true;
            }

            if (in_array($normalized, ['false', '0', 'no', 'off'], true)) {
                return false;
            }
        }

        throw new CommandException(sprintf('Invalid %s value for key %s.', $field, $key), 4);
    }

    private function normalize_timeout_value(bool|int|string $value, string $key): int
    {
        if (is_bool($value)) {
            throw new CommandException(sprintf('Invalid timeout_seconds value for key %s.', $key), 4);
        }

        $timeout_value = is_string($value) ? trim($value) : (string) $value;

        if (1 !== preg_match('/^[0-9]+$/', $timeout_value)) {
            throw new CommandException(sprintf('Invalid timeout_seconds value for key %s.', $key), 4);
        }

        $timeout_seconds = (int) $timeout_value;
        if ($timeout_seconds <= 0) {
            throw new CommandException(sprintf('timeout_seconds must be greater than 0 (key: %s).', $key), 4);
        }

        return $timeout_seconds;
    }

    private function pre_process_source_file(string $source_path, array $include_stack = []): string
    {
        $resolved_source_path = realpath($source_path);
        if (false === $resolved_source_path) {
            $resolved_source_path = $source_path;
        }

        if (in_array($resolved_source_path, $include_stack, true)) {
            $full_stack = array_merge($include_stack, [$resolved_source_path]);

            throw new CommandException(sprintf('Circular #include detected: %s', implode(' -> ', $full_stack)), 4);
        }

        $content = file_get_contents($resolved_source_path);
        if (false === $content) {
            throw new CommandException(sprintf('Failed to read command source file: %s', $resolved_source_path), 1);
        }

        $line_parts = preg_split('/\R/u', $content);
        $lines = is_array($line_parts) ? $line_parts : [];
        $stack = array_merge($include_stack, [$resolved_source_path]);
        $processed_lines = [];

        foreach ($lines as $line) {
            $include_path = $this->parse_include_path($line);
            if (null === $include_path) {
                $processed_lines[] = $line;

                continue;
            }

            $resolved_include_path = $include_path;
            if (! $this->is_absolute_path($resolved_include_path)) {
                $resolved_include_path = dirname($resolved_source_path).'/'.$resolved_include_path;
            }

            if (! is_file($resolved_include_path)) {
                throw new CommandException(sprintf('Failed to read included file: %s (from %s)', $resolved_include_path, $resolved_source_path), 4);
            }

            $processed_lines[] = $this->pre_process_source_file($resolved_include_path, $stack);
        }

        return implode(PHP_EOL, $processed_lines);
    }

    private function parse_include_path(string $line): ?string
    {
        if (1 !== preg_match('/^\s*#include\s+["\'](.+)["\']\s*$/', trim($line), $matches)) {
            return null;
        }

        $path = trim($matches[1]);

        return harbor_is_blank($path) ? null : $path;
    }

    private function is_absolute_path(string $path): bool
    {
        return 1 === preg_match('#^([a-zA-Z]:[\\\\/]|/)#', $path);
    }

    /**
     * @param array<string, array<string, mixed>> $registry
     */
    private function render_registry_file_content(array $registry): string
    {
        return "<?php\n\ndeclare(strict_types=1);\n\nreturn ".$this->export_php_value($registry).";\n";
    }

    private function export_php_value(mixed $value, int $indent_level = 0): string
    {
        if (! is_array($value)) {
            return var_export($value, true);
        }

        if (empty($value)) {
            return '[]';
        }

        $is_list = array_is_list($value);
        $current_indentation = str_repeat('    ', $indent_level);
        $item_indentation = str_repeat('    ', $indent_level + 1);
        $lines = ['['];

        foreach ($value as $key => $item) {
            $serialized_item = $this->export_php_value($item, $indent_level + 1);
            if ($is_list) {
                $lines[] = $item_indentation.$serialized_item.',';

                continue;
            }

            $serialized_key = is_int($key) ? (string) $key : var_export($key, true);
            $lines[] = $item_indentation.$serialized_key.' => '.$serialized_item.',';
        }

        $lines[] = $current_indentation.']';

        return implode(PHP_EOL, $lines);
    }
}
