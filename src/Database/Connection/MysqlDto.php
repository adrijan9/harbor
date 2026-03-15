<?php

declare(strict_types=1);

namespace Harbor\Database;

require_once __DIR__.'/../../Config/config.php';

require_once __DIR__.'/../../Support/value.php';

require_once __DIR__.'/../DatabaseConnectionDtoInterface.php';

use function Harbor\Config\config_array_get;
use function Harbor\Config\config_get;
use function Harbor\Config\config_resolve;
use function Harbor\Support\harbor_is_blank;
use function Harbor\Support\harbor_is_null;

/**
 * Class MysqlDto.
 */
final readonly class MysqlDto implements DatabaseConnectionDtoInterface
{
    public function __construct(
        public string $host,
        public string $user,
        public string $password,
        public string $database,
        public int $port = 3306,
        public string $charset = 'utf8mb4',
        public array $options = []
    ) {}

    public static function make(
        string $host,
        string $user,
        string $password,
        string $database,
        int $port = 3306,
        string $charset = 'utf8mb4',
        array $options = []
    ): self {
        $normalized_host = trim($host);
        if (harbor_is_blank($normalized_host)) {
            throw new \InvalidArgumentException('MySQL host cannot be empty.');
        }

        $normalized_user = trim($user);
        if (harbor_is_blank($normalized_user)) {
            throw new \InvalidArgumentException('MySQL user cannot be empty.');
        }

        $normalized_database = trim($database);
        if (harbor_is_blank($normalized_database)) {
            throw new \InvalidArgumentException('MySQL database cannot be empty.');
        }

        $normalized_charset = trim($charset);
        if (harbor_is_blank($normalized_charset)) {
            throw new \InvalidArgumentException('MySQL charset cannot be empty.');
        }

        return new self(
            host: $normalized_host,
            user: $normalized_user,
            password: $password,
            database: $normalized_database,
            port: $port,
            charset: $normalized_charset,
            options: $options
        );
    }

    public static function from_config(array $config = []): static
    {
        $host = self::resolve_config_value(
            $config,
            ['mysql.host', 'host'],
            ['db.mysql.host', 'database.mysql.host'],
            '127.0.0.1'
        );

        $user = self::resolve_config_value(
            $config,
            ['mysql.user', 'mysql.username', 'user', 'username'],
            ['db.mysql.user', 'database.mysql.user', 'db.mysql.username', 'database.mysql.username'],
            'root'
        );

        $password = self::resolve_config_value(
            $config,
            ['mysql.password', 'mysql.pass', 'password', 'pass'],
            ['db.mysql.password', 'database.mysql.password', 'db.mysql.pass', 'database.mysql.pass'],
            ''
        );

        $database = self::resolve_config_value(
            $config,
            ['mysql.database', 'mysql.db', 'database', 'db'],
            ['db.mysql.database', 'database.mysql.database', 'db.mysql.db', 'database.mysql.db'],
            ''
        );

        $port = self::resolve_config_value(
            $config,
            ['mysql.port', 'port'],
            ['db.mysql.port', 'database.mysql.port'],
            3306
        );

        $charset = self::resolve_config_value(
            $config,
            ['mysql.charset', 'charset'],
            ['db.mysql.charset', 'database.mysql.charset'],
            'utf8mb4'
        );

        $configured_options = self::resolve_config_value(
            $config,
            ['mysql.options', 'options'],
            ['db.mysql.options', 'database.mysql.options'],
            []
        );

        $options = is_array($configured_options) ? $configured_options : [];

        return self::make(
            (string) $host,
            (string) $user,
            (string) $password,
            (string) $database,
            (int) $port,
            (string) $charset,
            $options
        );
    }

    public function to_array(): array
    {
        return [
            'host' => $this->host,
            'user' => $this->user,
            'password' => $this->password,
            'database' => $this->database,
            'port' => $this->port,
            'charset' => $this->charset,
            'options' => $this->options,
        ];
    }

    private static function resolve_config_value(
        array $config,
        array $config_keys,
        array $runtime_keys,
        mixed $default = null
    ): mixed {
        foreach ($config_keys as $config_key) {
            $value = config_array_get($config, $config_key);
            if (! harbor_is_null($value) && ! (is_string($value) && harbor_is_blank($value))) {
                return $value;
            }
        }

        $runtime_key_count = count($runtime_keys);
        if (2 === $runtime_key_count) {
            $resolved = config_resolve($runtime_keys[0], $runtime_keys[1], $default);
            if (! harbor_is_null($resolved) && ! (is_string($resolved) && harbor_is_blank($resolved))) {
                return $resolved;
            }

            return $default;
        }

        foreach ($runtime_keys as $runtime_key) {
            $value = config_get($runtime_key);
            if (! harbor_is_null($value) && ! (is_string($value) && harbor_is_blank($value))) {
                return $value;
            }
        }

        return $default;
    }
}
