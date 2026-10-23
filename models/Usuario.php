<?php
declare(strict_types=1);

require_once __DIR__ . '/Validacion.php';

final class Usuario
{
    // Aqui viven las consultas basicas para las cuentas del sistema.
    public function __construct(private PDO $conexion)
    {
    }

    public function create(
        string $nombreCompleto,
        string $correo,
        ?string $carnet,
        string $clave,
        string $nombreRol,
        bool $activo,
        ?string $carrera = null,
        bool $crearSolicitud = false
    ): int {
        if (
            !Validacion::correoItcaValido($correo)
            || Validacion::tieneEmoji($nombreCompleto)
            || ($carnet !== null && Validacion::tieneEmoji($carnet))
            || Validacion::tieneEmoji($clave)
            || ($carrera !== null && Validacion::tieneEmoji($carrera))
        ) {
            throw new InvalidArgumentException('Revisa los datos: usa correo ITCA y no incluyas emojis.');
        }

        // Guardamos la cuenta y su rol juntos para no dejar registros a medias.
        $this->conexion->beginTransaction();
        try {
            $consulta = $this->conexion->prepare(
                'INSERT INTO usuarios (nombre_completo, correo, carnet, clave_hash, estado_activo, carrera)
                 VALUES (:nombre_completo, :correo, :carnet, :clave_hash, :estado_activo, :carrera)'
            );
            $consulta->execute([
                'nombre_completo' => $nombreCompleto,
                'correo' => $correo,
                'carnet' => $carnet,
                'clave_hash' => password_hash($clave, PASSWORD_DEFAULT),
                'estado_activo' => $activo ? 1 : 0,
                'carrera' => $carrera,
            ]);
            $usuarioId = (int) $this->conexion->lastInsertId();
            $rol = (new Rol($this->conexion))->findByName($nombreRol);

            if ($rol === null) {
                throw new InvalidArgumentException('El rol seleccionado no existe.');
            }

            (new Rol($this->conexion))->assign($usuarioId, (int) $rol['rol_id']);
            if ($crearSolicitud) {
                (new SolicitudRegistro($this->conexion))->create($usuarioId);
            }
            $this->conexion->commit();

            return $usuarioId;
        } catch (Throwable $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            throw $error;
        }
    }

    public function authenticate(string $identificador, string $clave): ?array
    {
        if (Validacion::tieneEmoji($identificador) || Validacion::tieneEmoji($clave)) {
            return null;
        }

        $consulta = $this->conexion->prepare(
            'SELECT usuario_id, nombre_completo, correo, carnet, clave_hash, estado_activo
             FROM usuarios
             WHERE (correo = :correo OR carnet = :carnet) AND estado_activo = 1'
        );
        $consulta->execute(['correo' => $identificador, 'carnet' => $identificador]);
        $usuario = $consulta->fetch();

        if ($usuario === false || !password_verify($clave, $usuario['clave_hash'])) {
            return null;
        }

        unset($usuario['clave_hash']);

        return $usuario;
    }

    public function hasPendingRegistration(string $identificador): bool
    {
        $consulta = $this->conexion->prepare(
            "SELECT 1
             FROM solicitudes_registro s
             JOIN usuarios u ON u.usuario_id = s.usuario_id
             WHERE (u.correo = :correo OR u.carnet = :carnet)
               AND s.estado_solicitud = 'pendiente'"
        );
        $consulta->execute(['correo' => $identificador, 'carnet' => $identificador]);

        return $consulta->fetchColumn() !== false;
    }

    public function findActiveById(int $usuarioId): ?array
    {
        $consulta = $this->conexion->prepare(
            'SELECT usuario_id, nombre_completo, correo, carnet, estado_activo
             FROM usuarios WHERE usuario_id = :usuario_id AND estado_activo = 1'
        );
        $consulta->execute(['usuario_id' => $usuarioId]);
        $usuario = $consulta->fetch();

        return $usuario === false ? null : $usuario;
    }

    public function allWithRoles(): array
    {
        return $this->conexion->query(
            'SELECT u.usuario_id, u.nombre_completo, u.correo, u.estado_activo,
                    r.rol_id, r.nombre_rol, r.etiqueta
             FROM usuarios u
             LEFT JOIN usuarios_roles ur ON ur.usuario_id = u.usuario_id
             LEFT JOIN roles r ON r.rol_id = ur.rol_id
             ORDER BY u.usuario_id'
        )->fetchAll();
    }

    public function setActive(int $usuarioId, bool $activo): void
    {
        $consulta = $this->conexion->prepare(
            'UPDATE usuarios SET estado_activo = :estado_activo WHERE usuario_id = :usuario_id'
        );
        $consulta->execute(['estado_activo' => $activo ? 1 : 0, 'usuario_id' => $usuarioId]);

        if ($consulta->rowCount() === 0 && $this->findById($usuarioId) === null) {
            throw new InvalidArgumentException('La cuenta solicitada no existe.');
        }
    }

    public function findById(int $usuarioId): ?array
    {
        $consulta = $this->conexion->prepare(
            'SELECT usuario_id, estado_activo FROM usuarios WHERE usuario_id = :usuario_id'
        );
        $consulta->execute(['usuario_id' => $usuarioId]);
        $usuario = $consulta->fetch();

        return $usuario === false ? null : $usuario;
    }

    public function emailExists(string $correo): bool
    {
        $consulta = $this->conexion->prepare('SELECT 1 FROM usuarios WHERE correo = :correo');
        $consulta->execute(['correo' => $correo]);

        return $consulta->fetchColumn() !== false;
    }

    public function carnetExists(string $carnet): bool
    {
        $consulta = $this->conexion->prepare('SELECT 1 FROM usuarios WHERE carnet = :carnet');
        $consulta->execute(['carnet' => $carnet]);

        return $consulta->fetchColumn() !== false;
    }
}
