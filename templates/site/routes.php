<?php

return [
    'assets' => null,
    'routes' => [
        [
            'path' => '/',
            'method' => 'GET',
            'name' => 'home',
            'entry' => 'pages/index.php',
        ],
        [
            'method' => 'GET',
            'path' => '/404',
            'entry' => 'not_found.php',
        ],
    ],
];
