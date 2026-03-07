<?php

declare(strict_types=1);

namespace Harbor\Tests\Bin;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2).'/bin/harbor-seed';

require_once dirname(__DIR__, 2).'/src/Support/value.php';

use function Harbor\Support\harbor_is_blank;

/**
 * Class HarborSeedTest.
 */
final class HarborSeedTest extends TestCase
{
    private string $workspace_path = '';
    private string $original_working_directory = '';

    public function test_new_command_creates_timestamped_seeder_file_in_configured_directory(): void
    {
        $this->prepare_workspace();

        $exit_code = $this->run_seed_command(['harbor-seed', 'new', 'seed_permissions']);
        self::assertSame(0, $exit_code);

        $seeder_files = glob($this->workspace_path.'/database/seeders/*.php');
        self::assertIsArray($seeder_files);
        self::assertCount(1, $seeder_files);

        $file_name = basename($seeder_files[0]);
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2}_seed_permissions\.php$/', $file_name);
    }

    public function test_seed_rollback_and_rerun_workflow_tracks_seed_batches(): void
    {
        $this->prepare_workspace();
        $this->write_counter_seeder('2026-03-07-22-00-00_seed_permissions.php');

        self::assertSame(0, $this->run_seed_command(['harbor-seed']));
        self::assertSame('1', $this->counter_value('seed-counter.txt'));

        $rows_after_first_seed = $this->tracking_rows('seeders');
        self::assertCount(1, $rows_after_first_seed);
        self::assertSame('2026-03-07-22-00-00_seed_permissions.php', $rows_after_first_seed[0]['name']);
        self::assertSame(1, (int) $rows_after_first_seed[0]['batch']);

        self::assertSame(0, $this->run_seed_command(['harbor-seed', 'rollback']));
        self::assertSame('0', $this->counter_value('seed-counter.txt'));
        self::assertSame([], $this->tracking_rows('seeders'));

        self::assertSame(0, $this->run_seed_command(['harbor-seed']));
        self::assertSame('1', $this->counter_value('seed-counter.txt'));

        $rows_after_rerun = $this->tracking_rows('seeders');
        self::assertCount(1, $rows_after_rerun);
    }

    public function test_command_fails_when_current_directory_is_not_a_site(): void
    {
        $this->prepare_workspace();
        unlink($this->workspace_path.'/.router');

        self::assertSame(1, $this->run_seed_command(['harbor-seed']));
    }

    #[After]
    protected function cleanup_workspace(): void
    {
        if ('' !== $this->original_working_directory && is_dir($this->original_working_directory)) {
            chdir($this->original_working_directory);
        }

        if (harbor_is_blank($this->workspace_path) || ! is_dir($this->workspace_path)) {
            return;
        }

        $this->delete_directory_tree($this->workspace_path);
    }

    private function prepare_workspace(): void
    {
        $working_directory = getcwd();
        $this->original_working_directory = false === $working_directory ? '' : $working_directory;

        $workspace_path = sys_get_temp_dir().'/harbor_seed_'.bin2hex(random_bytes(8));
        if (! mkdir($workspace_path, 0o777, true) && ! is_dir($workspace_path)) {
            throw new \RuntimeException(sprintf('Failed to create test workspace "%s".', $workspace_path));
        }

        $resolved_workspace_path = realpath($workspace_path);
        if (false === $resolved_workspace_path) {
            throw new \RuntimeException(sprintf('Failed to resolve test workspace "%s".', $workspace_path));
        }

        $this->workspace_path = $resolved_workspace_path;

        mkdir($this->workspace_path.'/config', 0o777, true);
        mkdir($this->workspace_path.'/database/migrations', 0o777, true);
        mkdir($this->workspace_path.'/database/seeders', 0o777, true);
        mkdir($this->workspace_path.'/storage', 0o777, true);
        mkdir($this->workspace_path.'/vendor', 0o777, true);
        file_put_contents($this->workspace_path.'/.router', "# site\n");
        file_put_contents($this->workspace_path.'/vendor/autoload.php', "<?php\n");

        file_put_contents(
            $this->workspace_path.'/config/migration.php',
            <<<'CONFIG'
                <?php

                declare(strict_types=1);

                use Harbor\Database\DbDriver;

                return [
                    'driver' => DbDriver::SQLITE->value,
                    'sqlite' => [
                        'path' => __DIR__.'/../storage/tracker.sqlite',
                    ],
                    'mysql' => [
                        'host' => '127.0.0.1',
                        'port' => 3306,
                        'user' => 'root',
                        'password' => '',
                        'database' => 'app_db',
                        'charset' => 'utf8mb4',
                    ],
                    'migrations' => [
                        'directory' => __DIR__.'/../database/migrations',
                        'table' => 'migrations',
                    ],
                    'seeders' => [
                        'directory' => __DIR__.'/../database/seeders',
                        'table' => 'seeders',
                    ],
                ];
                CONFIG
        );

        chdir($this->workspace_path);
    }

    private function write_counter_seeder(string $file_name): void
    {
        $seeder_path = $this->workspace_path.'/database/seeders/'.$file_name;

        file_put_contents(
            $seeder_path,
            <<<'SEEDER'
                <?php

                declare(strict_types=1);

                require __DIR__.'/../../vendor/autoload.php';

                return new class {
                    public function up(): void
                    {
                        $counter_path = __DIR__.'/../../storage/seed-counter.txt';
                        $count = is_file($counter_path) ? (int) file_get_contents($counter_path) : 0;
                        file_put_contents($counter_path, (string) ($count + 1));
                    }

                    public function down(): void
                    {
                        $counter_path = __DIR__.'/../../storage/seed-counter.txt';
                        $count = is_file($counter_path) ? (int) file_get_contents($counter_path) : 0;
                        file_put_contents($counter_path, (string) max(0, $count - 1));
                    }
                };
                SEEDER
        );
    }

    private function counter_value(string $file_name): string
    {
        $counter_path = $this->workspace_path.'/storage/'.$file_name;
        if (! is_file($counter_path)) {
            return '0';
        }

        $content = file_get_contents($counter_path);

        return is_string($content) ? trim($content) : '0';
    }

    /**
     * @return array<int, array{name: string, batch: int|string}>
     */
    private function tracking_rows(string $table): array
    {
        $connection = new \PDO('sqlite:'.$this->workspace_path.'/storage/tracker.sqlite');
        $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $statement = $connection->query(sprintf('SELECT name, batch FROM %s ORDER BY name ASC', $table));
        if (! $statement instanceof \PDOStatement) {
            return [];
        }

        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    /**
     * @param array<int, string> $arguments
     */
    private function run_seed_command(array $arguments): int
    {
        ob_start();

        try {
            return \harbor_seed_run($arguments);
        } finally {
            ob_end_clean();
        }
    }

    private function delete_directory_tree(string $directory_path): void
    {
        $entries = scandir($directory_path);
        if (false === $entries) {
            return;
        }

        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }

            $entry_path = $directory_path.'/'.$entry;
            if (is_dir($entry_path)) {
                $this->delete_directory_tree($entry_path);

                continue;
            }

            unlink($entry_path);
        }

        rmdir($directory_path);
    }
}
