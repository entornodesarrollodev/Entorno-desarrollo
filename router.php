<?php
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (str_starts_with($path, '/Views/') || str_starts_with($path, '/data/')) {
    http_response_code(404);
    exit('No encontrado.');
}

require __DIR__ . '/index.php';
