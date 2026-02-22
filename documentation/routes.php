<?php

declare(strict_types=1);

return [
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
        'path' => '/request',
        'method' => 'GET',
        'name' => 'docs.request',
        'entry' => 'pages/request.php',
    ],
    4 => [
        'path' => '/filesystem',
        'method' => 'GET',
        'name' => 'docs.filesystem',
        'entry' => 'pages/filesystem.php',
    ],
    5 => [
        'path' => '/logging',
        'method' => 'GET',
        'name' => 'docs.logging',
        'entry' => 'pages/logging.php',
    ],
    6 => [
        'path' => '/cli',
        'method' => 'GET',
        'name' => 'docs.cli',
        'entry' => 'pages/cli.php',
    ],
    7 => [
        'method' => 'GET',
        'path' => '/404',
        'entry' => 'not_found.php',
    ],
];
