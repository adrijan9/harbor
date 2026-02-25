<?php

declare(strict_types=1);

return [
    [
        'method' => 'GET',
        'path' => '/',
        'entry' => 'entries/throws.php',
    ],
    [
        'method' => 'GET',
        'path' => '/404',
        'entry' => 'entries/not_found.php',
    ],
];
