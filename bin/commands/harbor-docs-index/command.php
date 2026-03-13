#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * @param array<int, string> $arguments
 */
function harbor_docs_index_run(array $arguments): int
{
    if (in_array('--help', $arguments, true) || in_array('-h', $arguments, true)) {
        docs_index_print_usage();

        return 0;
    }

    $documentation_root_path = realpath(__DIR__.'/../../../documentation');
    if (false === $documentation_root_path || ! is_dir($documentation_root_path)) {
        fwrite(STDERR, sprintf('Documentation directory not found: %s%s', __DIR__.'/../../../documentation', PHP_EOL));

        return 1;
    }

    $router_path = $documentation_root_path.'/.router';
    if (! is_file($router_path)) {
        fwrite(STDERR, sprintf('Documentation router file not found: %s%s', $router_path, PHP_EOL));

        return 1;
    }

    $public_path = $documentation_root_path.'/public';
    if (! is_dir($public_path)) {
        fwrite(STDERR, sprintf('Documentation public directory not found: %s%s', $public_path, PHP_EOL));

        return 1;
    }

    try {
        $output_path = docs_index_output_path($arguments, $public_path.'/assets/search-index.json');
        $search_index = docs_index_build($router_path, $public_path);
    } catch (Throwable $throwable) {
        fwrite(STDERR, $throwable->getMessage().PHP_EOL);

        return 1;
    }

    $output_directory = dirname($output_path);
    if (! is_dir($output_directory) && ! mkdir($output_directory, 0o777, true) && ! is_dir($output_directory)) {
        fwrite(STDERR, sprintf('Failed to create output directory: %s%s', $output_directory, PHP_EOL));

        return 1;
    }

    $encoded_index = json_encode($search_index, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (! is_string($encoded_index)) {
        fwrite(STDERR, 'Failed to encode documentation search index JSON.'.PHP_EOL);

        return 1;
    }

    if (false === file_put_contents($output_path, $encoded_index.PHP_EOL)) {
        fwrite(STDERR, sprintf('Failed to write documentation search index: %s%s', $output_path, PHP_EOL));

        return 1;
    }

    fwrite(STDOUT, sprintf('Documentation search index generated: %s%s', $output_path, PHP_EOL));
    fwrite(STDOUT, sprintf('Indexed pages: %d%s', count($search_index['items']), PHP_EOL));

    return 0;
}

function docs_index_print_usage(): void
{
    fwrite(STDOUT, 'Usage: harbor-docs-index [--output=PATH]'.PHP_EOL);
    fwrite(STDOUT, PHP_EOL);
    fwrite(STDOUT, 'Builds documentation search index JSON from documentation/.router.'.PHP_EOL);
    fwrite(STDOUT, 'Default output: documentation/public/assets/search-index.json'.PHP_EOL);
}

function docs_index_output_path(array $arguments, string $default_output_path): string
{
    foreach ($arguments as $argument) {
        if (! is_string($argument) || ! str_starts_with($argument, '--output=')) {
            continue;
        }

        $requested_output_path = trim((string) substr($argument, strlen('--output=')));
        if ('' === $requested_output_path) {
            throw new InvalidArgumentException('Output path cannot be empty.');
        }

        return $requested_output_path;
    }

    return $default_output_path;
}

/**
 * @return array{generated_at: string, source: string, items: array<int, array{path: string, title: string, description: string, headings: array<int, string>, content: string}>}
 */
function docs_index_build(string $router_path, string $public_path): array
{
    $router_content = file_get_contents($router_path);
    if (! is_string($router_content) || '' === trim($router_content)) {
        throw new RuntimeException(sprintf('Documentation router file is empty: %s', $router_path));
    }

    $routes = docs_index_routes($router_content);
    $items = [];

    foreach ($routes as $route) {
        $entry_path = $public_path.'/'.ltrim($route['entry'], '/');
        if (! is_file($entry_path)) {
            throw new RuntimeException(sprintf('Documentation page entry not found for route "%s": %s', $route['path'], $entry_path));
        }

        $page_content = file_get_contents($entry_path);
        if (! is_string($page_content)) {
            throw new RuntimeException(sprintf('Failed to read documentation page entry: %s', $entry_path));
        }

        $items[] = [
            'path' => $route['path'],
            'title' => docs_index_page_title($page_content, $route['path']),
            'description' => docs_index_page_description($page_content),
            'headings' => docs_index_page_headings($page_content),
            'content' => docs_index_page_content($page_content),
        ];
    }

    usort(
        $items,
        static fn (array $left, array $right): int => strcmp($left['path'], $right['path'])
    );

    return [
        'generated_at' => gmdate('c'),
        'source' => 'documentation/.router',
        'items' => $items,
    ];
}

/**
 * @return array<int, array{path: string, entry: string}>
 */
function docs_index_routes(string $router_content): array
{
    preg_match_all('/<route>\s*(.*?)\s*<\/route>/is', $router_content, $route_matches);

    $routes = [];
    $seen_paths = [];

    foreach ($route_matches[1] ?? [] as $raw_route_block) {
        if (! is_string($raw_route_block)) {
            continue;
        }

        $path = docs_index_route_field($raw_route_block, 'path');
        $method = strtoupper(docs_index_route_field($raw_route_block, 'method'));
        $entry = docs_index_route_field($raw_route_block, 'entry');

        if ('' === $path || '' === $entry || 'GET' !== $method) {
            continue;
        }

        if (array_key_exists($path, $seen_paths)) {
            continue;
        }

        $routes[] = [
            'path' => $path,
            'entry' => $entry,
        ];
        $seen_paths[$path] = true;
    }

    if (empty($routes)) {
        throw new RuntimeException('No GET documentation routes found in documentation/.router.');
    }

    return $routes;
}

function docs_index_route_field(string $raw_route_block, string $field): string
{
    $pattern = sprintf('/^\s*%s:\s*(.+)\s*$/mi', preg_quote($field, '/'));
    if (1 !== preg_match($pattern, $raw_route_block, $field_match)) {
        return '';
    }

    $field_value = trim((string) ($field_match[1] ?? ''));

    return docs_index_normalize_text($field_value);
}

function docs_index_page_title(string $page_content, string $fallback_path): string
{
    $html_content = docs_index_strip_php($page_content);

    if (1 === preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $html_content, $h1_match)) {
        $h1_title = docs_index_html_to_text((string) ($h1_match[1] ?? ''));

        if ('' !== $h1_title) {
            return $h1_title;
        }
    }

    if (1 === preg_match('/\$page_title\s*=\s*\'([^\']+)\'\s*;/i', $page_content, $page_title_match)) {
        $page_title = docs_index_normalize_text((string) ($page_title_match[1] ?? ''));

        if ('' !== $page_title) {
            return $page_title;
        }
    }

    return $fallback_path;
}

function docs_index_page_description(string $page_content): string
{
    $html_content = docs_index_strip_php($page_content);

    if (1 === preg_match('/<section[^>]*class="[^"]*hero[^"]*"[^>]*>.*?<p[^>]*>(.*?)<\/p>/is', $html_content, $hero_paragraph_match)) {
        return docs_index_html_to_text((string) ($hero_paragraph_match[1] ?? ''));
    }

    if (1 === preg_match('/<p[^>]*>(.*?)<\/p>/is', $html_content, $paragraph_match)) {
        return docs_index_html_to_text((string) ($paragraph_match[1] ?? ''));
    }

    return '';
}

/**
 * @return array<int, string>
 */
function docs_index_page_headings(string $page_content): array
{
    $html_content = docs_index_strip_php($page_content);
    preg_match_all('/<(h2|h3)[^>]*>(.*?)<\/\1>/is', $html_content, $heading_matches);

    $headings = [];
    foreach ($heading_matches[2] ?? [] as $heading_match) {
        if (! is_string($heading_match)) {
            continue;
        }

        $normalized_heading = docs_index_html_to_text($heading_match);
        if ('' !== $normalized_heading) {
            $headings[] = $normalized_heading;
        }
    }

    return array_values(array_unique($headings));
}

function docs_index_page_content(string $page_content): string
{
    $html_content = docs_index_strip_php($page_content);
    $html_content = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html_content) ?? $html_content;
    $html_content = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $html_content) ?? $html_content;

    return docs_index_html_to_text($html_content);
}

function docs_index_strip_php(string $value): string
{
    return preg_replace('/<\?(?:php|=)?[\s\S]*?\?>/i', ' ', $value) ?? $value;
}

function docs_index_html_to_text(string $value): string
{
    $decoded_html = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $stripped_text = strip_tags($decoded_html);

    return docs_index_normalize_text($stripped_text);
}

function docs_index_normalize_text(string $value): string
{
    $trimmed_value = trim($value);
    if ('' === $trimmed_value) {
        return '';
    }

    $normalized_spaces = preg_replace('/\s+/u', ' ', $trimmed_value);

    return is_string($normalized_spaces) ? trim($normalized_spaces) : $trimmed_value;
}

if ('cli' === PHP_SAPI) {
    $script_file = $_SERVER['SCRIPT_FILENAME'] ?? null;
    $resolved_script_file = is_string($script_file) ? realpath($script_file) : false;

    if (is_string($resolved_script_file) && __FILE__ === $resolved_script_file) {
        exit(harbor_docs_index_run($_SERVER['argv'] ?? []));
    }
}
