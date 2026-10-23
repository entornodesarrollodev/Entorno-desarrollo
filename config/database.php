<?php
declare(strict_types=1);

function conectarBaseDatos(): PDO
{
    // Cambia estos valores si tu MySQL usa otros datos de acceso.
    $servidor = getenv('BD_SERVIDOR') ?: '127.0.0.1';
    $puerto = getenv('BD_PUERTO') ?: '3306';
    $nombreBase = getenv('BD_NOMBRE') ?: 'sistema_academico';
    $usuario = getenv('BD_USUARIO') ?: 'root';
    $clave = getenv('BD_CLAVE') ?: '';

    if (preg_match('/^[A-Za-z0-9_]+$/', $nombreBase) !== 1) {
        throw new InvalidArgumentException('El nombre de la base de datos configurado no es valido.');
    }

    // La conexion queda lista para guardar y leer texto en espanol sin problemas.
    $conexion = new PDO(
        "mysql:host={$servidor};port={$puerto};dbname={$nombreBase};charset=utf8mb4",
        $usuario,
        $clave,
        [PDO::ATTR_EMULATE_PREPARES => false]
    );
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $conexion;
}
