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
            'path' => '/request',
            'method' => 'GET',
            'name' => 'docs.request',
            'entry' => 'pages/request.php',
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
