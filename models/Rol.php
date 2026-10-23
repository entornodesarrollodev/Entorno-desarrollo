<?php
declare(strict_types=1);

final class Rol
{
    // Los permisos se consultan aqui antes de abrir una accion protegida.
    public function __construct(private PDO $conexion)
    {
    }

    public function all(): array
    {
        return $this->conexion->query(
            'SELECT rol_id, nombre_rol, etiqueta FROM roles ORDER BY rol_id'
        )->fetchAll();
    }

    public function findByName(string $nombreRol): ?array
    {
        $consulta = $this->conexion->prepare(
            'SELECT rol_id, nombre_rol, etiqueta FROM roles WHERE nombre_rol = :nombre_rol'
        );
        $consulta->execute(['nombre_rol' => $nombreRol]);
        $rol = $consulta->fetch();

        return $rol === false ? null : $rol;
    }

    public function userHasPermission(int $usuarioId, string $permiso): bool
    {
        // No basta con esconder el boton: el servidor revisa el permiso cada vez.
        $consulta = $this->conexion->prepare(
            'SELECT 1
             FROM usuarios_roles ur
             JOIN roles_permisos rp ON rp.rol_id = ur.rol_id
             JOIN permisos p ON p.permiso_id = rp.permiso_id
             JOIN usuarios u ON u.usuario_id = ur.usuario_id
             WHERE ur.usuario_id = :usuario_id
               AND p.nombre_permiso = :permiso
               AND u.estado_activo = 1'
        );
        $consulta->execute(['usuario_id' => $usuarioId, 'permiso' => $permiso]);

        return $consulta->fetchColumn() !== false;
    }

    public function forUser(int $usuarioId): ?array
    {
        $consulta = $this->conexion->prepare(
            'SELECT r.rol_id, r.nombre_rol, r.etiqueta
             FROM usuarios_roles ur
             JOIN roles r ON r.rol_id = ur.rol_id
             WHERE ur.usuario_id = :usuario_id'
        );
        $consulta->execute(['usuario_id' => $usuarioId]);
        $rol = $consulta->fetch();

        return $rol === false ? null : $rol;
    }

    public function assign(int $usuarioId, int $rolId): void
    {
        // MySQL actualiza la asignacion si ese usuario ya tenia un rol.
        $consulta = $this->conexion->prepare(
            'INSERT INTO usuarios_roles (usuario_id, rol_id)
             VALUES (:usuario_id, :rol_id)
             ON DUPLICATE KEY UPDATE rol_id = VALUES(rol_id), fecha_asignacion = CURRENT_TIMESTAMP'
        );
        $consulta->execute(['usuario_id' => $usuarioId, 'rol_id' => $rolId]);
    }
}
