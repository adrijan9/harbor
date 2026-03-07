<?php

declare(strict_types=1);

namespace Harbor\Pipeline;

function pipeline_new(): array
{
    return [
        'passable' => [],
        'actions' => [],
        'result' => null,
        'closed' => false,
    ];
}

function pipeline_send(array &$pipeline, mixed ...$passable): void
{
    pipeline_bootstrap($pipeline);

    $pipeline['passable'] = $passable;
    $pipeline['result'] = null;
    $pipeline['closed'] = false;
}

function pipeline_through(array &$pipeline, callable ...$actions): void
{
    pipeline_bootstrap($pipeline);

    $pipeline['actions'] = array_values($actions);
    $pipeline['result'] = null;
    $pipeline['closed'] = false;
}

function pipeline_clog(array &$pipeline): void
{
    pipeline_bootstrap($pipeline);

    $destination = static fn (mixed ...$passable): mixed => pipeline_finalize_passable($passable);

    $runner = array_reduce(
        array_reverse($pipeline['actions']),
        static function (callable $next_action, callable $action): callable {
            return static function (mixed ...$current_passable) use ($action, $next_action): mixed {
                $next = static function (mixed ...$next_passable) use ($next_action, $current_passable): mixed {
                    if (empty($next_passable)) {
                        $next_passable = $current_passable;
                    }

                    return $next_action(...$next_passable);
                };

                $action_arguments = [...$current_passable, $next];

                return $action(...$action_arguments);
            };
        },
        $destination
    );

    $result = $runner(...$pipeline['passable']);

    $pipeline['result'] = $result;
    $pipeline['closed'] = true;

    $GLOBALS[pipeline_result_global_key()] = $result;
}

function pipeline_get(): mixed
{
    return $GLOBALS[pipeline_result_global_key()] ?? null;
}

function pipeline_bootstrap(array &$pipeline): void
{
    if (! isset($pipeline['passable']) || ! is_array($pipeline['passable'])) {
        $pipeline['passable'] = [];
    }

    if (! isset($pipeline['actions']) || ! is_array($pipeline['actions'])) {
        $pipeline['actions'] = [];
    }

    if (! array_key_exists('result', $pipeline)) {
        $pipeline['result'] = null;
    }

    if (! isset($pipeline['closed']) || ! is_bool($pipeline['closed'])) {
        $pipeline['closed'] = false;
    }
}

function pipeline_finalize_passable(array $passable): mixed
{
    if (empty($passable)) {
        return null;
    }

    if (1 === count($passable)) {
        return $passable[0];
    }

    return $passable;
}

function pipeline_result_global_key(): string
{
    return '__harbor_pipeline_last_result';
}
