<?php
declare(strict_types=1);

require_once __DIR__ . '/Validacion.php';

final class Estudiante
{
    // Esta ficha guarda los datos academicos que no necesita cada cuenta.
    public function __construct(private PDO $conexion)
    {
    }

    public function create(
        string $nombreCompleto,
        string $correo,
        ?string $telefono,
        string $carrera,
        int $anioAcademico
    ): void {
        if (
            !Validacion::correoItcaValido($correo)
            || Validacion::tieneEmoji($nombreCompleto)
            || ($telefono !== null && Validacion::tieneEmoji($telefono))
            || Validacion::tieneEmoji($carrera)
        ) {
            throw new InvalidArgumentException('Revisa los datos: usa correo ITCA y no incluyas emojis.');
        }

        // Si ya se registro como usuario, ligamos aqui su ficha escolar.
        $consultaUsuario = $this->conexion->prepare(
            "SELECT u.usuario_id
             FROM usuarios u
             JOIN usuarios_roles ur ON ur.usuario_id = u.usuario_id
             JOIN roles r ON r.rol_id = ur.rol_id
             WHERE u.correo = :correo AND r.nombre_rol = 'estudiante'"
        );
        $consultaUsuario->execute(['correo' => $correo]);
        $usuarioId = $consultaUsuario->fetchColumn();

        $consulta = $this->conexion->prepare(
            'INSERT INTO estudiantes
                (usuario_id, nombre_completo, correo, telefono, carrera, anio_academico)
             VALUES (:usuario_id, :nombre_completo, :correo, :telefono, :carrera, :anio_academico)'
        );
        $consulta->execute([
            'usuario_id' => $usuarioId === false ? null : (int) $usuarioId,
            'nombre_completo' => $nombreCompleto,
            'correo' => $correo,
            'telefono' => $telefono,
            'carrera' => $carrera,
            'anio_academico' => $anioAcademico,
        ]);
    }
}
