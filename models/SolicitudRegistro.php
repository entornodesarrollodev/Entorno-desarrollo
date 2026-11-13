<?php
declare(strict_types=1);

require_once __DIR__ . '/Estudiante.php';

final class SolicitudRegistro
{
    // Este bloque recibe la conexión usada para solicitudes de registro.
    public function __construct(private PDO $conexion)
    {
    }

    // Este bloque abre una solicitud de registro para revisión administrativa.
    public function create(int $usuarioId): void
    {
        $consulta = $this->conexion->prepare(
            'INSERT INTO solicitudes_registro (usuario_id) VALUES (:usuario_id)'
        );
        $consulta->execute(['usuario_id' => $usuarioId]);
    }

    // Este bloque lista solicitudes de cuenta que todavía esperan una decisión.
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

    public function contarPendientes(string $busqueda = ''): int
    {
        $sql = "SELECT COUNT(*)
                FROM solicitudes_registro s
                JOIN usuarios u ON u.usuario_id = s.usuario_id
                WHERE s.estado_solicitud = 'pendiente'";
        $parametros = [];
        $busqueda = trim($busqueda);
        if ($busqueda !== '') {
            $sql .= ' AND (u.nombre_completo LIKE :nombre OR u.correo LIKE :correo
                       OR COALESCE(u.carnet, \'\') LIKE :carnet)';
            $parametros = [
                'nombre' => '%' . $busqueda . '%',
                'correo' => '%' . $busqueda . '%',
                'carnet' => '%' . $busqueda . '%',
            ];
        }

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($parametros);

        return (int) $consulta->fetchColumn();
    }

    public function pendientesPaginadas(string $busqueda, int $limite, int $desplazamiento): array
    {
        if ($limite < 1 || $limite > 100 || $desplazamiento < 0) {
            throw new InvalidArgumentException('La paginación de solicitudes no es válida.');
        }

        $sql = "SELECT s.solicitud_id, s.fecha_solicitud, u.usuario_id, u.nombre_completo,
                       u.correo, u.carnet, u.carrera
                FROM solicitudes_registro s
                JOIN usuarios u ON u.usuario_id = s.usuario_id
                WHERE s.estado_solicitud = 'pendiente'";
        $consulta = $this->conexion->prepare($sql . ($busqueda !== ''
            ? ' AND (u.nombre_completo LIKE :nombre OR u.correo LIKE :correo
                     OR COALESCE(u.carnet, \'\') LIKE :carnet)'
            : '') . ' ORDER BY s.fecha_solicitud ASC, s.solicitud_id ASC LIMIT :limite OFFSET :desplazamiento');
        if ($busqueda !== '') {
            $patron = '%' . trim($busqueda) . '%';
            $consulta->bindValue(':nombre', $patron, PDO::PARAM_STR);
            $consulta->bindValue(':correo', $patron, PDO::PARAM_STR);
            $consulta->bindValue(':carnet', $patron, PDO::PARAM_STR);
        }
        $consulta->bindValue(':limite', $limite, PDO::PARAM_INT);
        $consulta->bindValue(':desplazamiento', $desplazamiento, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll();
    }

    // Este bloque resuelve una solicitud y activa o bloquea su cuenta dentro de una transacción.
    public function resolver(int $solicitudId, int $revisorId, string $decision, ?string $nombreCompleto = null): bool
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

            if ($decision === 'aceptada' && $nombreCompleto !== null) {
                $nombreCompleto = trim($nombreCompleto);
                if (!Validacion::nombreCompletoValido($nombreCompleto) || Validacion::tieneEmoji($nombreCompleto)) {
                    throw new InvalidArgumentException('Para aprobar el registro, corrige el nombre e incluye al menos un nombre y un apellido.');
                }
                $actualizarNombre = $this->conexion->prepare(
                    'UPDATE usuarios SET nombre_completo = :nombre WHERE usuario_id = :usuario_id'
                );
                $actualizarNombre->execute([
                    'nombre' => $nombreCompleto,
                    'usuario_id' => (int) $solicitud['usuario_id'],
                ]);
            }

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

    // Este bloque comprueba si la cuenta sigue bloqueada por una solicitud no aprobada.
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
