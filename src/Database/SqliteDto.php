<?php

declare(strict_types=1);

namespace Harbor\Database;

require_once __DIR__.'/../Config/config.php';

require_once __DIR__.'/../Support/value.php';

require_once __DIR__.'/DatabaseConnectionDtoInterface.php';

use function Harbor\Config\config_array_get;
use function Harbor\Config\config_get;
use function Harbor\Config\config_resolve;
use function Harbor\Support\harbor_is_blank;
use function Harbor\Support\harbor_is_null;

/**
 * Class SqliteDto.
 */
final readonly class SqliteDto implements DatabaseConnectionDtoInterface
{
    public function __construct(
        public string $database_path,
        public array $options = []
    ) {}

    public static function make(string $database_path, array $options = []): self
    {
        $normalized_database_path = trim($database_path);
        if (harbor_is_blank($normalized_database_path)) {
            throw new \InvalidArgumentException('SQLite database path cannot be empty.');
        }

        return new self($normalized_database_path, $options);
    }

    public static function from_config(array $config = []): static
    {
        $configured_database_path = self::resolve_config_value(
            $config,
            ['sqlite.path', 'path', 'database'],
            ['db.sqlite.path', 'database.sqlite.path']
        );

        if (! is_string($configured_database_path) || harbor_is_blank($configured_database_path)) {
            throw new \RuntimeException('SQLite database path not configured. Set "db.sqlite.path" or provide it in SqliteDto::from_config() config.');
        }

        $configured_options = self::resolve_config_value(
            $config,
            ['sqlite.options', 'options'],
            ['db.sqlite.options', 'database.sqlite.options']
        );

        $options = is_array($configured_options) ? $configured_options : [];

        return self::make($configured_database_path, $options);
    }

    public function to_array(): array
    {
        return [
            'database_path' => $this->database_path,
            'options' => $this->options,
        ];
    }

    private static function resolve_config_value(array $config, array $config_keys, array $runtime_keys): mixed
    {
        foreach ($config_keys as $config_key) {
            $value = config_array_get($config, $config_key);
            if (! harbor_is_null($value)) {
                return $value;
            }
        }

        $runtime_key_count = count($runtime_keys);
        if (2 === $runtime_key_count) {
            return config_resolve($runtime_keys[0], $runtime_keys[1]);
        }

        foreach ($runtime_keys as $runtime_key) {
            $value = config_get($runtime_key);
            if (! harbor_is_null($value)) {
                return $value;
            }
        }

        return null;
    }
}
