<?php

declare(strict_types=1);

namespace Harbor;

require_once __DIR__.'/Support/value.php';

use function Harbor\Support\harbor_is_null;

final class HelperLoader
{
    public static function load(string ...$helpers): void
    {
        foreach ($helpers as $helper) {
            $helper_paths = self::resolve_helper_paths($helper);

            foreach ($helper_paths as $helper_path) {
                require_once $helper_path;
            }
        }
    }

    public static function available(): array
    {
        return array_keys(self::helper_paths());
    }

    private static function resolve_helper_paths(string $helper): array
    {
        $normalized_helper = strtolower(trim($helper));
        $helper_path = self::helper_paths()[$normalized_helper] ?? null;

        if (harbor_is_null($helper_path)) {
            throw new \InvalidArgumentException(
                sprintf('Helper "%s" is not registered.', $helper)
            );
        }

        $paths = is_array($helper_path) ? $helper_path : [$helper_path];

        foreach ($paths as $path) {
            if (! is_file($path)) {
                throw new \RuntimeException(
                    sprintf('Helper file for "%s" not found.', $helper)
                );
            }
        }

        return $paths;
    }

    private static function helper_paths(): array
    {
        return [
            // Route
            'route_segments' => __DIR__.'/Router/helpers/route_segments.php',
            'route_query' => __DIR__.'/Router/helpers/route_query.php',
            'route_named' => __DIR__.'/Router/helpers/route_named.php',
            'route' => [
                __DIR__.'/Router/helpers/route_segments.php',
                __DIR__.'/Router/helpers/route_query.php',
                __DIR__.'/Router/helpers/route_named.php',
            ],
            // Config
            'config' => __DIR__.'/Config/config.php',
            // Support
            'value' => __DIR__.'/Support/value.php',
            // Request
            'request' => __DIR__.'/Request/request.php',
            // Filesystem
            'filesystem' => __DIR__.'/Filesystem/filesystem.php',
            // Log
            'log' => __DIR__.'/Log/log.php',
        ];
    }
}
