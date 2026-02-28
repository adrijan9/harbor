<?php

declare(strict_types=1);

namespace Harbor\Tests\Response;

use Harbor\HelperLoader;
use Harbor\Validation\ValidationResult;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\TestCase;

use function Harbor\Response\response_file;
use function Harbor\Response\response_download;
use function Harbor\Response\response_header;
use function Harbor\Response\response_json;
use function Harbor\Response\response_status;
use function Harbor\Response\response_text;
use function Harbor\Response\response_validation;

final class ResponseHelpersTest extends TestCase
{
    private string $workspace_path;

    #[BeforeClass]
    public static function load_response_helpers(): void
    {
        HelperLoader::load('response');
    }

    public function test_response_status_sets_http_status_code(): void
    {
        response_status(204);

        self::assertSame(204, http_response_code());
    }

    public function test_response_header_throws_when_name_is_blank(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Response header name must be a non-empty string.');

        response_header('   ', 'value');
    }

    public function test_response_json_outputs_json_payload_and_status_code(): void
    {
        ob_start();

        try {
            response_json([
                'ok' => true,
                'count' => 2,
            ], 201);

            $output = ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }

        self::assertSame('{"ok":true,"count":2}', $output);
        self::assertSame(201, http_response_code());
    }

    public function test_response_text_outputs_content_and_status_code(): void
    {
        ob_start();

        try {
            response_text('Harbor', 202);

            $output = ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }

        self::assertSame('Harbor', $output);
        self::assertSame(202, http_response_code());
    }

    public function test_response_file_outputs_file_content(): void
    {
        $file_path = $this->workspace_path.'/report.txt';
        file_put_contents($file_path, 'report');

        ob_start();

        try {
            response_file($file_path);

            $output = ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }

        self::assertSame('report', $output);
        self::assertSame(200, http_response_code());
    }

    public function test_response_file_throws_when_file_is_missing(): void
    {
        $missing_path = $this->workspace_path.'/missing.txt';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Response file "'.$missing_path.'" not found.');

        response_file($missing_path);
    }

    public function test_response_file_throws_when_download_name_is_blank(): void
    {
        $file_path = $this->workspace_path.'/download.txt';
        file_put_contents($file_path, 'download');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Response download name must be a non-empty string.');

        response_file($file_path, '   ');
    }

    public function test_response_download_outputs_file_content(): void
    {
        $file_path = $this->workspace_path.'/invoice.txt';
        file_put_contents($file_path, 'invoice');

        ob_start();

        try {
            response_download($file_path);

            $output = ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }

        self::assertSame('invoice', $output);
        self::assertSame(200, http_response_code());
    }

    public function test_response_validation_outputs_json_payload_when_json_is_requested(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $result = ValidationResult::failed([
            'email' => ['The email field is required.'],
        ]);

        ob_start();

        try {
            response_validation($result);

            $output = ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }

        self::assertSame('{"message":"Validation failed.","errors":{"email":["The email field is required."]}}', $output);
        self::assertSame(422, http_response_code());
    }

    public function test_response_validation_outputs_text_payload_when_json_is_not_requested(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html';
        $result = ValidationResult::failed([
            'email' => ['The email field is required.'],
        ]);

        ob_start();

        try {
            response_validation($result);

            $output = ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }

        self::assertSame('Validation failed.', $output);
        self::assertSame(422, http_response_code());
    }

    #[Before]
    protected function prepare_environment(): void
    {
        http_response_code(200);
        header_remove();

        $this->workspace_path = sys_get_temp_dir().'/harbor_response_'.bin2hex(random_bytes(8));

        if (! mkdir($this->workspace_path, 0o777, true) && ! is_dir($this->workspace_path)) {
            throw new \RuntimeException(sprintf('Failed to create test workspace "%s".', $this->workspace_path));
        }
    }

    #[After]
    protected function cleanup_environment(): void
    {
        http_response_code(200);
        header_remove();

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
