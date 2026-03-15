<?php

declare(strict_types=1);

namespace Harbor;

/**
 * Enum Helper.
 */
enum Helper: string
{
    case RouteSegments = 'route_segments';
    case RouteQuery = 'route_query';
    case RouteNamed = 'route_named';
    case Route = 'route';
    case Config = 'config';
    case Value = 'value';
    case SupportArray = 'array';
    case Carbon = 'carbon';
    case Pipeline = 'pipeline';
    case Middleware = 'middleware';
    case Request = 'request';
    case Cookie = 'cookie';
    case Session = 'session';
    case Password = 'password';
    case AuthWeb = 'auth_web';
    case AuthApi = 'auth_api';
    case Auth = 'auth';
    case Response = 'response';
    case Database = 'database';
    case DbSqlite = 'db_sqlite';
    case DbMysqlPdo = 'db_mysql_pdo';
    case DbMysqli = 'db_mysqli';
    case Pagination = 'pagination';
    case Validation = 'validation';
    case Performance = 'performance';
    case Units = 'units';
    case Filesystem = 'filesystem';
    case CacheArray = 'cache_array';
    case CacheFile = 'cache_file';
    case CacheApc = 'cache_apc';
    case Cache = 'cache';
    case RateLimiter = 'rate_limiter';
    case Log = 'log';
    case Language = 'language';
    case Translations = 'translations';

    public static function available(): array
    {
        $available_keys = [];

        foreach (self::cases() as $case) {
            $available_keys[] = $case->value;
        }

        foreach (self::aliases() as $alias => $case) {
            $available_keys[] = $alias;
        }

        return array_values(array_unique($available_keys));
    }

    public static function resolve(string $helper): self
    {
        $normalized_helper = strtolower(trim($helper));
        $resolved_case = self::tryFrom($normalized_helper);

        if ($resolved_case instanceof self) {
            return $resolved_case;
        }

        $aliased_case = self::aliases()[$normalized_helper] ?? null;
        if ($aliased_case instanceof self) {
            return $aliased_case;
        }

        throw new \InvalidArgumentException(
            sprintf('Helper "%s" is not registered.', $helper)
        );
    }

    public static function load_many(self|string ...$helpers): void
    {
        foreach ($helpers as $helper) {
            if ($helper instanceof self) {
                $helper->load();
            } else {
                self::resolve($helper)->load();
            }
        }
    }

    public function load(): void
    {
        $helper_paths = $this->paths();

        foreach ($helper_paths as $path) {
            if (! is_file($path)) {
                throw new \RuntimeException(
                    sprintf('Helper file for "%s" not found.', $this->value)
                );
            }

            require_once $path;
        }
    }

    /**
     * @return array<int, string>
     */
    public function paths(): array
    {
        return match ($this) {
            self::RouteSegments => [__DIR__.'/Router/helpers/route_segments.php'],
            self::RouteQuery => [__DIR__.'/Router/helpers/route_query.php'],
            self::RouteNamed => [__DIR__.'/Router/helpers/route_named.php'],
            self::Route => [
                __DIR__.'/Router/helpers/route_segments.php',
                __DIR__.'/Router/helpers/route_query.php',
                __DIR__.'/Router/helpers/route_named.php',
            ],
            self::Config => [__DIR__.'/Config/config.php'],
            self::Value => [__DIR__.'/Support/value.php'],
            self::SupportArray => [__DIR__.'/Support/array.php'],
            self::Carbon => [__DIR__.'/Date/date.php'],
            self::Pipeline => [__DIR__.'/Pipeline/pipeline.php'],
            self::Middleware => [__DIR__.'/Middleware/middleware.php'],
            self::Request => [__DIR__.'/Request/request.php'],
            self::Cookie => [__DIR__.'/Cookie/cookie.php'],
            self::Session => [__DIR__.'/Session/session.php'],
            self::Password => [__DIR__.'/Password/password.php'],
            self::AuthWeb => [__DIR__.'/Auth/auth_web.php'],
            self::AuthApi => [__DIR__.'/Auth/auth_api.php'],
            self::Auth => [
                __DIR__.'/Auth/auth_web.php',
                __DIR__.'/Auth/auth_api.php',
            ],
            self::Response => [__DIR__.'/Response/response.php'],
            self::Database => [__DIR__.'/Database/Connection/db.php'],
            self::DbSqlite => [__DIR__.'/Database/Connection/db_sqlite.php'],
            self::DbMysqlPdo => [__DIR__.'/Database/Connection/db_mysql_pdo.php'],
            self::DbMysqli => [__DIR__.'/Database/Connection/db_mysqli.php'],
            self::Pagination => [__DIR__.'/Pagination/pagination_helpers.php'],
            self::Validation => [__DIR__.'/Validation/validation.php'],
            self::Performance => [__DIR__.'/Performance/performance.php'],
            self::Units => [__DIR__.'/Units/units.php'],
            self::Filesystem => [__DIR__.'/Filesystem/filesystem.php'],
            self::CacheArray => [__DIR__.'/Cache/cache_array.php'],
            self::CacheFile => [__DIR__.'/Cache/cache_file.php'],
            self::CacheApc => [__DIR__.'/Cache/cache_apc.php'],
            self::Cache => [__DIR__.'/Cache/cache.php'],
            self::RateLimiter => [__DIR__.'/RateLimiter/rate_limiter.php'],
            self::Log => [__DIR__.'/Log/log.php'],
            self::Language => [__DIR__.'/Lang/language.php'],
            self::Translations => [__DIR__.'/Lang/translations.php'],
        };
    }

    /**
     * @return array<string, self>
     */
    private static function aliases(): array
    {
        return [
            'db' => self::Database,
            'schema' => self::Database,
            'database_schema' => self::Database,
            'query_builder' => self::Database,
            'database_query_builder' => self::Database,
            'lang' => self::Language,
            'translation' => self::Translations,
        ];
    }
}
