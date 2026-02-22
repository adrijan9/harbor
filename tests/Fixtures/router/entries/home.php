<?php

declare(strict_types=1);

echo 'Hello '.($name ?? 'Guest').' from '.($GLOBALS['route']['path'] ?? '');
