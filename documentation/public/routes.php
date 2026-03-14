<?php

declare(strict_types=1);

return [
    'assets' => '/assets',
    'routes' => [
        [
            'path' => '/',
            'method' => 'GET',
            'name' => 'docs.home',
            'entry' => 'pages/index.php',
        ],
        [
            'path' => '/load-helpers',
            'method' => 'GET',
            'name' => 'docs.load_helpers',
            'entry' => 'pages/load-helpers.php',
        ],
        [
            'path' => '/installation',
            'method' => 'GET',
            'name' => 'docs.installation',
            'entry' => 'pages/installation.php',
        ],
        [
            'path' => '/routing',
            'method' => 'GET',
            'name' => 'docs.routing',
            'entry' => 'pages/routing.php',
        ],
        [
            'path' => '/config',
            'method' => 'GET',
            'name' => 'docs.config',
            'entry' => 'pages/config.php',
        ],
        [
            'path' => '/database',
            'method' => 'GET',
            'name' => 'docs.database',
            'entry' => 'pages/database.php',
        ],
        [
            'path' => '/model',
            'method' => 'GET',
            'name' => 'docs.model',
            'entry' => 'pages/model.php',
        ],
        [
            'path' => '/model/pagination',
            'method' => 'GET',
            'name' => 'docs.model_pagination',
            'entry' => 'pages/model-pagination.php',
        ],
        [
            'path' => '/migrations',
            'method' => 'GET',
            'name' => 'docs.migrations',
            'entry' => 'pages/migrations.php',
        ],
        [
            'path' => '/lang',
            'method' => 'GET',
            'name' => 'docs.lang',
            'entry' => 'pages/lang.php',
        ],
        [
            'path' => '/support',
            'method' => 'GET',
            'name' => 'docs.support',
            'entry' => 'pages/support.php',
        ],
        [
            'path' => '/date',
            'method' => 'GET',
            'name' => 'docs.date',
            'entry' => 'pages/date.php',
        ],
        [
            'path' => '/pipeline',
            'method' => 'GET',
            'name' => 'docs.pipeline',
            'entry' => 'pages/pipeline.php',
        ],
        [
            'path' => '/middleware',
            'method' => 'GET',
            'name' => 'docs.middleware',
            'entry' => 'pages/middleware.php',
        ],
        [
            'path' => '/validation',
            'method' => 'GET',
            'name' => 'docs.validation',
            'entry' => 'pages/validation.php',
        ],
        [
            'path' => '/request',
            'method' => 'GET',
            'name' => 'docs.request',
            'entry' => 'pages/request.php',
        ],
        [
            'path' => '/cookie',
            'method' => 'GET',
            'name' => 'docs.cookie',
            'entry' => 'pages/cookie.php',
        ],
        [
            'path' => '/session',
            'method' => 'GET',
            'name' => 'docs.session',
            'entry' => 'pages/session.php',
        ],
        [
            'path' => '/password',
            'method' => 'GET',
            'name' => 'docs.password',
            'entry' => 'pages/password.php',
        ],
        [
            'path' => '/auth',
            'method' => 'GET',
            'name' => 'docs.auth',
            'entry' => 'pages/auth.php',
        ],
        [
            'path' => '/response',
            'method' => 'GET',
            'name' => 'docs.response',
            'entry' => 'pages/response.php',
        ],
        [
            'path' => '/performance',
            'method' => 'GET',
            'name' => 'docs.performance',
            'entry' => 'pages/performance.php',
        ],
        [
            'path' => '/units',
            'method' => 'GET',
            'name' => 'docs.units',
            'entry' => 'pages/units.php',
        ],
        [
            'path' => '/filesystem',
            'method' => 'GET',
            'name' => 'docs.filesystem',
            'entry' => 'pages/filesystem.php',
        ],
        [
            'path' => '/cache',
            'method' => 'GET',
            'name' => 'docs.cache',
            'entry' => 'pages/cache.php',
        ],
        [
            'path' => '/logging',
            'method' => 'GET',
            'name' => 'docs.logging',
            'entry' => 'pages/logging.php',
        ],
        [
            'path' => '/cli',
            'method' => 'GET',
            'name' => 'docs.cli',
            'entry' => 'pages/cli.php',
        ],
        [
            'path' => '/test',
            'method' => 'POST',
            'name' => 'docs.cli',
            'entry' => 'pages/cli.php',
        ],
        [
            'method' => 'GET',
            'path' => '/404',
            'entry' => 'pages/error/404.php',
        ],
    ],
];
