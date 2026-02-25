<?php

declare(strict_types=1);

$error_data = isset($error) && is_array($error) ? $error : [];
$message = is_string($error_data['message'] ?? null) ? $error_data['message'] : '';
$file = is_string($error_data['file'] ?? null) ? $error_data['file'] : '';
$line = is_int($error_data['line'] ?? null) ? $error_data['line'] : 0;

echo sprintf('Internal Server Error | %s | %s | %d', $message, $file, $line);
