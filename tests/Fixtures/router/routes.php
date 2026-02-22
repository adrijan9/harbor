<?php

declare(strict_types=1);

return [
    [
        'method' => 'GET',
        'path' => '/',
        'entry' => 'entries/home.php',
    ],
    [
        'method' => 'GET',
        'path' => '/posts/$',
        'entry' => 'entries/post.php',
    ],
    [
        'method' => 'GET',
        'path' => '/404',
        'entry' => 'entries/not_found.php',
    ],
];
