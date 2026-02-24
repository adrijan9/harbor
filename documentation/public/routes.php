<?php

declare(strict_types=1);

return [
    'assets' => '/assets',
    'routes' => [
        0 => [
            'path' => '/',
            'method' => 'GET',
            'name' => 'docs.home',
            'entry' => 'pages/index.php',
        ],
        1 => [
            'path' => '/installation',
            'method' => 'GET',
            'name' => 'docs.installation',
            'entry' => 'pages/installation.php',
        ],
        2 => [
            'path' => '/routing',
            'method' => 'GET',
            'name' => 'docs.routing',
            'entry' => 'pages/routing.php',
        ],
        3 => [
            'path' => '/config',
            'method' => 'GET',
            'name' => 'docs.config',
            'entry' => 'pages/config.php',
        ],
        4 => [
            'path' => '/lang',
            'method' => 'GET',
            'name' => 'docs.lang',
            'entry' => 'pages/lang.php',
        ],
        5 => [
            'path' => '/support',
            'method' => 'GET',
            'name' => 'docs.support',
            'entry' => 'pages/support.php',
        ],
        6 => [
            'path' => '/request',
            'method' => 'GET',
            'name' => 'docs.request',
            'entry' => 'pages/request.php',
        ],
        7 => [
            'path' => '/filesystem',
            'method' => 'GET',
            'name' => 'docs.filesystem',
            'entry' => 'pages/filesystem.php',
        ],
        8 => [
            'path' => '/logging',
            'method' => 'GET',
            'name' => 'docs.logging',
            'entry' => 'pages/logging.php',
        ],
        9 => [
            'path' => '/cli',
            'method' => 'GET',
            'name' => 'docs.cli',
            'entry' => 'pages/cli.php',
        ],
        10 => [
            'method' => 'GET',
            'path' => '/404',
            'entry' => 'not_found.php',
        ],
    ],
];
