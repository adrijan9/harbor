<?php

declare(strict_types=1);

namespace Harbor\Tests\Filesystem;

use Harbor\HelperLoader;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\TestCase;

use function Harbor\Filesystem\fs_append;
use function Harbor\Filesystem\fs_copy;
use function Harbor\Filesystem\fs_delete;
use function Harbor\Filesystem\fs_dir_create;
use function Harbor\Filesystem\fs_dir_delete;
use function Harbor\Filesystem\fs_dir_exists;
use function Harbor\Filesystem\fs_dir_is_empty;
use function Harbor\Filesystem\fs_dir_list;
use function Harbor\Filesystem\fs_exists;
use function Harbor\Filesystem\fs_move;
use function Harbor\Filesystem\fs_read;
use function Harbor\Filesystem\fs_size;
use function Harbor\Filesystem\fs_write;

final class FilesystemHelpersTest extends TestCase
{
    private string $workspace_path;

    #[BeforeClass]
    public static function load_filesystem_helpers(): void
    {
        HelperLoader::load('filesystem');
    }

    public function test_file_helpers_manage_file_lifecycle(): void
    {
        $files_path = $this->workspace_path.'/files';
        fs_dir_create($files_path);

        $file_path = $files_path.'/notes.txt';

        self::assertSame(5, fs_write($file_path, 'hello'));
        self::assertTrue(fs_exists($file_path));
        self::assertSame('hello', fs_read($file_path));
        self::assertSame(5, fs_size($file_path));

        self::assertSame(7, fs_append($file_path, ' world!'));
        self::assertSame('hello world!', fs_read($file_path));
        self::assertSame(12, fs_size($file_path));

        $copied_path = $files_path.'/copy.txt';
        self::assertTrue(fs_copy($file_path, $copied_path));
        self::assertSame('hello world!', fs_read($copied_path));

        $moved_path = $files_path.'/moved.txt';
        self::assertTrue(fs_move($copied_path, $moved_path));
        self::assertFalse(fs_exists($copied_path));
        self::assertTrue(fs_exists($moved_path));

        self::assertTrue(fs_delete($moved_path));
        self::assertFalse(fs_exists($moved_path));
    }

    public function test_fs_read_throws_when_file_is_missing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File "'.$this->workspace_path.'/missing.txt" not found.');

        fs_read($this->workspace_path.'/missing.txt');
    }

    public function test_fs_write_throws_when_parent_directory_is_missing(): void
    {
        $file_path = $this->workspace_path.'/missing/path/file.txt';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Directory "'.$this->workspace_path.'/missing/path" not found.');

        fs_write($file_path, 'content');
    }

    public function test_directory_helpers_manage_directory_lifecycle(): void
    {
        $root_directory_path = $this->workspace_path.'/project';
        $nested_directory_path = $root_directory_path.'/nested';

        self::assertTrue(fs_dir_create($nested_directory_path));
        self::assertTrue(fs_dir_exists($root_directory_path));
        self::assertTrue(fs_dir_exists($nested_directory_path));
        self::assertFalse(fs_dir_is_empty($root_directory_path));
        self::assertTrue(fs_dir_is_empty($nested_directory_path));

        fs_write($root_directory_path.'/a.txt', 'a');
        fs_write($nested_directory_path.'/b.txt', 'b');

        self::assertFalse(fs_dir_is_empty($root_directory_path));
        self::assertSame(['a.txt', 'nested'], fs_dir_list($root_directory_path));
        self::assertSame(
            [$root_directory_path.'/a.txt', $root_directory_path.'/nested'],
            fs_dir_list($root_directory_path, true)
        );

        self::assertTrue(fs_dir_delete($root_directory_path, true));
        self::assertFalse(fs_dir_exists($root_directory_path));
    }

    public function test_fs_dir_delete_throws_when_directory_is_not_empty_without_recursive_flag(): void
    {
        $directory_path = $this->workspace_path.'/non_empty_dir';

        fs_dir_create($directory_path);
        fs_write($directory_path.'/file.txt', 'data');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Directory "'.$directory_path.'" is not empty.');

        fs_dir_delete($directory_path);
    }

    public function test_fs_dir_list_throws_when_directory_is_missing(): void
    {
        $missing_path = $this->workspace_path.'/missing_dir';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Directory "'.$missing_path.'" not found.');

        fs_dir_list($missing_path);
    }

    #[Before]
    protected function create_workspace(): void
    {
        $this->workspace_path = sys_get_temp_dir().'/php_framework_fs_'.bin2hex(random_bytes(8));

        if (! mkdir($this->workspace_path, 0o777, true) && ! is_dir($this->workspace_path)) {
            throw new \RuntimeException(sprintf('Failed to create test workspace "%s".', $this->workspace_path));
        }
    }

    #[After]
    protected function cleanup_workspace(): void
    {
        if (! is_dir($this->workspace_path)) {
            return;
        }

        $this->delete_directory_tree($this->workspace_path);
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
