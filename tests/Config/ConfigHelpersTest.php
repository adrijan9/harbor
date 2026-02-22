<?php

declare(strict_types=1);

namespace Harbor\Tests\Config;

use Harbor\HelperLoader;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;

use function Harbor\Config\config;
use function Harbor\Config\config_all;
use function Harbor\Config\config_arr;
use function Harbor\Config\config_bool;
use function Harbor\Config\config_count;
use function Harbor\Config\config_exists;
use function Harbor\Config\config_float;
use function Harbor\Config\config_get;
use function Harbor\Config\config_init;
use function Harbor\Config\config_int;
use function Harbor\Config\config_json;
use function Harbor\Config\config_obj;
use function Harbor\Config\config_str;

final class ConfigHelpersTest extends TestCase
{
    private array $original_env = [];

    /** @var array<int, string> */
    private array $temporary_files = [];

    #[Before]
    protected function load_helper_and_capture_env(): void
    {
        $this->original_env = is_array($_ENV) ? $_ENV : [];
        HelperLoader::load('config');
    }

    public function test_config_init_merges_multiple_files_into_env(): void
    {
        $base_file = $this->create_temp_config_file([
            'app_name' => 'Harbor Site',
            'db' => ['host' => 'localhost', 'port' => '3306'],
            'ratio' => '1.5',
            'feature' => 'false',
        ]);

        $override_file = $this->create_temp_config_file([
            'db' => ['port' => 3307],
            'feature' => 'true',
            'tags' => 'php,harbor',
            'json_payload' => '{"id":15,"enabled":true}',
            'profile' => ['team' => 'core'],
        ]);

        config_init($base_file, $override_file);

        self::assertSame('Harbor Site', config('app_name'));
        self::assertSame('localhost', config_get('db.host'));
        self::assertSame(3307, config_int('db.port'));
        self::assertSame(1.5, config_float('ratio'));
        self::assertTrue(config_bool('feature'));
        self::assertSame(['php', 'harbor'], config_arr('tags'));
        self::assertSame(['id' => 15, 'enabled' => true], config_json('json_payload'));
        self::assertTrue(config_exists('profile.team'));
        self::assertGreaterThanOrEqual(5, config_count());
        self::assertSame($_ENV, config_all());
    }

    public function test_config_helpers_return_defaults_for_missing_or_invalid_values(): void
    {
        $_ENV = [
            'app_name' => 'Harbor',
            'enabled' => 'not-bool',
            'count' => 'not-numeric',
            'meta' => '{"team":"runtime"}',
        ];

        self::assertSame('fallback', config_str('missing', 'fallback'));
        self::assertSame(9, config_int('count', 9));
        self::assertFalse(config_bool('enabled', false));
        self::assertSame(['x' => 1], config_json('missing_json', ['x' => 1]));

        $meta = config_obj('meta');
        self::assertInstanceOf(\stdClass::class, $meta);
        self::assertSame('runtime', $meta->team);
    }

    public function test_config_init_throws_for_missing_file(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Config file not found:');

        config_init('/path/does/not/exist/config.php');
    }

    public function test_config_init_throws_when_file_does_not_return_array(): void
    {
        $invalid_file = $this->create_temp_php_file('<?php return "invalid";');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must return an array');

        config_init($invalid_file);
    }

    #[After]
    protected function restore_env_and_cleanup_files(): void
    {
        $_ENV = $this->original_env;
        $GLOBALS['_ENV'] = $_ENV;

        foreach ($this->temporary_files as $file_path) {
            if (is_file($file_path)) {
                unlink($file_path);
            }
        }

        $this->temporary_files = [];
    }

    private function create_temp_config_file(array $configuration): string
    {
        $content = '<?php return '.var_export($configuration, true).';';

        return $this->create_temp_php_file($content);
    }

    private function create_temp_php_file(string $content): string
    {
        $file_path = tempnam(sys_get_temp_dir(), 'harbor_cfg_');
        if (false === $file_path) {
            throw new \RuntimeException('Failed to create temporary file.');
        }

        file_put_contents($file_path, $content);
        $this->temporary_files[] = $file_path;

        return $file_path;
    }
}
