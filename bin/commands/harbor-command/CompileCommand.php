<?php

declare(strict_types=1);

namespace Harbor\CommandSystem;

require_once __DIR__.'/../../../src/Support/value.php';

use function Harbor\Support\harbor_is_blank;

final class CompileCommand extends BaseCommand
{
    public function __construct(bool $debug_mode, private readonly CommandCompiler $compiler)
    {
        parent::__construct($debug_mode);
    }

    public function execute(?string $path_argument, string $working_directory): int
    {
        [$source_path, $output_path] = $this->resolve_paths($path_argument, $working_directory);

        $this->debug(sprintf('Compiling source: %s', $source_path));
        $this->debug(sprintf('Output registry: %s', $output_path));

        $this->compiler->compile($source_path, $output_path);

        $this->info(sprintf('Command registry generated: %s', $output_path));

        return 0;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolve_paths(?string $path_argument, string $working_directory): array
    {
        if (! is_string($path_argument) || harbor_is_blank($path_argument)) {
            return [$this->source_path($working_directory), $this->registry_path($working_directory)];
        }

        $normalized_path_argument = rtrim($path_argument, '/\\');
        if (harbor_is_blank($normalized_path_argument)) {
            return [$this->source_path($working_directory), $this->registry_path($working_directory)];
        }

        if (! $this->is_absolute_path($normalized_path_argument)) {
            $normalized_path_argument = $working_directory.'/'.$normalized_path_argument;
        }

        if (is_dir($normalized_path_argument)) {
            return [$normalized_path_argument.'/.commands', $normalized_path_argument.'/commands/commands.php'];
        }

        if (str_ends_with($normalized_path_argument, '.commands')) {
            return [$normalized_path_argument, dirname($normalized_path_argument).'/commands/commands.php'];
        }

        throw new CommandException('Compile path must be a directory or .commands file path.', 2);
    }
}
