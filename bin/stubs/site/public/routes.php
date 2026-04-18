<?php

declare(strict_types=1);

return [
    'assets' => null,
    'routes' => [
        [
            'path' => '/robots.txt',
            'method' => 'GET',
            'name' => 'robots',
            'entry' => 'robots.txt',
        ],
        [
            'path' => '/',
            'method' => 'GET',
            'name' => 'home',
            'entry' => 'pages/index.php',
        ],
        [
            'method' => 'GET',
            'path' => '/404',
            'entry' => 'pages/error/404.php',
        ],
    ],
];
