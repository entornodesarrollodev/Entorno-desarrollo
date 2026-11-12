<?php
declare(strict_types=1);

// Este bloque bloquea recursos internos antes de entregar la petición al enrutador.
$ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (str_starts_with($ruta, '/Views/') || str_starts_with($ruta, '/database/')) {
    http_response_code(404);
    exit('No encontrado.');
}

// Aqui solo dejamos pasar las rutas de la app al punto de entrada.
require __DIR__ . '/index.php';
