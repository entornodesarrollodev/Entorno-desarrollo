<?php
declare(strict_types=1);

require_once __DIR__ . '/Estudiante.php';

final class SolicitudRegistro
{
    public function __construct(private PDO $conexion)
    {
    }

    public function create(int $usuarioId): void
    {
        $consulta = $this->conexion->prepare(
            'INSERT INTO solicitudes_registro (usuario_id) VALUES (:usuario_id)'
        );
        $consulta->execute(['usuario_id' => $usuarioId]);
    }

    public function pendientes(): array
    {
        return $this->conexion->query(
            "SELECT s.solicitud_id, s.fecha_solicitud, u.usuario_id, u.nombre_completo,
                    u.correo, u.carnet, u.carrera
             FROM solicitudes_registro s
             JOIN usuarios u ON u.usuario_id = s.usuario_id
             WHERE s.estado_solicitud = 'pendiente'
             ORDER BY s.fecha_solicitud ASC"
        )->fetchAll();
    }

    public function resolver(int $solicitudId, int $revisorId, string $decision): bool
    {
        if (!in_array($decision, ['aceptada', 'rechazada'], true)) {
            throw new InvalidArgumentException('La decision de la solicitud no es valida.');
        }

        $this->conexion->beginTransaction();
        try {
            $consulta = $this->conexion->prepare(
                'SELECT usuario_id, estado_solicitud
                 FROM solicitudes_registro
                 WHERE solicitud_id = :solicitud_id
                 FOR UPDATE'
            );
            $consulta->execute(['solicitud_id' => $solicitudId]);
            $solicitud = $consulta->fetch();

            if ($solicitud === false || $solicitud['estado_solicitud'] !== 'pendiente') {
                $this->conexion->commit();

                return false;
            }

            $actualizarSolicitud = $this->conexion->prepare(
                'UPDATE solicitudes_registro
                 SET estado_solicitud = :estado_solicitud,
                     revisada_por = :revisada_por,
                     fecha_revision = CURRENT_TIMESTAMP
                 WHERE solicitud_id = :solicitud_id'
            );
            $actualizarSolicitud->execute([
                'estado_solicitud' => $decision,
                'revisada_por' => $revisorId,
                'solicitud_id' => $solicitudId,
            ]);

            // Solo una solicitud aceptada puede activar la cuenta del alumno.
            $actualizarUsuario = $this->conexion->prepare(
                'UPDATE usuarios SET estado_activo = :estado_activo WHERE usuario_id = :usuario_id'
            );
            $actualizarUsuario->execute([
                'estado_activo' => $decision === 'aceptada' ? 1 : 0,
                'usuario_id' => $solicitud['usuario_id'],
            ]);
            if ($decision === 'aceptada') {
                (new Estudiante($this->conexion))->createForUser((int)$solicitud['usuario_id']);
            }
            $this->conexion->commit();

            return true;
        } catch (Throwable $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            throw $error;
        }
    }

    public function tieneSolicitudSinAceptar(int $usuarioId): bool
    {
        $consulta = $this->conexion->prepare(
            "SELECT 1
             FROM solicitudes_registro
             WHERE usuario_id = :usuario_id AND estado_solicitud != 'aceptada'"
        );
        $consulta->execute(['usuario_id' => $usuarioId]);

        return $consulta->fetchColumn() !== false;
    }
}
