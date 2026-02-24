<?php

declare(strict_types=1);

namespace Harbor\Lang;

require_once __DIR__.'/language.php';
require_once __DIR__.'/../Support/value.php';

use function Harbor\Support\harbor_is_blank;

/**
 * Load translation files grouped by locale and write the result globally.
 *
 * Example:
 * translation_init([
 *     'en' => [__DIR__.'/../../lang/en/messages.php', __DIR__.'/../../lang/en/validation.php'],
 *     'es' => [__DIR__.'/../../lang/es/messages.php'],
 * ]);
 */
function translation_init(array $translations): void
{
    if (empty($translations)) {
        translation_write_global([]);

        return;
    }

    $loaded_translations = [];

    foreach ($translations as $locale => $locale_files) {
        $normalized_locale = translation_normalize_locale($locale);
        $normalized_files = translation_normalize_locale_files($locale_files, $normalized_locale);
        $locale_translations = [];

        foreach ($normalized_files as $translation_file) {
            $loaded_file_translations = translation_load_file($translation_file);
            $locale_translations = array_replace_recursive($locale_translations, $loaded_file_translations);
        }

        $loaded_translations[$normalized_locale] = $locale_translations;
    }

    translation_write_global($loaded_translations);
}

function translations_all(): array
{
    $translations = $GLOBALS['translations'] ?? [];

    return is_array($translations) ? $translations : [];
}

function translation_locale(?string $locale = null): string
{
    if (is_string($locale)) {
        $normalized_locale = trim($locale);

        if (! harbor_is_blank($normalized_locale)) {
            return $normalized_locale;
        }
    }

    return lang_get();
}

function translation_exists(string $key, ?string $locale = null): bool
{
    if (harbor_is_blank($key)) {
        return false;
    }

    return translation_array_has(translation_locale_translations($locale), $key);
}

function translation_get(string $key, array $replace = [], ?string $locale = null): string
{
    if (harbor_is_blank($key)) {
        return $key;
    }

    $translation_value = translation_array_get(translation_locale_translations($locale), $key, $key);
    $translation = translation_value_to_string($translation_value, $key);

    return translation_apply_replacements($translation, $replace);
}

function t(string $key, array $replace = [], ?string $locale = null): string
{
    return translation_get($key, $replace, $locale);
}

function translation_locale_translations(?string $locale = null): array
{
    $translations = translations_all();
    $resolved_locale = translation_locale($locale);
    $locale_translations = $translations[$resolved_locale] ?? [];

    return is_array($locale_translations) ? $locale_translations : [];
}

function translation_normalize_locale(mixed $locale): string
{
    if (! is_string($locale)) {
        throw new \InvalidArgumentException('Translation locale key must be a non-empty string.');
    }

    $normalized_locale = trim($locale);
    if (harbor_is_blank($normalized_locale)) {
        throw new \InvalidArgumentException('Translation locale key must be a non-empty string.');
    }

    return $normalized_locale;
}

function translation_normalize_locale_files(mixed $locale_files, string $locale): array
{
    if (is_string($locale_files)) {
        return [translation_normalize_file_path($locale_files, $locale)];
    }

    if (! is_array($locale_files)) {
        throw new \InvalidArgumentException(
            sprintf('Translation files for locale "%s" must be a string or array of file paths.', $locale)
        );
    }

    if (empty($locale_files)) {
        return [];
    }

    $normalized_files = [];

    foreach ($locale_files as $locale_file) {
        $normalized_files[] = translation_normalize_file_path($locale_file, $locale);
    }

    return $normalized_files;
}

function translation_normalize_file_path(mixed $file_path, string $locale): string
{
    if (! is_string($file_path)) {
        throw new \InvalidArgumentException(
            sprintf('Translation file path for locale "%s" must be a non-empty string.', $locale)
        );
    }

    $normalized_file_path = trim($file_path);
    if (harbor_is_blank($normalized_file_path)) {
        throw new \InvalidArgumentException(
            sprintf('Translation file path for locale "%s" must be a non-empty string.', $locale)
        );
    }

    return $normalized_file_path;
}

function translation_load_file(string $translation_file): array
{
    if (! is_file($translation_file)) {
        throw new \RuntimeException(sprintf('Translation file not found: %s', $translation_file));
    }

    $loaded_translations = require $translation_file;
    if (! is_array($loaded_translations)) {
        throw new \RuntimeException(
            sprintf('Translation file "%s" must return an array.', $translation_file)
        );
    }

    return $loaded_translations;
}

function translation_write_global(array $translations): void
{
    $GLOBALS['translations'] = $translations;
}

function translation_array_get(array $array, string $key, mixed $default = null): mixed
{
    if (array_key_exists($key, $array)) {
        return $array[$key];
    }

    $segments = explode('.', $key);
    $current = $array;

    foreach ($segments as $segment) {
        if (! is_array($current) || ! array_key_exists($segment, $current)) {
            return $default;
        }

        $current = $current[$segment];
    }

    return $current;
}

function translation_array_has(array $array, string $key): bool
{
    if (array_key_exists($key, $array)) {
        return true;
    }

    $segments = explode('.', $key);
    $current = $array;

    foreach ($segments as $segment) {
        if (! is_array($current) || ! array_key_exists($segment, $current)) {
            return false;
        }

        $current = $current[$segment];
    }

    return true;
}

function translation_value_to_string(mixed $value, string $default): string
{
    if (is_string($value)) {
        return $value;
    }

    if (is_scalar($value)) {
        return (string) $value;
    }

    if (is_object($value) && method_exists($value, '__toString')) {
        return (string) $value;
    }

    return $default;
}

function translation_apply_replacements(string $translation, array $replace): string
{
    foreach ($replace as $replace_key => $replace_value) {
        $key = (string) $replace_key;
        $value = translation_replacement_value_to_string($replace_value);
        $placeholder = ':'.$key;
        $uppercase_placeholder = ':'.strtoupper($key);
        $capitalized_placeholder = ':'.ucfirst($key);

        $translation = str_replace($placeholder, $value, $translation);
        $translation = str_replace($capitalized_placeholder, ucfirst($value), $translation);
        $translation = str_replace($uppercase_placeholder, strtoupper($value), $translation);
    }

    return $translation;
}

function translation_replacement_value_to_string(mixed $value): string
{
    if (is_string($value)) {
        return $value;
    }

    if (is_scalar($value)) {
        return (string) $value;
    }

    if (is_object($value) && method_exists($value, '__toString')) {
        return (string) $value;
    }

    if (null === $value) {
        return '';
    }

    try {
        $encoded = json_encode($value, JSON_THROW_ON_ERROR);
    } catch (\JsonException) {
        return '';
    }

    return is_string($encoded) ? $encoded : '';
}

if (! isset($GLOBALS['translations']) || ! is_array($GLOBALS['translations'])) {
    $GLOBALS['translations'] = [];
}
